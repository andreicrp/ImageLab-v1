<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';

/**
 * HistoryManager Class
 * Manages database logs for conversion history and gathers analytics metrics
 */
class HistoryManager {
    private ?PDO $db;
    private string $fallbackFile;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->fallbackFile = Config::LOG_PATH . 'fallback_history.json';
    }

    /**
     * Add log record of an image operation
     * 
     * @return bool
     */
    public function addHistory(string $original, string $processed, string $operation, int $sizeBefore, int $sizeAfter): bool {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO conversion_history 
                    (original_filename, processed_filename, operation, file_size_before, file_size_after) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                return $stmt->execute([$original, $processed, $operation, $sizeBefore, $sizeAfter]);
            } catch (PDOException $e) {
                // If query fails, write to log and fall back to json
                @file_put_contents(Config::LOG_PATH . 'database_error.log', date('[Y-m-d H:i:s] ') . "Insert History failed: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
        
        return $this->addJsonFallback($original, $processed, $operation, $sizeBefore, $sizeAfter);
    }

    /**
     * Fetch logs list
     * 
     * @param int $limit Max logs to retrieve
     * @return array
     */
    public function getHistory(int $limit = 10): array {
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM conversion_history ORDER BY id DESC LIMIT ?");
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
                $stmt->execute();
                return $stmt->fetchAll();
            } catch (PDOException $e) {
                // Log and fall back
            }
        }
        return $this->getJsonFallback($limit);
    }

    /**
     * Get aggregate statistics for dashboard metric displays
     * 
     * @return array
     */
    public function getAnalytics(): array {
        $stats = [
            'total_uploads'     => 0,
            'total_conversions' => 0,
            'storage_saved'     => 0, // In bytes
            'files_processed'   => 0,
            'db_connected'      => ($this->db !== null)
        ];

        if ($this->db) {
            try {
                // Query total records and space metrics
                $query = $this->db->query("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN operation = 'Upload & Validate' THEN 0 ELSE 1 END) as conversions,
                        SUM(CASE WHEN file_size_before > file_size_after THEN (file_size_before - file_size_after) ELSE 0 END) as saved
                    FROM conversion_history
                ");
                
                if ($query) {
                    $row = $query->fetch();
                    $stats['total_uploads'] = (int)$row['total'];
                    $stats['total_conversions'] = (int)$row['conversions'];
                    $stats['storage_saved'] = (int)$row['saved'];
                    $stats['files_processed'] = (int)$row['total'];
                    return $stats;
                }
            } catch (PDOException $e) {
                // Log and run fallback
            }
        }

        // Run fallback count calculations if db is unavailable
        $fallbackLogs = $this->getJsonFallback(1000);
        $stats['total_uploads'] = count($fallbackLogs);
        
        $conversions = 0;
        $saved = 0;
        foreach ($fallbackLogs as $log) {
            if ($log['operation'] !== 'Upload & Validate') {
                $conversions++;
            }
            $diff = $log['file_size_before'] - $log['file_size_after'];
            if ($diff > 0) {
                $saved += $diff;
            }
        }
        $stats['total_conversions'] = $conversions;
        $stats['storage_saved'] = $saved;
        $stats['files_processed'] = count($fallbackLogs);

        return $stats;
    }

    /**
     * Write to log file when database is offline
     */
    private function addJsonFallback(string $original, string $processed, string $operation, int $sizeBefore, int $sizeAfter): bool {
        $logs = $this->getJsonFallback(1000);
        
        $newLog = [
            'id' => count($logs) + 1,
            'original_filename' => $original,
            'processed_filename' => $processed,
            'operation' => $operation,
            'file_size_before' => $sizeBefore,
            'file_size_after' => $sizeAfter,
            'created_at' => date('Y-m-d H:i:s')
        ];

        array_unshift($logs, $newLog); // Put new items at start
        
        // Truncate fallback file if too large
        if (count($logs) > 100) {
            $logs = array_slice($logs, 0, 100);
        }

        return (bool)@file_put_contents($this->fallbackFile, json_encode($logs, JSON_PRETTY_PRINT));
    }

    /**
     * Fetch JSON fallback logs
     */
    private function getJsonFallback(int $limit): array {
        if (!file_exists($this->fallbackFile)) {
            return [];
        }
        $data = @file_get_contents($this->fallbackFile);
        $logs = json_decode($data, true);
        if (!is_array($logs)) {
            return [];
        }
        return array_slice($logs, 0, $limit);
    }
}
