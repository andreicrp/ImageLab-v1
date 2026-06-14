<?php
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/ExportManager.php';
require_once __DIR__ . '/../core/HistoryManager.php';

// Allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

// Get parameters
$filename = $_POST['filename'] ?? '';
$format = $_POST['format'] ?? 'webp';
$quality = isset($_POST['quality']) ? (int)$_POST['quality'] : 80;
$action = $_POST['action'] ?? 'process'; // 'estimate' or 'process'

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

$sourcePath = Config::UPLOAD_PATH . $filename;
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

$exportManager = new ExportManager();

if ($action === 'estimate') {
    $estimatedSize = $exportManager->estimateSize($sourcePath, $format, $quality);
    Response::json([
        'success' => true,
        'estimated_size' => $estimatedSize,
        'formatted_size' => round($estimatedSize / 1024, 2) . ' KB'
    ]);
} elseif ($action === 'process') {
    // Parse background options
    $bgOptions = [];

    // 1. Color Replace
    $oldColor = $_POST['color_replace_old'] ?? '';
    $newColor = $_POST['color_replace_new'] ?? '';
    $fuzz = isset($_POST['color_replace_fuzz']) ? (int)$_POST['color_replace_fuzz'] : 10;
    if (!empty($oldColor) && !empty($newColor)) {
        $bgOptions['color_replace'] = [
            'old_color' => $oldColor,
            'new_color' => $newColor,
            'fuzz' => $fuzz
        ];
    }

    // 2. Solid BG
    $solidBg = $_POST['solid_bg'] ?? '';
    if (!empty($solidBg)) {
        $bgOptions['solid_bg'] = $solidBg;
    }

    // 3. Expand Canvas
    $expandW = isset($_POST['expand_width']) ? (int)$_POST['expand_width'] : 0;
    $expandH = isset($_POST['expand_height']) ? (int)$_POST['expand_height'] : 0;
    $expandBg = $_POST['expand_bg'] ?? '#ffffff';
    $expandGravity = $_POST['expand_gravity'] ?? 'center';
    if ($expandW > 0 && $expandH > 0) {
        $bgOptions['expand_canvas'] = [
            'width' => $expandW,
            'height' => $expandH,
            'bg_color' => $expandBg,
            'gravity' => $expandGravity
        ];
    }

    // Output target path
    $sourceFilename = pathinfo($filename, PATHINFO_FILENAME);
    $targetFilename = $sourceFilename . '_export_' . time() . '.' . $format;
    $targetPath = Config::PROCESSED_PATH . $targetFilename;

    $result = $exportManager->exportImage($sourcePath, $targetPath, $format, $quality, $bgOptions);

    if (!$result) {
        Response::error('Failed to export image.', 500);
    }

    // Save to history log
    $originalSize = filesize($sourcePath);
    $history = new HistoryManager();
    $history->addHistory(
        $filename,
        $targetFilename,
        'Export to ' . strtoupper($format),
        $originalSize,
        $result['size']
    );

    Response::json([
        'success' => true,
        'filename' => $targetFilename,
        'original_size' => $originalSize,
        'new_size' => $result['size'],
        'width' => $result['width'],
        'height' => $result['height'],
        'extension' => $format
    ]);
} else {
    Response::error('Invalid action. Must be estimate or process.', 400);
}
