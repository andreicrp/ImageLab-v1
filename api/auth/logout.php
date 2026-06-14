<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method.', 405);
}

$csrfToken = $_POST['csrf_token'] ?? '';

// CSRF check
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

$auth = new AuthService();
if ($auth->logout()) {
    Response::success([], 'Logged out successfully.');
} else {
    Response::error('Logout failed.', 500);
}
