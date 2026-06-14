<?php
require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/AIService.php';
require_once __DIR__ . '/../../core/AIQueueManager.php';
require_once __DIR__ . '/../../core/HistoryManager.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

$filename = $_POST['filename'] ?? '';
$scale = isset($_POST['scale']) ? (int)$_POST['scale'] : 2;
$mode = $_POST['mode'] ?? 'sync'; // 'sync' or 'async'

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

if ($scale !== 2 && $scale !== 4) {
    Response::error('Scale factor must be 2 or 4.', 400);
}

// Build paths
$sourcePath = Config::UPLOAD_PATH . $filename;
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

$operation = "upscale_{$scale}x";

if ($mode === 'async') {
    $queue = new AIQueueManager();
    $jobId = $queue->enqueue(basename($sourcePath), $operation);
    
    if ($jobId) {
        Response::json([
            'success' => true,
            'job_id' => $jobId,
            'status' => 'queued',
            'message' => 'Upscaling task enqueued successfully.'
        ]);
    } else {
        Response::error('Failed to enqueue upscaling task.', 500);
    }
} else {
    // Sync mode
    $sourceFilename = basename($sourcePath);
    $sourceBase = pathinfo($sourceFilename, PATHINFO_FILENAME);
    $sourceExt = strtolower(pathinfo($sourceFilename, PATHINFO_EXTENSION));
    $targetFilename = $sourceBase . "_upscaled_{$scale}x_" . time() . '.' . $sourceExt;
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    $aiService = new AIService();
    if (!$aiService->upscale($sourcePath, $targetPath, $scale)) {
        Response::error('AI upscaling failed. Make sure AI microservice is online.', 503);
    }

    $originalSize = filesize($sourcePath);
    $newSize = filesize($targetPath);

    // Save to history logs
    $history = new HistoryManager();
    $history->addHistory(
        $sourceFilename,
        $targetFilename,
        "AI Upscale {$scale}x",
        $originalSize,
        $newSize
    );

    // Get output resolution
    $width = 0; $height = 0;
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
}
