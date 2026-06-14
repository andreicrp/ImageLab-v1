<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * FilterManager Class
 * Appliess modern color filters and photographic styles using ImageMagick CLI
 */
class FilterManager {
    protected array $filters = [
        'original' => '',
        'vivid' => '-modulate 100,140,100 -sigmoidal-contrast 3x50%',
        'warm' => '-channel R -evaluate multiply 1.15 -channel B -evaluate multiply 0.85 +channel',
        'cool' => '-channel B -evaluate multiply 1.15 -channel R -evaluate multiply 0.85 +channel',
        'bw' => '-colorspace gray -contrast-stretch 0.15%',
        'vintage' => '-sepia-tone 75% -modulate 100,90,100 -channel R -evaluate multiply 1.05 -channel B -evaluate multiply 0.9 +channel',
        'cinema' => '-modulate 95,120,100 -channel R -evaluate multiply 1.05 -channel B -evaluate multiply 1.1 +channel -sigmoidal-contrast 4x50%',
        'hdr' => '-contrast-stretch 0.15% -sharpen 0x1.5 -evaluate multiply 1.05',
        'soft' => '-blur 0x1 -evaluate multiply 1.02',
        'dramatic' => '-modulate 85,90,100 -sigmoidal-contrast 5x50%'
    ];

    /**
     * Get list of supported filters
     * @return array
     */
    public function getAvailableFilters(): array {
        return array_keys($this->filters);
    }

    /**
     * Apply filter using ImageMagick CLI
     * 
     * @param string $source Path of original image file
     * @param string $target Path of target output file
     * @param string $filterName Key representing filter settings
     * @return bool
     */
    public function applyFilter(string $source, string $target, string $filterName): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $filterName = strtolower($filterName);
        if (!array_key_exists($filterName, $this->filters)) {
            return false;
        }

        $filterArgs = $this->filters[$filterName];

        // If 'original', just copy the file directly
        if (empty($filterArgs)) {
            return @copy($source, $target);
        }

        $command = sprintf(
            'magick %s %s %s 2>&1',
            escapeshellarg($source),
            $filterArgs,
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        // Fallback to convert syntax
        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s %s %s 2>&1',
                escapeshellarg($source),
                $filterArgs,
                escapeshellarg($target)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($target)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Filter apply error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }
}
