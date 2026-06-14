<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method.', 405);
}

// Read raw POST body or form-data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// CSRF check
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

if (empty($name) || empty($email) || empty($password)) {
    Response::error('Missing registration parameters.', 400);
}

$auth = new AuthService();
$result = $auth->register($name, $email, $password);

if ($result['success']) {
    Response::success([
        'user_id' => $result['user_id']
    ], $result['message']);
} else {
    Response::error($result['message'], 400);
}
