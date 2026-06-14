<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Validator.php';

/**
 * MetadataManager Class
 * Reads EXIF metadata information and strips metadata headers for privacy
 */
class MetadataManager {
    
    /**
     * Read EXIF data from an image file
     * 
     * @param string $filePath Path to image file
     * @return array Metadata fields, empty array if none found or unsupported format
     */
    public function readMetadata(string $filePath): array {
        if (!file_exists($filePath) || !Validator::isPathSafe($filePath, Config::UPLOAD_PATH)) {
            // Also check processed folder
            if (!Validator::isPathSafe($filePath, Config::PROCESSED_PATH)) {
                return [];
            }
        }

        // EXIF is only supported for JPEG and TIFF files in PHP
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'tiff', 'tif'])) {
            return [
                'success' => false,
                'message' => 'EXIF metadata is only supported for JPEG and TIFF formats.'
            ];
        }

        if (!function_exists('exif_read_data')) {
            return [
                'success' => false,
                'message' => 'PHP EXIF extension is not enabled on this server.'
            ];
        }

        // Suppress warnings as corrupt EXIF blocks can cause errors
        $exif = @exif_read_data($filePath);
        if (!$exif || !is_array($exif)) {
            return [
                'success' => true,
                'has_exif' => false,
                'fields' => []
            ];
        }

        $fields = [];

        // Camera Info
        if (isset($exif['Make'])) $fields['Make'] = trim($exif['Make']);
        if (isset($exif['Model'])) $fields['Model'] = trim($exif['Model']);
        if (isset($exif['Software'])) $fields['Software'] = trim($exif['Software']);

        // Date/Time
        if (isset($exif['DateTimeOriginal'])) $fields['DateTaken'] = $exif['DateTimeOriginal'];
        elseif (isset($exif['DateTime'])) $fields['DateTaken'] = $exif['DateTime'];

        // Settings
        if (isset($exif['ExposureTime'])) $fields['ExposureTime'] = $exif['ExposureTime'] . ' s';
        if (isset($exif['FNumber'])) {
            $fNumber = $this->rationalToFloat($exif['FNumber']);
            $fields['Aperture'] = 'f/' . round($fNumber, 2);
        }
        if (isset($exif['ISOSpeedRatings'])) {
            $fields['ISO'] = is_array($exif['ISOSpeedRatings']) ? implode(', ', $exif['ISOSpeedRatings']) : $exif['ISOSpeedRatings'];
        }
        if (isset($exif['FocalLength'])) {
            $focal = $this->rationalToFloat($exif['FocalLength']);
            $fields['FocalLength'] = round($focal, 1) . ' mm';
        }

        // GPS Coordinates
        if (
            isset($exif['GPSLatitude']) && isset($exif['GPSLatitudeRef']) &&
            isset($exif['GPSLongitude']) && isset($exif['GPSLongitudeRef'])
        ) {
            $lat = $this->getGpsDecimal($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            $lng = $this->getGpsDecimal($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
            if ($lat !== null && $lng !== null) {
                $fields['GPS'] = [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'formatted' => sprintf('%.6f, %.6f', $lat, $lng)
                ];
            }
        }

        // File basic info
        if (isset($exif['FileSize'])) $fields['FileSize'] = $exif['FileSize'] . ' bytes';
        if (isset($exif['COMPUTED']['Width'])) $fields['Width'] = $exif['COMPUTED']['Width'] . ' px';
        if (isset($exif['COMPUTED']['Height'])) $fields['Height'] = $exif['COMPUTED']['Height'] . ' px';

        return [
            'success' => true,
            'has_exif' => true,
            'fields' => $fields
        ];
    }

    /**
     * Strip all metadata headers from an image for privacy protection
     * 
     * @param string $source Path of original image file
     * @param string $target Path of target output file
     * @return bool
     */
    public function stripMetadata(string $source, string $target): bool {
        if (!file_exists($source) || !Validator::isPathSafe($source, Config::UPLOAD_PATH)) {
            return false;
        }
        if (!Validator::isPathSafe($target, Config::PROCESSED_PATH)) {
            return false;
        }

        $command = sprintf(
            'magick %s -strip %s 2>&1',
            escapeshellarg($source),
            escapeshellarg($target)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $fallbackCommand = sprintf(
                'convert %s -strip %s 2>&1',
                escapeshellarg($source),
                escapeshellarg($target)
            );
            exec($fallbackCommand, $output, $returnCode);
        }

        if ($returnCode !== 0 || !file_exists($target)) {
            $logMsg = date('[Y-m-d H:i:s] ') . "Strip Metadata Error. Exec: {$command}. Output: " . implode(' | ', $output) . "\n";
            @file_put_contents(Config::LOG_PATH . 'imagemagick.log', $logMsg, FILE_APPEND);
            return false;
        }

        return true;
    }

    /**
     * Parse GPS coordinate arrays to decimal format
     */
    protected function getGpsDecimal(array $coordinate, string $ref): ?float {
        if (count($coordinate) < 3) return null;
        
        $degrees = $this->rationalToFloat($coordinate[0]);
        $minutes = $this->rationalToFloat($coordinate[1]);
        $seconds = $this->rationalToFloat($coordinate[2]);
        
        $decimal = $degrees + ($minutes / 60) + ($seconds / 3600);
        
        if (in_array(strtoupper($ref), ['S', 'W'])) {
            $decimal = -$decimal;
        }
        
        return $decimal;
    }

    /**
     * Helper to resolve fractions or numbers into floating points
     */
    protected function rationalToFloat($rational): float {
        if (is_numeric($rational)) {
            return (float)$rational;
        }
        if (is_string($rational)) {
            $parts = explode('/', $rational);
            if (count($parts) === 2 && $parts[1] != 0) {
                return (float)$parts[0] / (float)$parts[1];
            }
        }
        return 0.0;
    }
}
