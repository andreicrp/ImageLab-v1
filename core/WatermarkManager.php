<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * WatermarkManager Class
 * Handles text and image/logo watermarking on top of images using ImageMagick CLI
 */
class WatermarkManager {
    
    // Mapping of UI alignment options to ImageMagick gravity values
    protected array $gravityMap = [
        'top-left'      => 'NorthWest',
        'top-center'    => 'North',
        'top-right'     => 'NorthEast',
        'center-left'   => 'West',
        'center'        => 'Center',
        'center-right'  => 'East',
        'bottom-left'   => 'SouthWest',
        'bottom-center' => 'South',
        'bottom-right'  => 'SouthEast'
    ];

    /**
     * Add a text watermark to an image
     * 
     * @param string $source Original image path
     * @param string $target Output image path
     * @param string $text Text to draw
     * @param array $options Configuration options (font, size, color, opacity, position, rotation, offset_x, offset_y)
     * @return bool
     */
    public function addTextWatermark(string $source, string $target, string $text, array $options = []): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        if (empty($text)) {
            return @copy($source, $target);
        }

        $position = $options['position'] ?? 'bottom-right';
        $gravity = $this->gravityMap[$position] ?? 'SouthEast';
        
        $fontSize = isset($options['size']) ? (int)$options['size'] : 30;
        $color = $options['color'] ?? '#ffffff'; // Hex format e.g., #ffffff
        $opacity = isset($options['opacity']) ? (float)$options['opacity'] : 0.5; // 0.0 to 1.0
        $rotation = isset($options['rotation']) ? (int)$options['rotation'] : 0;
        $offsetX = isset($options['offset_x']) ? (int)$options['offset_x'] : 20;
        $offsetY = isset($options['offset_y']) ? (int)$options['offset_y'] : 20;

        // Parse hex color to rgba
        $rgba = $this->hexToRgba($color, $opacity);

        // Escape text for draw/annotate syntax: escape quotes, backslashes, percent signs
        $escapedText = str_replace(
            ['\\', '"', '%', '\n'],
            ['\\\\', '\\"', '\\%', "\n"],
            $text
        );

        // Build command
        // We use -annotate with rotation and offsets: -annotate [degrees]x[degrees]+[offset_x]+[offset_y]
        $drawArgs = sprintf(
            '-fill %s -pointsize %d -gravity %s -annotate %dx%d+%d+%d %s',
            escapeshellarg($rgba),
            $fontSize,
            $gravity,
            $rotation,
            $rotation,
            $offsetX,
            $offsetY,
            escapeshellarg($escapedText)
        );

        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $drawArgs,
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s 2>&1',
                escapeshellarg($source),
                $drawArgs,
                escapeshellarg($target)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($target)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Text Watermark Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }

    /**
     * Add an image logo watermark to an image
     * 
     * @param string $source Original image path
     * @param string $target Output image path
     * @param string $logoPath Logo image path
     * @param array $options Configuration options (position, opacity, scale, offset_x, offset_y)
     * @return bool
     */
    public function addLogoWatermark(string $source, string $target, string $logoPath, array $options = []): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }
        if (!file_exists($logoPath) || !Validator::isPathSafe($logoPath, Config::UPLOAD_PATH)) {
            // Also search temp or uploads just in case
            if (!Validator::isPathSafe($logoPath, Config::TEMP_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $position = $options['position'] ?? 'bottom-right';
        $gravity = $this->gravityMap[$position] ?? 'SouthEast';
        $opacity = isset($options['opacity']) ? (float)$options['opacity'] : 0.8; // 0.0 to 1.0
        $scale = isset($options['scale']) ? (int)$options['scale'] : 20; // percentage of main image width (e.g. 20%)
        $offsetX = isset($options['offset_x']) ? (int)$options['offset_x'] : 20;
        $offsetY = isset($options['offset_y']) ? (int)$options['offset_y'] : 20;

        // Fetch source and logo image sizes
        $sourceSize = @getimagesize($source);
        if (!$sourceSize) {
            return false;
        }
        $sourceWidth = $sourceSize[0];

        // Determine target logo width based on scale percentage of source image width
        $logoWidth = max(20, round($sourceWidth * ($scale / 100)));

        // Formulate logo transform arguments inside parentheses
        // 1. Resize the logo maintaining aspect ratio
        // 2. Adjust opacity using evaluate multiply on Alpha channel
        $logoTransform = sprintf(
            '( %s -resize %dx -channel A -evaluate multiply %.2f +channel )',
            escapeshellarg($logoPath),
            $logoWidth,
            $opacity
        );

        // Composite command: magick main_image logo_transformed -gravity gravity -geometry +x+y -composite target
        $command = sprintf(
            'magick %s %s -gravity %s -geometry +%d+%d -composite %s 2>&1',
            escapeshellarg($source),
            $logoTransform,
            $gravity,
            $offsetX,
            $offsetY,
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s -gravity %s -geometry +%d+%d -composite %s 2>&1',
                escapeshellarg($source),
                $logoTransform,
                $gravity,
                $offsetX,
                $offsetY,
                escapeshellarg($target)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($target)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Logo Watermark Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }

    /**
     * Helper to convert hex color code (#ffffff or #fff) to rgba(...) for ImageMagick
     * 
     * @param string $hex
     * @param float $alpha
     * @return string
     */
    protected function hexToRgba(string $hex, float $alpha): string {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } elseif (strlen($hex) == 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            $r = 255;
            $g = 255;
            $b = 255;
        }

        return sprintf('rgba(%d,%d,%d,%.2f)', $r, $g, $b, $alpha);
    }
}
