<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/APIKeyManager.php';
require_once __DIR__ . '/../../core/PermissionManager.php';
require_once __DIR__ . '/../../core/FileManager.php';
require_once __DIR__ . '/../../core/ImageService.php';
require_once __DIR__ . '/../../core/EnhancementService.php';
require_once __DIR__ . '/../../core/AIService.php';
require_once __DIR__ . '/../../core/SubscriptionService.php';

// Allow external API requests (CORS headers)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 1. Authenticate API Key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
if (empty($apiKey)) {
    Response::error('API Key required. Send it via X-API-Key header or api_key parameter.', 401);
}

$keyManager = new APIKeyManager();
$keyDetails = $keyManager->validateKey($apiKey);

if ($keyDetails === false) {
    Response::error('Invalid or revoked API Key.', 401);
}

$userId = (int)$keyDetails['user_id'];
$keyId = (int)$keyDetails['key_id'];
$role = $keyDetails['role'];

// 2. Check Plan API access Permission
if (!PermissionManager::checkPermission($userId, 'api_access')) {
    Response::error('API access is disabled on your current subscription plan. Please upgrade.', 403);
}

$pdo = Database::getConnection();
if ($pdo === null) {
    Response::error('Database offline.', 500);
}

// 3. Enforce API Rate Limit (e.g. 100 requests per day for standard keys, 2000 for admins/enterprise)
$limit = ($role === 'admin' || $role === 'super_admin') ? 5000 : 200;
$stmtLimit = $pdo->prepare("
    SELECT COUNT(*) FROM usage_logs 
    WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
");
$stmtLimit->execute([$keyId]);
$requestCount = (int)$stmtLimit->fetchColumn();

if ($requestCount >= $limit) {
    Response::error("Rate limit exceeded. Max {$limit} requests per day.", 429);
}

// 4. Resolve routing action
$endpoint = $_GET['endpoint'] ?? '';
if (empty($endpoint)) {
    Response::error('Missing endpoint query parameter.', 400);
}

switch (strtolower($endpoint)) {
    case 'upload':
        handleUploadEndpoint($userId, $keyId, $pdo);
        break;
    case 'convert':
        handleConvertEndpoint($userId, $keyId, $pdo);
        break;
    case 'resize':
        handleResizeEndpoint($userId, $keyId, $pdo);
        break;
    case 'enhance':
        handleEnhanceEndpoint($userId, $keyId, $pdo);
        break;
    case 'upscale':
        handleUpscaleEndpoint($userId, $keyId, $pdo);
        break;
    case 'remove-background':
        handleRemoveBackgroundEndpoint($userId, $keyId, $pdo);
        break;
    default:
        Response::error('Unsupported API endpoint.', 404);
}

/**
 * Handle image uploads
 */
function handleUploadEndpoint(int $userId, int $keyId, PDO $pdo) {
    if (!isset($_FILES['image'])) {
        Response::error('No image file uploaded in parameter "image".', 400);
    }
    
    $file = $_FILES['image'];
    if (!Validator::validateFile($file)) {
        Response::error('Corrupted or invalid file structure.', 400);
    }

    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    // Verify format limits
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];
    if (!Validator::validateFormat($originalName, $allowed)) {
        Response::error('Unsupported upload format.', 400);
    }

    // Verify size (10 MB)
    if (!Validator::validateSize($file['size'], Config::MAX_FILE_SIZE)) {
        Response::error('File size exceeds the 10 MB limit.', 400);
    }

    // Storage Limit check
    if (!PermissionManager::checkStorageLimit($userId, $file['size'])) {
        Response::error('Upload failed. Your cloud storage quota limit has been exceeded.', 403);
    }

    $fileManager = new FileManager();
    $uploadResult = $fileManager->upload($file, Config::UPLOAD_PATH);
    if (!$uploadResult) {
        Response::error('Failed to save uploaded image.', 500);
    }

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'upload', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "Uploaded API image: {$originalName}", $file['size']]);

    Response::success([
        'filename' => $uploadResult['filename'],
        'original_name' => $uploadResult['original_name'],
        'size' => $uploadResult['file_size'],
        'width' => $uploadResult['width'],
        'height' => $uploadResult['height'],
        'extension' => $uploadResult['extension']
    ], 'Image uploaded successfully!');
}

/**
 * Handle conversion
 */
function handleConvertEndpoint(int $userId, int $keyId, PDO $pdo) {
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? '';
    $targetFormat = strtolower($_POST['format'] ?? $_GET['format'] ?? '');

    if (empty($filename) || empty($targetFormat)) {
        Response::error('Parameters filename and format are required.', 400);
    }

    $sourcePath = Config::UPLOAD_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
        Response::error('Source file not found or path denied.', 404);
    }

    $imageService = new ImageService();
    $result = $imageService->convertImage($sourcePath, $targetFormat);

    if (!$result) {
        Response::error('Conversion failed.', 500);
    }

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'convert', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "Converted {$filename} to {$targetFormat}", $result['file_size']]);

    Response::success([
        'filename' => $result['filename'],
        'original_format' => $result['original_format'],
        'converted_format' => $result['converted_format'],
        'size' => $result['file_size'],
        'width' => $result['width'],
        'height' => $result['height']
    ], 'Conversion complete.');
}

/**
 * Handle resizing
 */
