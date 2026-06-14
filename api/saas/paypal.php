<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';
require_once __DIR__ . '/../../core/BillingService.php';
require_once __DIR__ . '/../../core/SubscriptionService.php';

AuthService::startSession();

$user = AuthService::checkAuth();
if (!$user) {
    Response::error('Unauthorized. Please login.', 401);
}

$userId = (int)$user['id'];
$billing = new BillingService();
$subscription = new SubscriptionService();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    Response::error('Invalid request method.', 405);
}

$action = $_POST['action'] ?? 'checkout';
$csrfToken = $_POST['csrf_token'] ?? '';

// Validate CSRF
if (!AuthService::validateCsrfToken($csrfToken)) {
    Response::error('CSRF security check failed.', 403);
}

if ($action === 'checkout') {
    $plan = $_POST['plan'] ?? 'starter';
    
    // Simulate transaction ID from PayPal
    $mockTxId = 'PAYID-' . strtoupper(bin2hex(random_bytes(10)));
    
    $result = $billing->processPayPalPayment($userId, $plan, $mockTxId);
    
    if ($result['success']) {
        Response::success([
            'transaction_id' => $mockTxId,
            'invoice' => $result['invoice']
        ], $result['message']);
    } else {
        Response::error($result['message'], 400);
    }
} elseif ($action === 'cancel') {
    // Cancel subscription, downgrade to free plan
    if ($subscription->updatePlan($userId, 'free')) {
        Response::success([], 'Your subscription was canceled successfully and downgraded to the Free tier.');
    } else {
        Response::error('Failed to cancel subscription.', 500);
    }
} else {
    Response::error('Invalid action.', 400);
}
