<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/EditorService.php';

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Invalid request method. GET required.', 405);
}

$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    Response::error('Missing required parameter: session_id.', 400);
}

$editorService = new EditorService();
$result = $editorService->loadWorkspace($sessionId);

if ($result === false) {
    Response::error('Workspace session not found or invalid.', 404);
}

Response::success($result, 'Workspace session loaded successfully.');
