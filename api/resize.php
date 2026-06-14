<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/ImageService.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get input parameters
$filename = $_POST['filename'] ?? '';
$width = isset($_POST['width']) ? (int)$_POST['width'] : 0;
$height = isset($_POST['height']) ? (int)$_POST['height'] : 0;
$maintainRatio = isset($_POST['maintainRatio']) ? (bool)(int)$_POST['maintainRatio'] : true;
$presetKey = $_POST['preset'] ?? '';

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

// Apply preset dimension override if specified
$presetMappings = [
    'instagram_post' => ['w' => 1080, 'h' => 1080, 'ratio' => false],
    'instagram_story' => ['w' => 1080, 'h' => 1920, 'ratio' => false],
    'facebook_post' => ['w' => 1200, 'h' => 630, 'ratio' => false],
    'youtube_thumbnail' => ['w' => 1280, 'h' => 720, 'ratio' => false],
    'tiktok_cover' => ['w' => 1080, 'h' => 1920, 'ratio' => false],
    'thumbnail_150' => ['w' => 150, 'h' => 150, 'ratio' => true],
    'medium_500' => ['w' => 500, 'h' => 500, 'ratio' => true],
    'large_1200' => ['w' => 1200, 'h' => 1200, 'ratio' => true]
];

if (!empty($presetKey) && array_key_exists($presetKey, $presetMappings)) {
    $width = $presetMappings[$presetKey]['w'];
    $height = $presetMappings[$presetKey]['h'];
    $maintainRatio = $presetMappings[$presetKey]['ratio'];
}

if ($width <= 0 && $height <= 0) {
    Response::error('Invalid dimensions. Width or height must be greater than 0.', 400);
}

// Build paths
$sourcePath = Config::UPLOAD_PATH . $filename;

// Security check: Verify file exists and stay within bounds (no traversal)
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

// Run resizing engine
$imageService = new ImageService();
$resizeResult = $imageService->resizeImage($sourcePath, $width, $height, $maintainRatio);

if (!$resizeResult) {
    Response::error('Resize operation failed. Ensure ImageMagick is installed.', 500);
}

// Log execution to DB history
$originalSize = filesize($sourcePath);
$history = new HistoryManager();
$history->addHistory(
    $filename,
    $resizeResult['filename'],
    'Resize',
    $originalSize,
    $resizeResult['file_size']
);

// Return JSON payload response
Response::json([
    'success' => true,
    'filename' => $resizeResult['filename'],
    'original_size' => $originalSize,
    'new_size' => $resizeResult['file_size'],
    'width' => $resizeResult['width'],
    'height' => $resizeResult['height'],
    'extension' => $resizeResult['extension']
]);
