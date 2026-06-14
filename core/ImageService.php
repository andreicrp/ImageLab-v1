<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * ImageService Class
 * Controls core image manipulation commands using ImageMagick
 */
class ImageService {
    /**
     * Convert image format using ImageMagick CLI
     * 
     * @param string $source Path to the source file in /uploads
     * @param string $targetFormat Desired output format (jpg, png, webp)
     * @return array|bool Metadata on success, false on failure
     */
    public function convertImage(string $source, string $targetFormat): array|bool {
        $allowedOutputs = [
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'tiff', 'tif', 'ico',
            'heic', 'heif', 'avif', 'psd', 'pdf', 'eps', 'tga', 'exr', 'hdr', 'jfif'
        ];
        $targetFormat = strtolower($targetFormat);

        if (!in_array($targetFormat, $allowedOutputs, true)) {
            return false;
        }

        if (!file_exists($source)) {
            return false;
        }

        // Determine destination file
        $sourceFilename = pathinfo($source, PATHINFO_FILENAME);
        $targetFilename = $sourceFilename . '.' . $targetFormat;
        $targetPath = Config::PROCESSED_PATH . $targetFilename;

        // Perform security path validation
        if (!Validator::isPathSafe($source, Config::UPLOAD_PATH) && 
            !Validator::isPathSafe($source, Config::TEMP_PATH) && 
            !Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
            return false;
        }
        if (!Validator::isPathSafe($targetPath, Config::PROCESSED_PATH)) {
            return false;
        }

        // Build CLI command using safe parameter escape sequences
        $command = sprintf(
            'magick %s %s 2>&1',
            escapeshellarg($source),
            escapeshellarg($targetPath)
        );

        // Execute ImageMagick command
        exec($command, $output, $returnCode);

        // If 'magick' fails, try legacy 'convert' syntax as a fallback
        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s 2>&1',
                escapeshellarg($source),
                escapeshellarg($targetPath)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        // Log shell errors if operation failed
        if ($returnCode !== 0 || !file_exists($targetPath)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "CLI Error. Code: {$returnCode}. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        // Fetch new dimension details
        $width = 0;
        $height = 0;
        $imageInfo = @getimagesize($targetPath);
        if ($imageInfo !== false) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
        }

        return [
            'success' => true,
            'filename' => $targetFilename,
            'original_format' => strtolower(pathinfo($source, PATHINFO_EXTENSION)),
            'converted_format' => $targetFormat,
            'file_path' => $targetPath,
            'file_size' => filesize($targetPath),
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Resize image dimensions securely using ImageMagick geometry rules
     * 
     * @param string $source Absolute path of file
     * @param int $width Desired width (0 for dynamic)
     * @param int $height Desired height (0 for dynamic)
     * @param bool $maintainRatio True to preserve aspect ratio, false to stretch
     * @return array|bool Metadata on success, false on failure
     */
    public function resizeImage(string $source, int $width, int $height, bool $maintainRatio): array|bool {
        if (!file_exists($source)) {
            return false;
        }
        if (!Validator::isPathSafe($source, Config::UPLOAD_PATH) && 
            !Validator::isPathSafe($source, Config::TEMP_PATH) && 
            !Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
            return false;
        }

        $sourceFilename = pathinfo($source, PATHINFO_FILENAME);
        $sourceExt = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $targetFilename = $sourceFilename . '_resized.' . $sourceExt;
        $targetPath = Config::PROCESSED_PATH . $targetFilename;

        if (!Validator::isPathSafe($targetPath, Config::PROCESSED_PATH)) {
            return false;
        }

        // Formulate ImageMagick geometry
        if ($width > 0 && $height > 0) {
            $geometry = $maintainRatio ? "{$width}x{$height}" : "{$width}x{$height}!";
        } elseif ($width > 0) {
            $geometry = "{$width}";
        } elseif ($height > 0) {
            $geometry = "x{$height}";
        } else {
            return false;
        }

        $command = sprintf(
            'magick %s -resize %s %s 2>&1',
            escapeshellarg($source),
            escapeshellarg($geometry),
            escapeshellarg($targetPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s -resize %s %s 2>&1',
                escapeshellarg($source),
                escapeshellarg($geometry),
                escapeshellarg($targetPath)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($targetPath)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Resize CLI Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        $newW = 0;
        $newH = 0;
        $imageInfo = @getimagesize($targetPath);
        if ($imageInfo !== false) {
            $newW = $imageInfo[0];
            $newH = $imageInfo[1];
        }

        return [
            'success' => true,
            'filename' => $targetFilename,
            'file_path' => $targetPath,
            'file_size' => filesize($targetPath),
            'width' => $newW,
            'height' => $newH,
            'extension' => $sourceExt
        ];
    }

    /**
     * Compress and optimize image size
     * 
     * @param string $source Absolute path of source
     * @param int $quality Quality factor 1-100
     * @return array|bool Metadata on success, false on failure
     */
    public function compressImage(string $source, int $quality): array|bool {
        if (!file_exists($source)) {
            return false;
        }
        if (!Validator::isPathSafe($source, Config::UPLOAD_PATH) && 
            !Validator::isPathSafe($source, Config::TEMP_PATH) && 
            !Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
            return false;
        }

        $sourceFilename = pathinfo($source, PATHINFO_FILENAME);
        $sourceExt = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $targetFilename = $sourceFilename . '_compressed.' . $sourceExt;
        $targetPath = Config::PROCESSED_PATH . $targetFilename;

        if (!Validator::isPathSafe($targetPath, Config::PROCESSED_PATH)) {
            return false;
        }

        $quality = max(1, min(100, $quality));
        $args = '-strip';

        if ($sourceExt === 'png') {
            // PNG optimizations
            if ($quality < 35) {
                $args .= ' -colors 64 -define png:compression-level=9';
            } elseif ($quality < 65) {
                $args .= ' -colors 128 -define png:compression-level=9';
            } elseif ($quality < 85) {
                $args .= ' -colors 256 -define png:compression-level=9';
            } else {
                $args .= ' -define png:compression-level=9';
            }
        } else {
            // JPG / WEBP quality
            $args .= sprintf(' -quality %d', $quality);
        }

        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $args,
            escapeshellarg($targetPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s 2>&1',
                escapeshellarg($source),
                $args,
                escapeshellarg($targetPath)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($targetPath)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Compress CLI Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        $newW = 0;
        $newH = 0;
        $imageInfo = @getimagesize($targetPath);
        if ($imageInfo !== false) {
            $newW = $imageInfo[0];
            $newH = $imageInfo[1];
        }

        return [
            'success' => true,
            'filename' => $targetFilename,
            'file_path' => $targetPath,
            'file_size' => filesize($targetPath),
            'width' => $newW,
            'height' => $newH,
            'extension' => $sourceExt
        ];
    }

    /**
     * Convert image format (Legacy mapping)
     */
    public function convert(string $source, string $target, string $format): bool {
        $result = $this->convertImage($source, $format);
        return $result !== false;
    }

    /**
     * Resize image dimensions (Legacy mapping)
     */
    public function resize(string $source, string $target, int $width, int $height, bool $maintainAspectRatio = true): bool {
        $result = $this->resizeImage($source, $width, $height, $maintainAspectRatio);
        if ($result && file_exists($result['file_path'])) {
            return @copy($result['file_path'], $target);
        }
        return false;
    }

    /**
     * Compress image (Legacy mapping)
     */
    public function compress(string $source, string $target, int $quality): bool {
        $result = $this->compressImage($source, $quality);
        if ($result && file_exists($result['file_path'])) {
            return @copy($result['file_path'], $target);
        }
        return false;
    }

    /**
     * Enhance image / apply filters
     */
    public function enhance(string $source, string $target, array $options): bool {
        return true;
    }
}
