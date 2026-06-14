<?php

require_once __DIR__ . '/../../core/Config.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../core/AuthService.php';
require_once __DIR__ . '/../../core/PermissionManager.php';
require_once __DIR__ . '/../../core/AnalyticsService.php';
require_once __DIR__ . '/../../core/UserService.php';

AuthService::startSession();

$user = AuthService::checkAuth();
if (!$user) {
    Response::error('Unauthorized.', 401);
}

$userId = (int)$user['id'];

// Check admin role permission
if (!PermissionManager::checkPermission($userId, 'admin_tools')) {
    Response::error('Access denied. Administrator privileges required.', 403);
}

$analytics = new AnalyticsService();
$userService = new UserService();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'summary';

    if ($action === 'summary') {
        $summary = $analytics->getSystemSummary();
        Response::success($summary, 'Summary stats fetched.');
    } elseif ($action === 'analytics') {
        Response::success([
            'revenue' => $analytics->getRevenueTimeline(),
            'active_users' => $analytics->getActiveUsersTimeline(),
            'storage_growth' => $analytics->getStorageGrowthTimeline(),
            'features' => $analytics->getFeatureUsageBreakdown()
        ], 'System timeline analytics fetched.');
    } elseif ($action === 'users') {
        $usersList = $userService->getAllUsers();
        Response::success($usersList, 'Users list fetched.');
    } else {
        Response::error('Invalid action.', 400);
    }
} elseif ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF
    if (!AuthService::validateCsrfToken($csrfToken)) {
        Response::error('CSRF security check failed.', 403);
    }

    if ($action === 'update_role') {
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $newRole = $_POST['role'] ?? '';
        
        if ($targetUserId <= 0 || empty($newRole)) {
            Response::error('Missing parameters.', 400);
        }

        if ($userService->updateRole($userId, $targetUserId, $newRole)) {
            Response::success([], 'User role updated successfully.');
        } else {
            Response::error('Failed to update user role.', 500);
        }
    } elseif ($action === 'delete_user') {
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if ($targetUserId <= 0) {
            Response::error('Invalid user ID.', 400);
        }

        if ($targetUserId === $userId) {
            Response::error('Cannot delete your own administrator account.', 400);
        }

        if ($userService->deleteUser($userId, $targetUserId)) {
            Response::success([], 'User account deleted successfully.');
        } else {
            Response::error('Failed to delete user.', 500);
        }
    } else {
        Response::error('Invalid action.', 400);
    }
} else {
    Response::error('Invalid method.', 405);
}
