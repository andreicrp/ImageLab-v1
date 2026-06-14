<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * ExportManager Class
 * Manages image conversions, background manipulations (solid fill, color replace, canvas extent),
 * quality settings, and provides file size estimations.
 */
class ExportManager {

    /**
     * Process background tools and export image in target quality & format
     * 
     * @param string $source Path of original image
     * @param string $target Path of output image
     * @param string $format Target format ('jpg', 'png', 'webp')
     * @param int $quality Quality factor (1 to 100)
     * @param array $bgOptions Background options (color_replace, solid_bg, expand_canvas)
     * @return array|bool Array of details on success, false on failure
     */
    public function exportImage(string $source, string $target, string $format, int $quality, array $bgOptions = []): array|bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $format = strtolower($format);
        $allowedFormats = [
            'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'tiff', 'tif', 'ico',
            'heic', 'heif', 'avif', 'psd', 'pdf', 'eps', 'tga', 'exr', 'hdr', 'jfif'
        ];
        if (!in_array($format, $allowedFormats, true)) {
            return false;
        }

        // Adjust quality bounds
        $quality = max(1, min(100, $quality));
        $args = [];

        // 1. Color Replace (fuzz factor + fill opaque color)
        if (isset($bgOptions['color_replace']) && is_array($bgOptions['color_replace'])) {
            $oldColor = $bgOptions['color_replace']['old_color'] ?? '';
            $newColor = $bgOptions['color_replace']['new_color'] ?? '';
            $fuzz = isset($bgOptions['color_replace']['fuzz']) ? (int)$bgOptions['color_replace']['fuzz'] : 10;
            
            if (!empty($oldColor) && !empty($newColor)) {
                $args[] = sprintf('-fuzz %d%% -fill %s -opaque %s', $fuzz, escapeshellarg($newColor), escapeshellarg($oldColor));
            }
        }

        // 2. Solid Background (Replaces transparent alpha channels with solid color)
        if (isset($bgOptions['solid_bg']) && !empty($bgOptions['solid_bg'])) {
            $bgColor = $bgOptions['solid_bg'];
            // For transparent images, flatten them on top of a solid background color
            $args[] = sprintf('-background %s -alpha remove -alpha off', escapeshellarg($bgColor));
        }

        // 3. Expand Canvas (extent image sizes with background border)
        if (isset($bgOptions['expand_canvas']) && is_array($bgOptions['expand_canvas'])) {
            $newW = isset($bgOptions['expand_canvas']['width']) ? (int)$bgOptions['expand_canvas']['width'] : 0;
            $newH = isset($bgOptions['expand_canvas']['height']) ? (int)$bgOptions['expand_canvas']['height'] : 0;
            $canvasBg = $bgOptions['expand_canvas']['bg_color'] ?? '#ffffff';
            $gravity = $bgOptions['expand_canvas']['gravity'] ?? 'center';

            // Map UI gravity options
            $gravityMap = [
                'top-left' => 'NorthWest', 'top-center' => 'North', 'top-right' => 'NorthEast',
                'center-left' => 'West', 'center' => 'Center', 'center-right' => 'East',
                'bottom-left' => 'SouthWest', 'bottom-center' => 'South', 'bottom-right' => 'SouthEast'
            ];
            $gVal = $gravityMap[$gravity] ?? 'Center';

            if ($newW > 0 && $newH > 0) {
                $args[] = sprintf('-background %s -gravity %s -extent %dx%d', escapeshellarg($canvasBg), $gVal, $newW, $newH);
            }
        }

        // 4. Quality Arguments
        if (in_array($format, ['jpg', 'jpeg', 'webp', 'heic', 'heif', 'avif', 'exr', 'hdr', 'jfif'], true)) {
            $args[] = sprintf('-quality %d', $quality);
        }

        $commandArgs = implode(' ', $args);

        // Execute unified command
        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $commandArgs,
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s 2>&1',
                escapeshellarg($source),
                $commandArgs,
                escapeshellarg($target)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($target)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Export Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        $width = 0;
        $height = 0;
        $imageInfo = @getimagesize($target);
        if ($imageInfo !== false) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
        }

        return [
            'success' => true,
            'filename' => basename($target),
            'format' => $format,
            'size' => filesize($target),
            'width' => $width,
            'height' => $height
        ];
    }

    /**
     * Estimates final output size by performing a fast dry-run conversion to a temporary file
     * 
     * @param string $source Original image path
     * @param string $format Target format ('jpg', 'png', 'webp')
     * @param int $quality Quality factor
     * @return int Estimated size in bytes
     */
    public function estimateSize(string $source, string $format, int $quality): int {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return 0;
            }
        }

        $format = strtolower($format);
        $tempFile = Config::TEMP_PATH . 'est_' . uniqid() . '.' . $format;
        
        $quality = max(1, min(100, $quality));
        $qualityArg = '';
        if ($format === 'jpg' || $format === 'jpeg' || $format === 'webp') {
            $qualityArg = sprintf('-quality %d', $quality);
        }

        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $qualityArg,
            escapeshellarg($tempFile)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s 2>&1',
                escapeshellarg($source),
                $qualityArg,
                escapeshellarg($tempFile)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        $estimatedSize = 0;
        if (file_exists($tempFile)) {
            $estimatedSize = filesize($tempFile);
            @unlink($tempFile); // Clean up immediately
        }

        return $estimatedSize;
    }
}
