<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Response.php';

// Allow only GET requests for downloads
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Invalid request method. GET required.', 405);
}

// Retrieve parameters
$filename = $_GET['file'] ?? '';
$type = $_GET['type'] ?? 'processed';
$customName = $_GET['name'] ?? '';

if (empty($filename)) {
    Response::error('Missing required parameter: file.', 400);
}

// Map directory based on type parameter
$baseDir = Config::PROCESSED_PATH;
if ($type === 'uploads') {
    $baseDir = Config::UPLOAD_PATH;
}

$filePath = $baseDir . $filename;

// Alternate folder fallback if not found under requested directory type
if (!file_exists($filePath)) {
    $altDir = ($type === 'uploads') ? Config::PROCESSED_PATH : Config::UPLOAD_PATH;
    $altPath = $altDir . $filename;
    if (file_exists($altPath) && Validator::isPathSafe($altPath, $altDir)) {
        $baseDir = $altDir;
        $filePath = $altPath;
    }
}

// Security check: Verify file exists and stay within target directory (prevent traversal)
if (!file_exists($filePath) || !Validator::isPathSafe($filePath, $baseDir)) {
    Response::error('File not found or access denied.', 404);
}

// Set MIME mapping for safety
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeType = 'application/octet-stream';

switch ($extension) {
    case 'jpg':
    case 'jpeg':
        $mimeType = 'image/jpeg';
        break;
    case 'png':
        $mimeType = 'image/png';
        break;
    case 'webp':
        $mimeType = 'image/webp';
        break;
    case 'gif':
        $mimeType = 'image/gif';
        break;
}

// Clear buffers to avoid corrupted files
if (ob_get_level()) {
    ob_end_clean();
}

// Set Headers to force file download directly
$downloadName = basename($filePath);
if (!empty($customName)) {
    // Sanitize to alphanumeric, dots, dashes, underscores
    $customName = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $customName);
    if (!empty($customName)) {
        $processedExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $customBase = pathinfo($customName, PATHINFO_FILENAME);
        $downloadName = $customBase . '.' . $processedExt;
    }
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Output file stream
readfile($filePath);
exit;
