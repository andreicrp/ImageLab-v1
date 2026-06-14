<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';
require_once __DIR__ . '/../../core/APIKeyManager.php';

AuthService::startSession();

$user = AuthService::checkAuth();
if (!$user) {
    Response::error('Unauthorized. Please login.', 401);
}

$userId = (int)$user['id'];
$keyManager = new APIKeyManager();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $keys = $keyManager->getUserKeys($userId);
    Response::success($keys, 'Developer keys fetched.');
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? 'generate';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF
    if (!AuthService::validateCsrfToken($csrfToken)) {
        Response::error('CSRF security check failed.', 403);
    }

    if ($action === 'generate') {
        $name = $_POST['name'] ?? 'Default Key';
        $key = $keyManager->generateKey($userId, $name);
        if ($key) {
            Response::success(['api_key' => $key], 'API Key generated successfully!');
        } else {
            Response::error('Failed to generate key.', 500);
        }
    } elseif ($action === 'revoke') {
        $keyId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($keyId <= 0) {
            Response::error('Invalid Key ID.', 400);
        }
        if ($keyManager->revokeKey($userId, $keyId)) {
            Response::success([], 'API Key revoked successfully.');
        } else {
            Response::error('Failed to revoke API key.', 500);
        }
    } else {
        Response::error('Invalid action.', 400);
    }
} else {
    Response::error('Invalid method.', 405);
}
