<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method.', 405);
}

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// CSRF check
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

if (empty($token) || empty($password)) {
    Response::error('Reset token and new password are required.', 400);
}

if (strlen($password) < 6) {
    Response::error('Password must be at least 6 characters long.', 400);
}

$pdo = Database::getConnection();
if ($pdo === null) {
    Response::error('Database offline.', 500);
}

// Verify token
$stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    Response::error('Invalid password reset token.', 400);
}

$userId = (int)$user['id'];
$expiry = strtotime($user['reset_token_expires']);

if (time() > $expiry) {
    Response::error('Password reset token has expired.', 400);
}

// Update password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("
    UPDATE users 
    SET password = ?, reset_token = NULL, reset_token_expires = NULL, failed_attempts = 0, lockout_until = NULL 
    WHERE id = ?
");

if ($stmt->execute([$hashedPassword, $userId])) {
    // Audit log
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, ip_address, details) 
        VALUES (?, 'password_reset_success', ?, 'Successfully reset password using token')
    ");
    $stmt->execute([$userId, $ip]);

    Response::success([], 'Password has been reset successfully. You can now login.');
} else {
    Response::error('Failed to reset password. Try again later.', 500);
}
