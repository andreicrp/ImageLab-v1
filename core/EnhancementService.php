<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * EnhancementService Class
 * Controls core image optimizations and slider adjustments using ImageMagick CLI
 */
class EnhancementService {
    /**
     * Apply all slide adjustments at once using a unified ImageMagick command
     * 
     * @param string $source Path of original image file
     * @param string $target Path of target output file
     * @param array $params Slider parameters (brightness, contrast, saturation, sharpness, exposure, highlights, shadows, temperature, tint)
     * @return bool
     */
    public function applySliders(string $source, string $target, array $params): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        // 1. Parse slide offsets (default is 0)
        $brightness = isset($params['brightness']) ? (int)$params['brightness'] : 0; // -100 to 100
        $contrast = isset($params['contrast']) ? (int)$params['contrast'] : 0; // -100 to 100
        $saturation = isset($params['saturation']) ? (int)$params['saturation'] : 0; // -100 to 100
        $sharpness = isset($params['sharpness']) ? (int)$params['sharpness'] : 0; // 0 to 100
        $exposure = isset($params['exposure']) ? (int)$params['exposure'] : 0; // -100 to 100
        $highlights = isset($params['highlights']) ? (int)$params['highlights'] : 0; // -100 to 100
        $shadows = isset($params['shadows']) ? (int)$params['shadows'] : 0; // -100 to 100
        $temp = isset($params['temperature']) ? (int)$params['temperature'] : 0; // -100 to 100
        $tint = isset($params['tint']) ? (int)$params['tint'] : 0; // -100 to 100

        $args = [];

        // Brightness & Saturation modulation (100% is default)
        // ImageMagick modulated params: brightness, saturation, hue
        $modB = 100 + $brightness;
        $modS = 100 + $saturation;
        if ($modB !== 100 || $modS !== 100) {
            $args[] = sprintf('-modulate %d,%d,100', $modB, $modS);
        }

        // Contrast adjustments (using sigmoidal contrast scaling)
        if ($contrast !== 0) {
            $factor = abs($contrast) / 10;
            if ($contrast > 0) {
                $args[] = sprintf('-sigmoidal-contrast %.1fx50%%', $factor);
            } else {
                $args[] = sprintf('+sigmoidal-contrast %.1fx50%%', $factor);
            }
        }

        // Exposure (evaluate multiply)
        if ($exposure !== 0) {
            $multFactor = 1.0 + ($exposure / 100);
            $args[] = sprintf('-evaluate multiply %.2f', $multFactor);
        }

        // Sharpness (unsharp mask)
        if ($sharpness > 0) {
            $radius = 0.5 + ($sharpness / 50);
            $args[] = sprintf('-sharpen 0x%.1f', $radius);
        }

        // Highlights & Shadows (using level manipulation or simple gamma adjustments)
        if ($highlights !== 0) {
            // Adjust highlights via gamma manipulation targeting highlights
            $gamma = 1.0 - ($highlights / 200);
            $args[] = sprintf('-level 0%%,100%%,%.2f', $gamma);
        }
        if ($shadows !== 0) {
            // Adjust shadows
            $gamma = 1.0 + ($shadows / 200);
            $args[] = sprintf('-level 0%%,100%%,%.2f', $gamma);
        }

        // Temperature & Tint (using color level adjustments)
        if ($temp !== 0) {
            // Shift red/blue channels
            $rShift = 1.0 + ($temp / 200);
            $bShift = 1.0 - ($temp / 200);
            $args[] = sprintf('-channel R -evaluate multiply %.2f -channel B -evaluate multiply %.2f +channel', $rShift, $bShift);
        }
        if ($tint !== 0) {
            // Shift green channel
            $gShift = 1.0 + ($tint / 200);
            $args[] = sprintf('-channel G -evaluate multiply %.2f +channel', $gShift);
        }

        $commandArgs = implode(' ', $args);

        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $commandArgs,
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        // Fallback to convert
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
            $logMsg = date('[Y-m-d H:i:s] ') . "Enhancement Sliders Error: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }

    /**
     * Apply Auto-Enhance preset modes
     * 
     * @param string $source Original file path
     * @param string $target Output target path
     * @param string $mode Auto mode preset key
     * @return bool
     */
    public function applyAutoEnhance(string $source, string $target, string $mode): bool {
        $presets = [
            'auto' => [
                'brightness' => 10,
                'contrast' => 5,
                'saturation' => 5,
                'sharpness' => 10,
                'exposure' => 5
            ],
            'landscape' => [
                'brightness' => 5,
                'contrast' => 15,
                'saturation' => 20,
                'sharpness' => 25,
                'temperature' => 5
            ],
            'portrait' => [
                'brightness' => 15,
                'contrast' => -5,
                'saturation' => -5,
                'sharpness' => 5,
                'tint' => 5
            ],
            'product' => [
                'brightness' => 20,
                'contrast' => 10,
                'saturation' => 5,
                'sharpness' => 30,
                'highlights' => 5
            ],
            'social' => [
                'brightness' => 10,
                'contrast' => 15,
                'saturation' => 15,
                'sharpness' => 15,
                'exposure' => 5
            ]
        ];

        $mode = strtolower($mode);
        if (!array_key_exists($mode, $presets)) {
            return false;
        }

        return $this->applySliders($source, $target, $presets[$mode]);
    }
}
