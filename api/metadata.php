<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/MetadataManager.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow POST requests for actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Invalid request method.', 405);
}

// Get parameters
$filename = $_REQUEST['filename'] ?? '';
$action = $_REQUEST['action'] ?? 'read'; // 'read' or 'strip'

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

$metadataManager = new MetadataManager();

if ($action === 'read') {
    $result = $metadataManager->readMetadata($sourcePath);
    Response::json($result);

} elseif ($action === 'strip') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('POST method required for stripping metadata.', 405);
    }

    $sourceFilename = pathinfo($filename, PATHINFO_FILENAME);
    $sourceExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $targetFilename = $sourceFilename . '_stripped_' . time() . '.' . $sourceExt;
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    if (!$metadataManager->stripMetadata($sourcePath, $targetPath)) {
        Response::error('Failed to strip metadata.', 500);
    }

    $originalSize = filesize($sourcePath);
    $newSize = filesize($targetPath);

    // Save to history log
    $history = new HistoryManager();
    $history->addHistory(
        $filename,
        $targetFilename,
        'Strip Metadata',
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
} else {
    Response::error('Invalid action. Must be read or strip.', 400);
}
