<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * EditorService Class
 * Controls server-side workspace snapshot operations
 */
class EditorService {
    private string $storageDir;

    public function __construct() {
        $this->storageDir = Config::TEMP_PATH;
    }

    /**
     * Store workspace JSON snapshot to disk
     * 
     * @param string $sessionId Unique session ID
     * @param string $canvasData Serialized Fabric.js canvas JSON string
     * @return bool
     */
    public function saveWorkspace(string $sessionId, string $canvasData): bool {
        // Sanitize session identifier to prevent path traversal
        $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        if (empty($safeSessionId)) {
            return false;
        }

        $targetPath = $this->storageDir . 'session_' . $safeSessionId . '.json';

        // Secure boundary check
        if (!Validator::isPathSafe($targetPath, $this->storageDir)) {
            return false;
        }

        $payload = json_encode([
            'session_id' => $safeSessionId,
            'canvas_data' => $canvasData,
            'updated_at' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);

        return (bool)@file_put_contents($targetPath, $payload);
    }

    /**
     * Fetch workspace JSON snapshot from disk
     * 
     * @param string $sessionId
     * @return array|bool Returns state metadata array or false on failure
     */
    public function loadWorkspace(string $sessionId): array|bool {
        $safeSessionId = preg_replace('/[^a-zA-Z0-9_-]/', '', $sessionId);
        if ($safeSessionId === 'latest') {
            // Find the most recently modified session file
            return $this->getLatestSession();
        }

        if (empty($safeSessionId)) {
            return false;
        }

        $targetPath = $this->storageDir . 'session_' . $safeSessionId . '.json';

        if (!file_exists($targetPath) || !Validator::isPathSafe($targetPath, $this->storageDir)) {
            return false;
        }

        $data = @file_get_contents($targetPath);
        if (!$data) {
            return false;
        }

        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : false;
    }

    /**
     * Helper to retrieve the latest modified session file
     */
    private function getLatestSession(): array|bool {
        $realDir = realpath($this->storageDir);
        if ($realDir === false || !is_dir($realDir)) {
            return false;
        }

        $files = scandir($realDir);
        if ($files === false) {
            return false;
        }

        $latestFile = null;
        $latestTime = 0;

        foreach ($files as $file) {
            if (str_starts_with($file, 'session_') && str_ends_with($file, '.json')) {
                $filePath = $realDir . DIRECTORY_SEPARATOR . $file;
                $mtime = filemtime($filePath);
                if ($mtime > $latestTime) {
                    $latestTime = $mtime;
                    $latestFile = $filePath;
                }
            }
        }

        if (!$latestFile || !file_exists($latestFile)) {
            return false;
        }

        $data = @file_get_contents($latestFile);
        if (!$data) {
            return false;
        }

        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : false;
    }
}
