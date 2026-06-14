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
$qualityParam = $_POST['quality'] ?? '';

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

// Convert string levels to numeric quality values
$quality = 80; // Default
if (is_numeric($qualityParam)) {
    $quality = (int)$qualityParam;
} else {
    switch (strtolower($qualityParam)) {
        case 'low':
            $quality = 85; // Low compression = high quality
            break;
        case 'medium':
            $quality = 65; // Medium compression
            break;
        case 'high':
            $quality = 45; // High compression
            break;
        case 'max':
        case 'maximum':
            $quality = 25; // Maximum compression = low quality
            break;
    }
}

// Build paths
$sourcePath = Config::UPLOAD_PATH . $filename;

// Security check: Verify file exists and path is safe
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    Response::error('Source file not found or access denied.', 404);
}

// Run compression engine
$imageService = new ImageService();
$compressResult = $imageService->compressImage($sourcePath, $quality);

if (!$compressResult) {
    Response::error('Compression operation failed. Check ImageMagick configurations.', 500);
}

// Log execution to DB history
$originalSize = filesize($sourcePath);
$history = new HistoryManager();
$history->addHistory(
    $filename,
    $compressResult['filename'],
    'Compress',
    $originalSize,
    $compressResult['file_size']
);

// Return JSON metrics comparing space details
Response::json([
    'success' => true,
    'filename' => $compressResult['filename'],
    'original_size' => $originalSize,
    'new_size' => $compressResult['file_size'],
    'saved_percent' => Math_round_pct($originalSize, $compressResult['file_size']),
    'width' => $compressResult['width'],
    'height' => $compressResult['height'],
    'extension' => $compressResult['extension']
]);

/**
 * Calculates size savings percentage
 */
function Math_round_pct(int $before, int $after): float {
    if ($before <= 0) return 0.0;
    $saved = $before - $after;
    return round(($saved / $before) * 100, 1);
}
