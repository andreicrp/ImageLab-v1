<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/FileManager.php';
require_once __DIR__ . '/../core/QueueManager.php';

// Route action check (process_next, status, clear_completed, or upload batch)
$action = $_GET['action'] ?? 'upload';

$queueManager = new QueueManager();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'process_next') {
    // Process next item in the queue
    $result = $queueManager->processQueue();
    if ($result === false) {
        Response::success([], 'Queue is empty. No pending jobs found.');
    }
    Response::success($result, 'Job processed successfully.');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'status') {
    // Get full queue list
    $jobs = $queueManager->getQueueStatus();
    Response::success($jobs, 'Queue status fetched.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'clear_completed') {
    // Clear completed or failed jobs from queue to keep it tidy
    $jobs = $queueManager->getQueueStatus();
    $clearedCount = 0;
    foreach ($jobs as $job) {
        if ($job['status'] === 'completed' || $job['status'] === 'failed') {
            if ($queueManager->removeFromQueue((int)$job['id'])) {
                $clearedCount++;
            }
        }
    }
    Response::success(['cleared_count' => $clearedCount], 'Completed jobs cleared.');
}

// Default: Handle batch file uploads and enqueue them
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $action !== 'upload') {
    Response::error('Invalid request route or method.', 405);
}

// Verify images parameter exists and is array structured
if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'])) {
    Response::error('No images uploaded in batch.', 400);
}

$files = $_FILES['images'];
$fileCount = count($files['name']);

// Enforce limit: Max 20 files
if ($fileCount > Config::MAX_BATCH_FILES) {
    Response::error(sprintf('Batch upload limit exceeded. Max %d files allowed.', Config::MAX_BATCH_FILES), 400);
}

// Calculate total size and pre-validate elements
$totalSize = 0;
$allowedUploadFormats = [
    'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'tif', 'ico',
    'heic', 'heif', 'avif', 'raw', 'cr2', 'cr3', 'nef', 'arw', 'dng', 'orf',
    'raf', 'psd', 'ai', 'eps', 'pdf', 'tga', 'exr', 'hdr', 'jfif'
];
$validatedFiles = [];

for ($i = 0; $i < $fileCount; $i++) {
    $fileItem = [
        'name' => $files['name'][$i],
        'type' => $files['type'][$i],
        'tmp_name' => $files['tmp_name'][$i],
        'error' => $files['error'][$i],
        'size' => $files['size'][$i]
    ];

    $totalSize += $fileItem['size'];

    // 1. Structure validation
    if (!Validator::validateFile($fileItem)) {
        Response::error(sprintf('File "%s" is corrupted or invalid.', $fileItem['name']), 400);
    }

    // 2. Extension validation
    if (!Validator::validateFormat($fileItem['name'], $allowedUploadFormats)) {
        Response::error(sprintf('File "%s" has an unsupported format. Use JPG, PNG, WEBP, or GIF.', $fileItem['name']), 400);
    }

    // 3. MIME type validation
    if (!Validator::validateMimeType($fileItem['tmp_name'], $fileItem['name'])) {
        Response::error(sprintf('Security check failed on file "%s". Extension does not match content.', $fileItem['name']), 400);
    }

    $validatedFiles[] = $fileItem;
}

// Enforce limit: Max 100MB total batch size
if ($totalSize > Config::MAX_BATCH_SIZE) {
    Response::error(sprintf('Total batch size exceeds the %d MB limit.', Config::MAX_BATCH_SIZE / (1024 * 1024)), 400);
}

// Read batch operation settings (Convert, Resize, Compress, or Preset)
$operationSettings = $_POST['operation'] ?? '';
if (empty($operationSettings)) {
    // Default operation: Convert to Webp
    $operationSettings = json_encode(['action' => 'convert', 'format' => 'webp']);
}

// Upload files and add them to the processing queue
$fileManager = new FileManager();
$enqueuedJobs = [];

foreach ($validatedFiles as $file) {
    $uploadResult = $fileManager->upload($file, Config::UPLOAD_PATH);
    if ($uploadResult) {
        // Enqueue job in MySQL
        $jobId = $queueManager->addToQueue($uploadResult['filename'], $operationSettings);
        $enqueuedJobs[] = [
            'job_id' => $jobId,
            'original_name' => $uploadResult['original_name'],
            'filename' => $uploadResult['filename'],
            'size' => $uploadResult['file_size'],
            'status' => 'waiting'
        ];
    }
}

Response::success([
    'enqueued_count' => count($enqueuedJobs),
    'jobs' => $enqueuedJobs
], 'Batch images uploaded and enqueued successfully.');
