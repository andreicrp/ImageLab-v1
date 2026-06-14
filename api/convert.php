<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/ImageService.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get input parameters
$filename = $_POST['filename'] ?? '';
$targetFormat = strtolower($_POST['format'] ?? '');

if (empty($filename) || empty($targetFormat)) {
    Response::error('Missing parameters: filename and format are required.', 400);
}

// Build source path
$sourcePath = Config::UPLOAD_PATH . $filename;

// Security check: Verify file exists and path is safe against directory traversal
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    Response::error('Source file not found or path is invalid.', 404);
}

// Validate target format
$allowedOutputs = [
    'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'tiff', 'tif', 'ico',
    'heic', 'heif', 'avif', 'psd', 'pdf', 'eps', 'tga', 'exr', 'hdr', 'jfif'
];
if (!in_array($targetFormat, $allowedOutputs, true)) {
    Response::error('Unsupported output format.', 400);
}

// Run conversion engine
$imageService = new ImageService();
$conversionResult = $imageService->convertImage($sourcePath, $targetFormat);

if (!$conversionResult) {
    Response::error('Image conversion failed. Ensure ImageMagick is installed and running.', 500);
}

// Get original size for comparison metric
$originalSize = filesize($sourcePath);

// Return JSON response including all metric details for the UI comparison
Response::json([
    'success' => true,
    'filename' => $conversionResult['filename'],
    'original_size' => $originalSize,
    'new_size' => $conversionResult['file_size'],
    'original_format' => $conversionResult['original_format'],
    'converted_format' => $conversionResult['converted_format'],
    'width' => $conversionResult['width'],
    'height' => $conversionResult['height']
]);
