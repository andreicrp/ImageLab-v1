<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/EnhancementService.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get input parameters
$filename = $_POST['filename'] ?? '';
if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

// Build paths
$sourcePath = Config::UPLOAD_PATH . $filename;

// Security check: Verify file exists and path is safe
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    Response::error('Source file not found or access denied.', 404);
}

// Prepare target path
$sourceFilename = pathinfo($filename, PATHINFO_FILENAME);
$sourceExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$targetFilename = $sourceFilename . '_enhanced_' . time() . '.' . $sourceExt;
$targetPath = Config::PROCESSED_PATH . $targetFilename;

$service = new EnhancementService();
$success = false;

// Check if applying an auto preset mode
$mode = $_POST['mode'] ?? '';
if (!empty($mode)) {
    $success = $service->applyAutoEnhance($sourcePath, $targetPath, $mode);
    $operation = 'Auto Enhance: ' . ucfirst($mode);
} else {
    // Custom sliders
    $params = [
        'brightness'  => isset($_POST['brightness']) ? (int)$_POST['brightness'] : 0,
        'contrast'    => isset($_POST['contrast']) ? (int)$_POST['contrast'] : 0,
        'saturation'  => isset($_POST['saturation']) ? (int)$_POST['saturation'] : 0,
        'sharpness'   => isset($_POST['sharpness']) ? (int)$_POST['sharpness'] : 0,
        'exposure'    => isset($_POST['exposure']) ? (int)$_POST['exposure'] : 0,
        'highlights'  => isset($_POST['highlights']) ? (int)$_POST['highlights'] : 0,
        'shadows'     => isset($_POST['shadows']) ? (int)$_POST['shadows'] : 0,
        'temperature' => isset($_POST['temperature']) ? (int)$_POST['temperature'] : 0,
        'tint'        => isset($_POST['tint']) ? (int)$_POST['tint'] : 0
    ];
    $success = $service->applySliders($sourcePath, $targetPath, $params);
    $operation = 'Custom Enhancement';
}

if (!$success || !file_exists($targetPath)) {
    Response::error('Failed to apply enhancement.', 500);
}

$originalSize = filesize($sourcePath);
$newSize = filesize($targetPath);

// Add to database / JSON history log
$history = new HistoryManager();
$history->addHistory(
    $filename,
    $targetFilename,
    $operation,
    $originalSize,
    $newSize
);

// Fetch image dimensions if possible
$width = 800;
$height = 600;
$imageInfo = @getimagesize($targetPath);
if ($imageInfo !== false) {
    $width = $imageInfo[0];
    $height = $imageInfo[1];
}

Response::json([
    'success' => true,
    'filename' => $targetFilename,
    'original_size' => $originalSize,
    'new_size' => $newSize,
    'width' => $width,
    'height' => $height,
    'extension' => $sourceExt
]);

