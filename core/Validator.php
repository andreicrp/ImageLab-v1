<?php

/**
 * ImageLab Validation Helper Class
 */
class Validator {
    // Expected mime mappings to enforce extension consistency
    protected static array $mimeMappings = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'jfif' => ['image/jpeg', 'image/pjpeg', 'image/jfif', 'application/octet-stream'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'svg'  => ['image/svg+xml', 'text/xml', 'application/xml', 'text/plain'],
        'bmp'  => ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp', 'image/x-win-bitmap', 'application/octet-stream'],
        'tiff' => ['image/tiff', 'image/x-tiff', 'application/octet-stream'],
        'tif'  => ['image/tiff', 'image/x-tiff', 'application/octet-stream'],
        'ico'  => ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico', 'application/ico', 'application/octet-stream'],
        'heic' => ['image/heic', 'image/heic-sequence', 'application/octet-stream'],
        'heif' => ['image/heif', 'image/heif-sequence', 'application/octet-stream'],
        'avif' => ['image/avif', 'application/octet-stream'],
        'raw'  => ['image/x-panasonic-raw', 'image/raw', 'application/octet-stream'],
        'cr2'  => ['image/x-canon-cr2', 'image/cr2', 'application/octet-stream'],
        'cr3'  => ['image/x-canon-cr3', 'image/cr3', 'application/octet-stream'],
        'nef'  => ['image/x-nikon-nef', 'image/nef', 'application/octet-stream'],
        'arw'  => ['image/x-sony-arw', 'image/arw', 'application/octet-stream'],
        'dng'  => ['image/x-adobe-dng', 'image/dng', 'application/octet-stream'],
        'orf'  => ['image/x-olympus-orf', 'image/orf', 'application/octet-stream'],
        'raf'  => ['image/x-fuji-raf', 'image/raf', 'application/octet-stream'],
        'psd'  => ['image/vnd.adobe.photoshop', 'image/x-photoshop', 'image/psd', 'application/x-photoshop', 'application/photoshop', 'application/octet-stream'],
        'ai'   => ['application/postscript', 'application/pdf', 'application/octet-stream'],
        'eps'  => ['application/postscript', 'image/x-eps', 'image/eps', 'application/octet-stream'],
        'pdf'  => ['application/pdf', 'application/octet-stream'],
        'tga'  => ['image/x-tga', 'image/tga', 'image/x-targa', 'application/octet-stream'],
        'exr'  => ['image/x-exr', 'application/octet-stream'],
        'hdr'  => ['image/vnd.radiance', 'image/hdr', 'application/octet-stream']
    ];

    /**
     * Check if a PHP file upload structure is valid and has no errors
     * 
     * @param array $file The $_FILES['name'] array element
     * @return bool
     */
    public static function validateFile(array $file): bool {
        return isset($file['tmp_name']) && 
               isset($file['error']) && 
               $file['error'] === UPLOAD_ERR_OK && 
               is_uploaded_file($file['tmp_name']);
    }

    /**
     * Check if the file format is allowed based on filename extension
     * 
     * @param string $filename Original name or path of the file
     * @param array $allowedFormats Array of lowercase extensions
     * @return bool
     */
    public static function validateFormat(string $filename, array $allowedFormats): bool {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedFormats, true);
    }

    /**
     * Check if the file size fits within limits
     * 
     * @param int $fileSize Actual file size in bytes
     * @param int $maxSize Max size in bytes
     * @return bool
     */
    public static function validateSize(int $fileSize, int $maxSize): bool {
        return $fileSize > 0 && $fileSize <= $maxSize;
    }

    /**
     * Perform real MIME type validation using PHP Fileinfo to prevent spoofing
     * 
     * @param string $tmpPath The temporary path of the uploaded file
     * @param string $filename The original filename to get extension
     * @return bool
     */
    public static function validateMimeType(string $tmpPath, string $filename): bool {
        if (!file_exists($tmpPath)) {
            return false;
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!array_key_exists($extension, self::$mimeMappings)) {
            return false;
        }

        // Get file mime type securely
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!$mimeType) {
            return false;
        }

        return in_array($mimeType, self::$mimeMappings[$extension], true);
    }

    /**
     * Verify that a file path is safe and stays within the base directory (prevent directory traversal)
     * 
     * @param string $path Target file path
     * @param string $baseDirectory Expected base directory (e.g. Config::UPLOAD_PATH)
     * @return bool
     */
    public static function isPathSafe(string $path, string $baseDirectory): bool {
        $realBase = realpath($baseDirectory);
        
        // If directory doesn't exist, try to resolve its parent
        if ($realBase === false) {
            // Attempt to create it or fall back
            return false;
        }

        $realPath = realpath($path);
        if ($realPath === false) {
            // If the file doesn't exist yet (e.g. output destination), check its parent directory
            $parentDir = dirname($path);
            $realParent = realpath($parentDir);
            if ($realParent === false) {
                return false;
            }
            
            $normalizedParent = strtolower(str_replace('\\', '/', $realParent));
            $normalizedBase = strtolower(str_replace('\\', '/', $realBase));
            
            // Normalize trailing slashes for correct prefix matching
            $normalizedParent = rtrim($normalizedParent, '/') . '/';
            $normalizedBase = rtrim($normalizedBase, '/') . '/';
            
            return str_starts_with($normalizedParent, $normalizedBase);
        }

        $normalizedPath = strtolower(str_replace('\\', '/', $realPath));
        $normalizedBase = strtolower(str_replace('\\', '/', $realBase));
        
        // If exact match
        if ($normalizedPath === rtrim($normalizedBase, '/')) {
            return true;
        }
        
        // Ensure prefix matches with directory boundary to avoid sibling matches
        $normalizedBase = rtrim($normalizedBase, '/') . '/';
        return str_starts_with($normalizedPath, $normalizedBase);
    }
}
