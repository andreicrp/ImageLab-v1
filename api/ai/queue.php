<?php
require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AIQueueManager.php';

// Allow GET/POST requests
$action = $_REQUEST['action'] ?? 'status';

$queue = new AIQueueManager();

function enrichJobData($job) {
    if ($job && $job['status'] === 'completed' && !empty($job['result_path'])) {
        $filePath = Config::PROCESSED_PATH . $job['result_path'];
        if (file_exists($filePath)) {
            $job['new_size'] = filesize($filePath);
            
            $sourcePath = Config::UPLOAD_PATH . $job['image_path'];
            if (!file_exists($sourcePath)) {
                $sourcePath = Config::PROCESSED_PATH . $job['image_path'];
            }
            $job['original_size'] = file_exists($sourcePath) ? filesize($sourcePath) : $job['new_size'];
            
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo !== false) {
                $job['width'] = $imageInfo[0];
                $job['height'] = $imageInfo[1];
            } else {
                $job['width'] = 0;
                $job['height'] = 0;
            }
            $job['extension'] = strtolower(pathinfo($job['result_path'], PATHINFO_EXTENSION));
        }
    }
    return $job;
}

if ($action === 'status') {
    $jobId = isset($_REQUEST['job_id']) ? (int)$_REQUEST['job_id'] : 0;
    
    if ($jobId > 0) {
        $job = $queue->getJobStatus($jobId);
        if ($job) {
            $job = enrichJobData($job);
            Response::json([
                'success' => true,
                'data' => $job
            ]);
        } else {
            Response::error('Job not found.', 404);
        }
    } else {
        // List all pending jobs
        $pending = $queue->getPendingJobs();
        Response::json([
            'success' => true,
            'data' => $pending
        ]);
    }
} elseif ($action === 'process_next') {
    // This executes the next job in the background queue
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('POST required for executing queue jobs.', 405);
    }
    
    $processedJob = $queue->processNextJob();
    if ($processedJob) {
        $processedJob = enrichJobData($processedJob);
        Response::json([
            'success' => true,
            'data' => $processedJob,
            'message' => 'Processed queue job #' . $processedJob['id']
        ]);
    } else {
        Response::json([
            'success' => true,
            'data' => null,
            'message' => 'No queued jobs in queue.'
        ]);
    }
} elseif ($action === 'cancel_all') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('POST required to cancel queue.', 405);
    }
    $success = $queue->cancelAllJobs();
    Response::json([
        'success' => $success,
        'message' => $success ? 'All pending and processing queue jobs have been cancelled.' : 'Failed to cancel queue jobs.'
    ]);
} elseif ($action === 'clear_all') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        Response::error('POST required to clear queue history.', 405);
    }
    $success = $queue->clearAllJobs();
    Response::json([
        'success' => $success,
        'message' => $success ? 'AI queue history has been cleared.' : 'Failed to clear queue history.'
    ]);
} else {
    Response::error('Invalid queue action.', 400);
}
