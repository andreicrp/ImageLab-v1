<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/AIService.php';

/**
 * AIQueueManager Class
 * Manages enqueuing, background execution, and tracking of heavy AI model tasks
 */
class AIQueueManager {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Enqueue a new AI job
     * 
     * @param string $imagePath Rel or absolute file path
     * @param string $operation AI operation name (upscale_2x, upscale_4x, face_enhance, remove_background, auto_enhance)
     * @return int|bool Job ID on success, false on failure
     */
    public function enqueue(string $imagePath, string $operation): int|bool {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                INSERT INTO ai_jobs (image_path, operation, status) 
                VALUES (?, ?, 'queued')
            ");
            if ($stmt->execute([$imagePath, $operation])) {
                return (int)$this->db->lastInsertId();
            }
        } catch (PDOException $e) {
            @file_put_contents(Config::LOG_PATH . 'database_error.log', date('[Y-m-d H:i:s] ') . "Enqueue AI job failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        return false;
    }

    /**
     * Fetch the next queued job, transition it to processing, execute it, and record the outcome
     * 
     * @return array|bool Job details on success, false if no jobs or error
     */
    public function processNextJob(): array|bool {
        if (!$this->db) {
            return false;
        }

        try {
            // Transaction to prevent race conditions in concurrent polling
            $this->db->beginTransaction();

            $stmt = $this->db->query("
                SELECT * FROM ai_jobs 
                WHERE status = 'queued' 
                ORDER BY id ASC LIMIT 1 FOR UPDATE
            ");
            $job = $stmt->fetch();

            if (!$job) {
                $this->db->rollBack();
                return false;
            }

            $jobId = $job['id'];

            // Update status to processing
            $update = $this->db->prepare("UPDATE ai_jobs SET status = 'processing' WHERE id = ?");
            $update->execute([$jobId]);
            $this->db->commit();

            // Perform processing
            $aiService = new AIService();
            $sourceFilename = $job['image_path'];
            $operation = $job['operation'];
            
            $sourcePath = Config::UPLOAD_PATH . $sourceFilename;
            if (!file_exists($sourcePath)) {
                $sourcePath = Config::PROCESSED_PATH . $sourceFilename;
            }

            // Target path naming
            $sourceBase = pathinfo($sourceFilename, PATHINFO_FILENAME);
            $sourceExt = strtolower(pathinfo($sourceFilename, PATHINFO_EXTENSION));
            
            $success = false;
            $errorMsg = null;
            $resultFilename = '';

            if ($operation === 'upscale_2x' || $operation === 'upscale_4x') {
                $scale = ($operation === 'upscale_4x') ? 4 : 2;
                $resultFilename = $sourceBase . "_upscaled_{$scale}x_" . time() . '.' . $sourceExt;
                $targetPath = Config::PROCESSED_PATH . $resultFilename;
                
                $success = $aiService->upscale($sourcePath, $targetPath, $scale);
            } elseif ($operation === 'face_enhance') {
                $resultFilename = $sourceBase . "_face_enhanced_" . time() . '.' . $sourceExt;
                $targetPath = Config::PROCESSED_PATH . $resultFilename;
                
                $success = $aiService->faceEnhance($sourcePath, $targetPath);
            } elseif ($operation === 'remove_background') {
                // Must be transparent PNG
                $resultFilename = $sourceBase . "_no_bg_" . time() . '.png';
                $targetPath = Config::PROCESSED_PATH . $resultFilename;
                
                $success = $aiService->removeBackground($sourcePath, $targetPath);
            } elseif ($operation === 'auto_enhance') {
                $resultFilename = $sourceBase . "_ai_enhanced_" . time() . '.' . $sourceExt;
                $targetPath = Config::PROCESSED_PATH . $resultFilename;
                
                $success = $aiService->autoEnhance($sourcePath, $targetPath);
            } else {
                $errorMsg = "Unsupported AI operation: " . $operation;
            }

            if ($success && file_exists($targetPath)) {
                // Set as completed
                $stmt = $this->db->prepare("
                    UPDATE ai_jobs 
                    SET status = 'completed', result_path = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$resultFilename, $jobId]);
                
                $job['status'] = 'completed';
                $job['result_path'] = $resultFilename;
            } else {
                $err = $errorMsg ?: "Python microservice execution failed or returned invalid response.";
                $stmt = $this->db->prepare("
                    UPDATE ai_jobs 
                    SET status = 'failed', error_message = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$err, $jobId]);
                
                $job['status'] = 'failed';
                $job['error_message'] = $err;
            }

            return $job;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            @file_put_contents(Config::LOG_PATH . 'database_error.log', date('[Y-m-d H:i:s] ') . "Process AI job failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        return false;
    }

    /**
     * Retrieve status of a specific job
     */
    public function getJobStatus(int $jobId): array|bool {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM ai_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retrieve active pending jobs list
     */
    public function getPendingJobs(): array {
        if (!$this->db) {
            return [];
        }

        try {
            $stmt = $this->db->query("
                SELECT * FROM ai_jobs 
                WHERE status IN ('queued', 'processing') 
                ORDER BY id ASC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Cancel all currently queued or processing jobs
     */
    public function cancelAllJobs(): bool {
        if (!$this->db) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("
                UPDATE ai_jobs 
                SET status = 'failed', error_message = 'Cancelled by user' 
                WHERE status IN ('queued', 'processing')
            ");
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Clear all jobs from the queue history
     */
    public function clearAllJobs(): bool {
        if (!$this->db) {
            return false;
        }
        try {
            $stmt = $this->db->prepare("DELETE FROM ai_jobs");
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
