<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/FileManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Ensure file parameter exists
if (!isset($_FILES['image'])) {
    Response::error('No image file uploaded.', 400);
}

$file = $_FILES['image'];

// 1. Structure validation
if (!Validator::validateFile($file)) {
    Response::error('Uploaded file corrupted or invalid.', 400);
}

// 2. Extension validation (lowercase check)
$originalName = $file['name'];
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$allowedUploadFormats = [
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif', 'ico',
    'heic', 'heif', 'avif', 'raw', 'cr2', 'cr3', 'nef', 'arw', 'dng', 'orf',
    'raf', 'psd', 'ai', 'eps', 'pdf', 'tga', 'exr', 'hdr', 'jfif'
];

if (!Validator::validateFormat($originalName, $allowedUploadFormats)) {
    Response::error('Invalid file extension. Format is not supported.', 400);
}

// 3. File size validation (10 MB maximum)
if (!Validator::validateSize($file['size'], Config::MAX_FILE_SIZE)) {
    Response::error('File size exceeds the 10 MB limit.', 400);
}

// 4. Secure MIME type validation (prevents executable/injection uploads renamed as images)
if (!Validator::validateMimeType($file['tmp_name'], $originalName)) {
    Response::error('Security check failed: File content does not match the image extension.', 400);
}

// 5. Run secure upload handler
$fileManager = new FileManager();
$uploadResult = $fileManager->upload($file, Config::UPLOAD_PATH);

if (!$uploadResult) {
    Response::error('Failed to save uploaded image. Check write permissions.', 500);
}

// Return exact response requirements, adding dimensions/size for frontend previews
Response::json([
    'success' => true,
    'filename' => $uploadResult['filename'],
    'original_name' => $uploadResult['original_name'],
    'size' => $uploadResult['file_size'],
    'width' => $uploadResult['width'],
    'height' => $uploadResult['height'],
    'extension' => $uploadResult['extension']
]);
