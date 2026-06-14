<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/ImageService.php';
require_once __DIR__ . '/HistoryManager.php';

/**
 * QueueManager Class
 * Oversees the processing queue database table and fallback states
 */
class QueueManager {
    private ?PDO $db;
    private string $fallbackFile;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->fallbackFile = Config::LOG_PATH . 'fallback_queue.json';
    }

    /**
     * Register a new task into the processing queue
     * 
     * @param string $filename Unique name of source file
     * @param string $operation String representation of task settings (json)
     * @return int Registered job ID
     */
    public function addToQueue(string $filename, string $operation): int {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO queue_jobs (filename, operation, status) 
                    VALUES (?, ?, 'waiting')
                ");
                $stmt->execute([$filename, $operation]);
                return (int)$this->db->lastInsertId();
            } catch (PDOException $e) {
                @file_put_contents(Config::LOG_PATH . 'database_error.log', date('[Y-m-d H:i:s] ') . "Queue insert failed: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }

        return $this->addJsonFallback($filename, $operation);
    }

    /**
     * Retrieve queue statuses
     * 
     * @return array List of current jobs
     */
    public function getQueueStatus(): array {
        if ($this->db) {
            try {
                $stmt = $this->db->query("SELECT * FROM queue_jobs ORDER BY id ASC LIMIT 50");
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                // fall back
            }
        }
        return $this->getJsonFallback();
    }

    /**
     * Delete a job from the queue
     * 
     * @param int $jobId
     * @return bool
     */
    public function removeFromQueue(int $jobId): bool {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("DELETE FROM queue_jobs WHERE id = ?");
                return $stmt->execute([$jobId]);
            } catch (PDOException $e) {
                // fall back
            }
        }

        $jobs = $this->getJsonFallback();
        $filtered = array_filter($jobs, fn($job) => (int)$job['id'] !== $jobId);
        return $this->writeJsonFallback(array_values($filtered));
    }

    /**
     * Process next pending item in the queue (Sequential batch processor)
     * 
     * @return array|bool Log info of completed job or false if no jobs are in queue
     */
    public function processQueue(): array|bool {
        $jobs = $this->getQueueStatus();
        $nextJob = null;

        // Find first 'waiting' job
        foreach ($jobs as $job) {
            if ($job['status'] === 'waiting') {
                $nextJob = $job;
                break;
            }
        }

        if (!$nextJob) {
            return false; // No jobs waiting
        }

        $jobId = (int)$nextJob['id'];
        
        // Update state to 'processing'
        $this->updateJobStatus($jobId, 'processing');

        // Parse operation parameters
        $options = json_decode($nextJob['operation'], true);
        if (!is_array($options)) {
            $this->updateJobStatus($jobId, 'failed');
            return false;
        }

        $sourcePath = Config::UPLOAD_PATH . $nextJob['filename'];
        if (!file_exists($sourcePath)) {
            $this->updateJobStatus($jobId, 'failed');
            return false;
        }

        $originalSize = filesize($sourcePath);
        $imageService = new ImageService();
        $outputFile = null;
        $success = false;

        try {
            // Check operation tasks
            $action = $options['action'] ?? 'convert';
            
            if ($action === 'resize') {
                // Run Resize
                $w = (int)($options['width'] ?? 0);
                $h = (int)($options['height'] ?? 0);
                $ratio = (bool)($options['maintainRatio'] ?? true);
                
                $result = $imageService->resizeImage($sourcePath, $w, $h, $ratio);
                if ($result) {
                    $success = true;
                    $outputFile = $result['file_path'];
                }

            } elseif ($action === 'compress') {
                // Run Compress
                $quality = (int)($options['quality'] ?? 80);
                
                $result = $imageService->compressImage($sourcePath, $quality);
                if ($result) {
                    $success = true;
                    $outputFile = $result['file_path'];
                }

            } else {
                // Default: Convert
                $fmt = strtolower($options['format'] ?? 'webp');
                $result = $imageService->convertImage($sourcePath, $fmt);
                if ($result) {
                    $success = true;
                    $outputFile = $result['file_path'];
                }
            }

            if ($success && file_exists($outputFile)) {
                $newSize = filesize($outputFile);
                
                // Log history log database record
                $history = new HistoryManager();
                $history->addHistory(
                    $nextJob['filename'],
                    basename($outputFile),
                    ucfirst($action),
                    $originalSize,
                    $newSize
                );

                $this->updateJobStatus($jobId, 'completed');
                return [
                    'job_id' => $jobId,
                    'filename' => basename($outputFile),
                    'action' => $action,
                    'size_before' => $originalSize,
                    'size_after' => $newSize,
                    'status' => 'completed'
                ];
            } else {
                $this->updateJobStatus($jobId, 'failed');
            }
        } catch (Exception $e) {
            $this->updateJobStatus($jobId, 'failed');
        }

        return [
            'job_id' => $jobId,
            'status' => 'failed'
        ];
    }

    /**
     * Update current state of a registered job
     */
    private function updateJobStatus(int $jobId, string $status): void {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("UPDATE queue_jobs SET status = ? WHERE id = ?");
                $stmt->execute([$status, $jobId]);
                return;
            } catch (PDOException $e) {
                // fall back
            }
        }

        $jobs = $this->getJsonFallback();
        foreach ($jobs as &$job) {
            if ((int)$job['id'] === $jobId) {
                $job['status'] = $status;
                $job['updated_at'] = date('Y-m-d H:i:s');
                break;
            }
        }
        $this->writeJsonFallback($jobs);
    }

    /**
     * Write helper for fallback JSON
     */
    private function writeJsonFallback(array $jobs): bool {
        return (bool)@file_put_contents($this->fallbackFile, json_encode($jobs, JSON_PRETTY_PRINT));
    }

    /**
     * Fetch helper for fallback JSON
     */
    private function getJsonFallback(): array {
        if (!file_exists($this->fallbackFile)) {
            return [];
        }
        $data = @file_get_contents($this->fallbackFile);
        $jobs = json_decode($data, true);
        return is_array($jobs) ? $jobs : [];
    }

    /**
     * Add log when MySQL is disconnected
     */
    private function addJsonFallback(string $filename, string $operation): int {
        $jobs = $this->getJsonFallback();
        
        $newId = 1;
        if (count($jobs) > 0) {
            $newId = (int)max(array_column($jobs, 'id')) + 1;
        }

        $newJob = [
            'id' => $newId,
            'filename' => $filename,
            'operation' => $operation,
            'status' => 'waiting',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $jobs[] = $newJob;
        $this->writeJsonFallback($jobs);
        return $newId;
    }
}
