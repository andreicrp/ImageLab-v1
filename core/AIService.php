<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/AIClient.php';
require_once __DIR__ . '/EnhancementService.php';

/**
 * AIService Class
 * Orchestrates calls to the Python AI microservice and manages local file outputs
 */
class AIService {
    private AIClient $client;

    public function __construct() {
        // FastAPI runs on port 8000 by default
        $this->client = new AIClient('http://127.0.0.1:8000', 90);
    }

    /**
     * Upscale an image using Real-ESRGAN
     */
    public function upscale(string $source, string $target, int $scale = 2): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $binary = $this->client->postFile('/upscale', $source, ['scale' => $scale], true);
        if ($binary === false || empty($binary)) {
            return false;
        }

        return (bool)@file_put_contents($target, $binary);
    }

    /**
     * Restore facial details using GFPGAN
     */
    public function faceEnhance(string $source, string $target): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $binary = $this->client->postFile('/face-enhance', $source, [], true);
        if ($binary === false || empty($binary)) {
            return false;
        }

        return (bool)@file_put_contents($target, $binary);
    }

    /**
     * Remove image background using rembg
     */
    public function removeBackground(string $source, string $target): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $binary = $this->client->postFile('/remove-background', $source, [], true);
        if ($binary === false || empty($binary)) {
            return false;
        }

        return (bool)@file_put_contents($target, $binary);
    }

    /**
     * Analyze image quality scoring and suggestions
     */
    public function analyzeQuality(string $source): array|bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }

        return $this->client->postFile('/analyze-quality', $source);
    }

    /**
     * Generate automatic tags
     */
    public function generateTags(string $source): array|bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }

        return $this->client->postFile('/generate-tags', $source);
    }

    /**
     * Smart Auto Enhance (AI Mode)
     * Analyzes image and applies a combination of corrections:
     * - Exposure correction
     * - Face enhancement (if face detected)
     * - Denoising or Sharpening
     */
    public function autoEnhance(string $source, string $target): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            if (!Validator::isPathSafe($source, Config::PROCESSED_PATH)) {
                return false;
            }
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        // 1. Analyze the image
        $analysis = $this->analyzeQuality($source);
        if (!$analysis || !isset($analysis['success']) || !$analysis['success']) {
            // Default to copying the file on failure
            return @copy($source, $target);
        }

        $currentSource = $source;
        $tempFiles = [];

        // 2. Decide enhancement chain
        $hasFace = $analysis['has_face'] ?? false;
        $metrics = $analysis['metrics'] ?? [];
        $exposure = $metrics['exposure'] ?? 100;
        $sharpness = $metrics['sharpness'] ?? 100;
        $noise = $metrics['noise'] ?? 100;

        // Step A: Face enhancement if face is detected and blurry
        if ($hasFace && $sharpness < 85) {
            $tmpFace = Config::TEMP_PATH . 'auto_face_' . uniqid() . '.' . pathinfo($source, PATHINFO_EXTENSION);
            if ($this->faceEnhance($currentSource, $tmpFace)) {
                $currentSource = $tmpFace;
                $tempFiles[] = $tmpFace;
            }
        }

        // Step B: Exposure, Contrast and White Balance corrections via ImageMagick
        $brightnessVal = $metrics['brightness_val'] ?? 127;
        $tempCast = $metrics['temp_cast'] ?? 0;
        
        $params = [];
        $needsCorrection = false;

        // Exposure / Brightness correction
        if ($exposure < 75) {
            $needsCorrection = true;
            if ($brightnessVal < 100) {
                // Underexposed -> boost exposure, contrast, and shadows
                $params['exposure'] = 15;
                $params['contrast'] = 10;
                $params['shadows'] = 8;
            } else if ($brightnessVal > 150) {
                // Overexposed -> lower exposure and highlights
                $params['exposure'] = -15;
                $params['contrast'] = 5;
                $params['highlights'] = -10;
            } else {
                // Normal average but poor dynamic range
                $params['contrast'] = 15;
            }
        }

        // Temperature / Color Cast correction
        // Positive tempCast means warm cast -> make it cooler (negative temperature)
        // Negative tempCast means cool cast -> make it warmer (positive temperature)
        if (abs($tempCast) > 15) {
            $needsCorrection = true;
            $shift = (int)($tempCast * 1.5);
            $params['temperature'] = -min(50, max(-50, $shift));
        }

        if ($needsCorrection) {
            $tmpExp = Config::TEMP_PATH . 'auto_exp_' . uniqid() . '.' . pathinfo($source, PATHINFO_EXTENSION);
            $enhanceService = new EnhancementService();
            
            if ($enhanceService->applySliders($currentSource, $tmpExp, $params)) {
                $currentSource = $tmpExp;
                $tempFiles[] = $tmpExp;
            }
        }

        // Step C: Denoising and Sharpening adjustments
        if ($noise < 65 || $sharpness < 60) {
            $tmpDenoise = Config::TEMP_PATH . 'auto_denoise_' . uniqid() . '.' . pathinfo($source, PATHINFO_EXTENSION);
            $enhanceService = new EnhancementService();
            
            $params = [];
            if ($noise < 65) {
                // Apply a minor highlights adjustment to mimic noise removal levels
                $params['highlights'] = -10;
            }
            if ($sharpness < 60) {
                $params['sharpness'] = 20;
            }

            if ($enhanceService->applySliders($currentSource, $tmpDenoise, $params)) {
                $currentSource = $tmpDenoise;
                $tempFiles[] = $tmpDenoise;
            }
        }

        // Copy final processed file to target path
        $result = @copy($currentSource, $target);

        // Cleanup temporary chain files
        foreach ($tempFiles as $file) {
            @unlink($file);
        }

        return $result;
    }

    /**
     * Check if FastAPI is running
     */
    public function isOnline(): bool {
        return $this->client->isHealthy();
    }
}
