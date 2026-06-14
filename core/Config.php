<?php

// Prepend custom bin folder containing magick/convert wrappers to PATH
$binPath = realpath(__DIR__ . '/../bin');
if ($binPath) {
    putenv("PATH=" . $binPath . PATH_SEPARATOR . getenv('PATH'));
}

/**
 * ImageLab Configuration Class
 */
class Config {
    // Paths relative to project root
    public const UPLOAD_PATH = __DIR__ . '/../uploads/';
    public const PROCESSED_PATH = __DIR__ . '/../processed/';
    public const TEMP_PATH = __DIR__ . '/../temp/';
    public const LOG_PATH = __DIR__ . '/../logs/';

    // Upload & file restrictions
    public const MAX_FILE_SIZE = 10485760; // 10MB in bytes
    public const ALLOWED_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff'];

    // Database configurations (Laragon MySQL Defaults)
    public const DB_HOST = 'localhost';
    public const DB_NAME = 'imagelab';
    public const DB_USER = 'root';
    public const DB_PASS = '';

    // Phase 2 limits for batch actions
    public const MAX_BATCH_FILES = 20;
    public const MAX_BATCH_SIZE = 104857600; // 100MB in bytes
}
