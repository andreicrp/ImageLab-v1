<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/FileManager.php';
require_once __DIR__ . '/../core/WatermarkManager.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get parameters
$filename = $_POST['filename'] ?? '';
$type = $_POST['type'] ?? 'text'; // 'text' or 'logo'

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

$sourcePath = Config::UPLOAD_PATH . $filename;
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

// Generate output filename
$sourceFilename = pathinfo($filename, PATHINFO_FILENAME);
$sourceExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$targetFilename = $sourceFilename . '_watermarked_' . time() . '.' . $sourceExt;
$targetPath = Config::PROCESSED_PATH . $targetFilename;

$watermarkManager = new WatermarkManager();
$success = false;

if ($type === 'text') {
    $text = $_POST['text'] ?? '';
    if (empty($text)) {
        Response::error('Missing required parameter: text.', 400);
    }

    $options = [
        'position' => $_POST['position'] ?? 'bottom-right',
        'size'     => isset($_POST['size']) ? (int)$_POST['size'] : 30,
        'color'    => $_POST['color'] ?? '#ffffff',
        'opacity'  => isset($_POST['opacity']) ? (float)$_POST['opacity'] : 0.5,
        'rotation' => isset($_POST['rotation']) ? (int)$_POST['rotation'] : 0,
        'offset_x' => isset($_POST['offset_x']) ? (int)$_POST['offset_x'] : 20,
        'offset_y' => isset($_POST['offset_y']) ? (int)$_POST['offset_y'] : 20
    ];

    $success = $watermarkManager->addTextWatermark($sourcePath, $targetPath, $text, $options);
    $operation = 'Text Watermark';

} elseif ($type === 'logo') {
    $logoFile = $_POST['logo_file'] ?? '';
    $logoPath = '';

    // Check if a file is uploaded in the request
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $fileManager = new FileManager();
        $uploadResult = $fileManager->upload($_FILES['logo'], Config::UPLOAD_PATH);
        if ($uploadResult) {
            $logoPath = $uploadResult['file_path'];
        } else {
            Response::error('Failed to upload logo file.', 400);
        }
    } elseif (!empty($logoFile)) {
        // Use an already uploaded logo file name
        $logoPath = Config::UPLOAD_PATH . $logoFile;
        if (!file_exists($logoPath) || !Validator::isPathSafe($logoPath, Config::UPLOAD_PATH)) {
            Response::error('Logo file not found or access denied.', 404);
        }
    } else {
        Response::error('Missing required parameter: logo or logo_file.', 400);
    }

    $options = [
        'position' => $_POST['position'] ?? 'bottom-right',
        'opacity'  => isset($_POST['opacity']) ? (float)$_POST['opacity'] : 0.8,
        'scale'    => isset($_POST['scale']) ? (int)$_POST['scale'] : 20,
        'offset_x' => isset($_POST['offset_x']) ? (int)$_POST['offset_x'] : 20,
        'offset_y' => isset($_POST['offset_y']) ? (int)$_POST['offset_y'] : 20
    ];

    $success = $watermarkManager->addLogoWatermark($sourcePath, $targetPath, $logoPath, $options);
    $operation = 'Logo Watermark';

    // If the logo was uploaded dynamically in this request, clean it up after applying watermark
    if (isset($_FILES['logo']) && !empty($logoPath) && file_exists($logoPath)) {
        @unlink($logoPath);
    }
} else {
    Response::error('Invalid watermark type. Must be text or logo.', 400);
}

if (!$success || !file_exists($targetPath)) {
    Response::error('Failed to apply watermark.', 500);
}

$originalSize = filesize($sourcePath);
$newSize = filesize($targetPath);

// Save to history log
$history = new HistoryManager();
$history->addHistory(
    $filename,
    $targetFilename,
    $operation,
    $originalSize,
    $newSize
);

// Determine dimensions
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
