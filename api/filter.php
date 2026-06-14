<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/FilterManager.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get parameters
$filename = $_POST['filename'] ?? '';
$filterName = $_POST['filter'] ?? '';

if (empty($filename) || empty($filterName)) {
    Response::error('Missing required parameters: filename and filter.', 400);
}

$sourcePath = Config::UPLOAD_PATH . $filename;
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    // Check if file is in processed folder
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

// Generate new target filename
$sourceFilename = pathinfo($filename, PATHINFO_FILENAME);
$sourceExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$targetFilename = $sourceFilename . '_filter_' . $filterName . '_' . time() . '.' . $sourceExt;
$targetPath = Config::PROCESSED_PATH . $targetFilename;

$manager = new FilterManager();
if (!$manager->applyFilter($sourcePath, $targetPath, $filterName)) {
    Response::error('Failed to apply filter.', 500);
}

$originalSize = filesize($sourcePath);
$newSize = filesize($targetPath);

// Save to history log
$history = new HistoryManager();
$history->addHistory(
    $filename,
    $targetFilename,
    'Filter: ' . ucfirst($filterName),
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
