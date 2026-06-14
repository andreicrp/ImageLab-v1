<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * FileManager Class for secure storage, deletion, and cleanup operations
 */
class FileManager {
    /**
     * Upload an image securely
     * 
     * @param array $file $_FILES['name'] item
     * @param string $destinationDir Target directory (typically Config::UPLOAD_PATH)
     * @return array|bool Metadata array on success, false on failure
     */
    public function upload(array $file, string $destinationDir): array|bool {
        if (!Validator::validateFile($file)) {
            return false;
        }

        $originalName = $file['name'];
        // Sanitize original filename (keep alphanumeric, dots, dashes, underscores)
        $sanitizedOriginal = preg_replace('/[^a-zA-Z0-9_\.-]/', '', $originalName);
        
        // Extract and clean extension
        $extension = strtolower(pathinfo($sanitizedOriginal, PATHINFO_EXTENSION));

        // Generate unique cryptographically secure filename
        $uniqueFilename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = rtrim($destinationDir, '/\\') . '/' . $uniqueFilename;

        // Prevent directory traversal attacks by validating that target stays inside base upload folder
        if (!Validator::isPathSafe($targetPath, $destinationDir)) {
            return false;
        }

        // Move the file from temp location
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        // Get image dimensions
        $width = 0;
        $height = 0;
        $imageInfo = @getimagesize($targetPath);
        if ($imageInfo !== false) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
        }

        return [
            'success' => true,
            'filename' => $uniqueFilename,
            'original_name' => $sanitizedOriginal,
            'file_path' => $targetPath,
            'file_size' => $file['size'],
            'width' => $width,
            'height' => $height,
            'extension' => $extension
        ];
    }

    /**
     * Delete a file securely from disk
     * 
     * @param string $path File path to delete
     * @return bool
     */
    public function delete(string $path): bool {
        // Enforce safety: only allow deleting files within designated project paths
        $isUploadSafe = Validator::isPathSafe($path, Config::UPLOAD_PATH);
        $isProcessedSafe = Validator::isPathSafe($path, Config::PROCESSED_PATH);
        $isTempSafe = Validator::isPathSafe($path, Config::TEMP_PATH);

        if (!$isUploadSafe && !$isProcessedSafe && !$isTempSafe) {
            return false; // Traversal or unauthorized file deletion attempt
        }

        if (is_file($path) && file_exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * Scan uploads, processed, and temp directories and clean up files older than 24 hours
     * 
     * @param int $maxAge Age threshold in seconds (default: 24 hours = 86400 seconds)
     * @return int Total files deleted
     */
    public function cleanupOldFiles(int $maxAge = 86400): int {
        $directories = [
            Config::UPLOAD_PATH,
            Config::PROCESSED_PATH,
            Config::TEMP_PATH
        ];

        $deletedCount = 0;
        $now = time();

        foreach ($directories as $dir) {
            $realDir = realpath($dir);
            if ($realDir === false || !is_dir($realDir)) {
                continue;
            }

            $files = scandir($realDir);
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                // Ignore directories and system files (.gitkeep, .htaccess, etc)
                if ($file === '.' || $file === '..' || $file === '.gitkeep' || $file === '.htaccess') {
                    continue;
                }

                $filePath = $realDir . DIRECTORY_SEPARATOR . $file;
                
                // Final safety check on path before unlinking
                if (!is_file($filePath) || !Validator::isPathSafe($filePath, $dir)) {
                    continue;
                }

                $fileAge = $now - filemtime($filePath);
                if ($fileAge > $maxAge) {
                    if (@unlink($filePath)) {
                        $deletedCount++;
                    }
                }
            }
        }

        return $deletedCount;
    }
}
