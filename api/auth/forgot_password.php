<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/NotificationService.php';
require_once __DIR__ . '/../../core/AuthService.php';

AuthService::startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Invalid request method.', 405);
}

$email = $_POST['email'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

// CSRF check
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

if (empty($email)) {
    Response::error('Email address is required.', 400);
}

$email = trim(strtolower($email));
$pdo = Database::getConnection();
if ($pdo === null) {
    Response::error('Database offline.', 500);
}

// Fetch user
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    // Return success message regardless of whether the user exists for privacy protection/security best practices
    Response::success([], 'If that email address is registered, a password reset link has been dispatched.');
}

$userId = (int)$user['id'];
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiry

// Save reset token
$stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
$stmt->execute([$token, $expires, $userId]);

// Send email
$mail = new NotificationService();
$resetUrl = "http://localhost/ImageLab/public/#panel-reset-password&token=" . $token;
$body = "Hi " . $user['name'] . ",\n\nWe received a request to reset your ImageLab password. Click the link below to change it:\n" . $resetUrl . "\n\nIf you did not request this, you can safely ignore this email.\n\nBest,\nImageLab Team";
$mail->sendEmail($email, "Reset your ImageLab Password", $body);

// Audit logging
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$stmt = $pdo->prepare("
    INSERT INTO audit_logs (user_id, action, ip_address, details) 
    VALUES (?, 'password_reset_request', ?, 'Requested password reset token')
");
$stmt->execute([$userId, $ip]);

Response::success([], 'Password reset link has been dispatched to your email.');
