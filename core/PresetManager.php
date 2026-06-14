<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/ImageService.php';

/**
 * PresetManager Class
 * Handles retrieval and execution of image optimization presets
 */
class PresetManager {
    protected array $presets = [
        'web_optimized' => [
            'name' => 'Web Optimized',
            'description' => 'WEBP format, 80% Quality, Max Width 1920px (Aspect preserved)',
            'format' => 'webp',
            'quality' => 80,
            'resize_mode' => 'max',
            'width' => 1920,
            'height' => 1080
        ],
        'social_media' => [
            'name' => 'Social Media Square',
            'description' => 'JPG format, 85% Quality, Strict 1080x1080px (Stretched)',
            'format' => 'jpg',
            'quality' => 85,
            'resize_mode' => 'stretch',
            'width' => 1080,
            'height' => 1080
        ],
        'thumbnail' => [
            'name' => 'Thumbnail',
            'description' => 'WEBP format, 70% Quality, Fit 500x500px',
            'format' => 'webp',
            'quality' => 70,
            'resize_mode' => 'fit',
            'width' => 500,
            'height' => 500
        ]
    ];

    /**
     * Get list of presets
     * 
     * @return array
     */
    public function getPresets(): array {
        return $this->presets;
    }

    /**
     * Apply a preset directly using ImageMagick
     * 
     * @param string $source Path to original file in /uploads
     * @param string $presetKey Key of preset (web_optimized, social_media, thumbnail)
     * @return array|bool Metadata on success, false on failure
     */
    public function applyPreset(string $source, string $presetKey): array|bool {
        if (!array_key_exists($presetKey, $this->presets)) {
            return false;
        }

        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }

        $preset = $this->presets[$presetKey];
        $targetFormat = $preset['format'];
        
        $sourceFilename = pathinfo($source, PATHINFO_FILENAME);
        $targetFilename = $sourceFilename . '_' . $presetKey . '.' . $targetFormat;
        $targetPath = Config::PROCESSED_PATH . $targetFilename;

        if (!Validator::isPathSafe($targetPath, Config::PROCESSED_PATH)) {
            return false;
        }

        // Build command modifiers based on preset rules
        $resizeArg = '';
        $w = $preset['width'];
        $h = $preset['height'];

        if ($preset['resize_mode'] === 'max') {
            // Only shrink if original is larger than 1920 width
            $resizeArg = sprintf('-resize "%dx%d>"', $w, $h);
        } elseif ($preset['resize_mode'] === 'stretch') {
            // Force dimensions exactly
            $resizeArg = sprintf('-resize %dx%d!', $w, $h);
        } else {
            // Default fit within box
            $resizeArg = sprintf('-resize %dx%d', $w, $h);
        }

        $qualityArg = sprintf('-quality %d', $preset['quality']);

        // Execute command
        $command = sprintf(
            'magick %s %s %s %s 2>&1',
            escapeshellarg($source),
            $resizeArg,
            $qualityArg,
            escapeshellarg($targetPath)
        );

        exec($command, $output, $returnCode);

        // Fallback to legacy convert syntax if magick not found
        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s %s 2>&1',
                escapeshellarg($source),
                $resizeArg,
                $qualityArg,
                escapeshellarg($targetPath)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($targetPath)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Preset Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

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
            'preset_name' => $preset['name'],
            'converted_format' => $targetFormat,
            'file_path' => $targetPath,
            'file_size' => filesize($targetPath),
            'width' => $width,
            'height' => $height
        ];
    }
}