function handleResizeEndpoint(int $userId, int $keyId, PDO $pdo) {
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? '';
    $width = isset($_POST['width']) ? (int)$_POST['width'] : (isset($_GET['width']) ? (int)$_GET['width'] : 0);
    $height = isset($_POST['height']) ? (int)$_POST['height'] : (isset($_GET['height']) ? (int)$_GET['height'] : 0);
    $maintainRatio = isset($_POST['maintainRatio']) ? (bool)(int)$_POST['maintainRatio'] : true;

    if (empty($filename)) {
        Response::error('Parameter filename is required.', 400);
    }

    $sourcePath = Config::UPLOAD_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
        Response::error('Source file not found.', 404);
    }

    $imageService = new ImageService();
    $result = $imageService->resizeImage($sourcePath, $width, $height, $maintainRatio);

    if (!$result) {
        Response::error('Resizing failed.', 500);
    }

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'resize', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "Resized {$filename} to {$result['width']}x{$result['height']}", $result['file_size']]);

    Response::success([
        'filename' => $result['filename'],
        'size' => $result['file_size'],
        'width' => $result['width'],
        'height' => $result['height']
    ], 'Resize complete.');
}

/**
 * Handle enhancement filters
 */
function handleEnhanceEndpoint(int $userId, int $keyId, PDO $pdo) {
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? '';
    if (empty($filename)) {
        Response::error('Parameter filename is required.', 400);
    }

    $sourcePath = Config::UPLOAD_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
        Response::error('Source file not found.', 404);
    }

    $brightness = isset($_POST['brightness']) ? (int)$_POST['brightness'] : (isset($_GET['brightness']) ? (int)$_GET['brightness'] : 0);
    $contrast = isset($_POST['contrast']) ? (int)$_POST['contrast'] : (isset($_GET['contrast']) ? (int)$_GET['contrast'] : 0);
    $saturation = isset($_POST['saturation']) ? (int)$_POST['saturation'] : (isset($_GET['saturation']) ? (int)$_GET['saturation'] : 0);

    $targetFilename = pathinfo($filename, PATHINFO_FILENAME) . '_api_enhance.' . pathinfo($filename, PATHINFO_EXTENSION);
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    $enhance = new EnhancementService();
    $result = $enhance->applySliders($sourcePath, $targetPath, [
        'brightness' => $brightness,
        'contrast' => $contrast,
        'saturation' => $saturation
    ]);

    if (!$result) {
        Response::error('Enhancement processing failed.', 500);
    }

    $newSize = filesize($targetPath);

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'enhance', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "Enhanced sliders on {$filename}", $newSize]);

    Response::success([
        'filename' => $targetFilename,
        'size' => $newSize
    ], 'Enhancement applied.');
}

/**
 * Handle AI super-resolution upscaling
 */
function handleUpscaleEndpoint(int $userId, int $keyId, PDO $pdo) {
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? '';
    if (empty($filename)) {
        Response::error('Parameter filename is required.', 400);
    }

    $sourcePath = Config::UPLOAD_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
        Response::error('Source file not found.', 404);
    }

    // Check credits
    if (!PermissionManager::checkPermission($userId, 'ai_request')) {
        Response::error('Insufficient AI Credits or AI operations limit exceeded for today.', 403);
    }

    $targetFilename = pathinfo($filename, PATHINFO_FILENAME) . '_api_upscale.' . pathinfo($filename, PATHINFO_EXTENSION);
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    $ai = new AIService();
    if (!$ai->upscale($sourcePath, $targetPath, 2)) {
        Response::error('AI Upscaling failed. Make sure the AI FastAPI microservice is online.', 500);
    }

    $newSize = filesize($targetPath);

    // Consume AI credit
    $subService = new SubscriptionService();
    $subService->consumeAICredit($userId);

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'ai_request', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "AI Upscaled {$filename}", $newSize]);

    Response::success([
        'filename' => $targetFilename,
        'size' => $newSize
    ], 'AI Upscaling complete.');
}

/**
 * Handle AI background removal
 */
function handleRemoveBackgroundEndpoint(int $userId, int $keyId, PDO $pdo) {
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? '';
    if (empty($filename)) {
        Response::error('Parameter filename is required.', 400);
    }

    $sourcePath = Config::UPLOAD_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
        Response::error('Source file not found.', 404);
    }

    // Check credits
    if (!PermissionManager::checkPermission($userId, 'ai_request')) {
        Response::error('Insufficient AI Credits or AI operations limit exceeded for today.', 403);
    }

    $targetFilename = pathinfo($filename, PATHINFO_FILENAME) . '_api_nobg.png'; // transparent PNG
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    $ai = new AIService();
    if (!$ai->removeBackground($sourcePath, $targetPath)) {
        Response::error('AI Background Removal failed. Make sure the AI FastAPI microservice is online.', 500);
    }

    $newSize = filesize($targetPath);

    // Consume AI credit
    $subService = new SubscriptionService();
    $subService->consumeAICredit($userId);

    // Log usage
    $stmt = $pdo->prepare("
        INSERT INTO usage_logs (user_id, api_key_id, action, details, bytes) 
        VALUES (?, ?, 'ai_request', ?, ?)
    ");
    $stmt->execute([$userId, $keyId, "AI Background Removal on {$filename}", $newSize]);

    Response::success([
        'filename' => $targetFilename,
        'size' => $newSize
    ], 'AI Background Removal complete.');
}
