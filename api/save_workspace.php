<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/EditorService.php';

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method. POST required.', 405);
}

$sessionId = $_POST['session_id'] ?? '';
$canvasData = $_POST['canvas_data'] ?? '';

if (empty($sessionId) || empty($canvasData)) {
    Response::error('Missing required parameters: session_id and canvas_data.', 400);
}

$editorService = new EditorService();
$success = $editorService->saveWorkspace($sessionId, $canvasData);

if (!$success) {
    Response::error('Failed to write workspace snapshot to storage.', 500);
}

Response::success([], 'Workspace snapshot saved successfully.');
