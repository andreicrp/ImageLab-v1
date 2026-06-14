<?php
require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/AIService.php';

// Allow POST/GET requests (POST preferred)
$filename = $_REQUEST['filename'] ?? '';

if (empty($filename)) {
    Response::error('Missing required parameter: filename.', 400);
}

// Build paths
$sourcePath = Config::UPLOAD_PATH . $filename;
if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::UPLOAD_PATH)) {
    $sourcePath = Config::PROCESSED_PATH . $filename;
    if (!file_exists($sourcePath) || !Validator::isPathSafe($sourcePath, Config::PROCESSED_PATH)) {
        Response::error('Source file not found or access denied.', 404);
    }
}

$aiService = new AIService();

// 1. Generate Tags
$tagResult = $aiService->generateTags($sourcePath);
$tags = ($tagResult && isset($tagResult['success']) && $tagResult['success']) ? $tagResult['tags'] : ['general'];

// 2. Analyze Quality
$qualityResult = $aiService->analyzeQuality($sourcePath);
$qualityScore = 80;
$metrics = ['sharpness' => 80, 'noise' => 80, 'exposure' => 80, 'resolution' => 80];
$suggestions = [];

if ($qualityResult && isset($qualityResult['success']) && $qualityResult['success']) {
    $qualityScore = $qualityResult['overall_score'];
    $metrics = $qualityResult['metrics'];
    $suggestions = $qualityResult['suggestions'];
}

// 3. Formulate Preset Suggestions based on generated tags and metrics
$presetSuggestions = [];

// Match suggestions from python service
foreach ($suggestions as $s) {
    $presetSuggestions[] = [
        'action' => $s['action'],
        'reason' => $s['reason']
    ];
}

// Additional tags heuristic suggestions
if (in_array('portrait', $tags) && !in_array('Face Enhancement', array_column($presetSuggestions, 'action'))) {
    $presetSuggestions[] = [
        'action' => 'Face Enhancement',
        'reason' => 'Portrait tag detected. Restoring face details will improve clarity.'
    ];
}
if (in_array('product', $tags) && !in_array('Background Removal', array_column($presetSuggestions, 'action'))) {
    $presetSuggestions[] = [
        'action' => 'Background Removal',
        'reason' => 'Product shot layout detected. Transparent background removal is recommended.'
    ];
}
if (in_array('low light', $tags)) {
    $presetSuggestions[] = [
        'action' => 'Auto Enhance / Exposure Correction',
        'reason' => 'Low lighting tags detected. Correcting lighting levels will balance colors.'
    ];
}

Response::json([
    'success' => true,
    'tags' => $tags,
    'quality_score' => $qualityScore,
    'metrics' => $metrics,
    'suggestions' => $presetSuggestions
]);
