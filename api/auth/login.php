<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method.', 405);
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) && ($_POST['remember'] === 'true' || $_POST['remember'] === '1');
$csrfToken = $_POST['csrf_token'] ?? '';

// CSRF check
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

if (empty($email) || empty($password)) {
    Response::error('Email and password are required.', 400);
}

$auth = new AuthService();
$result = $auth->login($email, $password, $remember);

if ($result['success']) {
    Response::success([
        'user' => $result['user']
    ], 'Login successful!');
} else {
    Response::error($result['message'], 401);
}
