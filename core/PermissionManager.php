<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/RoleManager.php';

/**
 * PermissionManager Class
 * Evaluates limits, allowances, and page/feature permissions based on subscription plan and user role.
 */
class PermissionManager {
    /**
     * Check if user is allowed to perform a certain action based on role/subscription limits
     * 
     * @param int|null $userId User ID or null for Guest
     * @param string $action Action name: 'upload', 'ai_request', 'api_access', 'admin_tools'
     * @return bool
     */
    public static function checkPermission(?int $userId, string $action): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }

        // Fetch user role & subscription details
        $role = RoleManager::ROLE_GUEST;
        $plan = 'free';
        $credits = 0;

        if ($userId !== null) {
            $stmt = $pdo->prepare("
                SELECT u.role, s.plan, s.credits 
                FROM users u 
                LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                $role = $user['role'];
                $plan = $user['plan'] ?: 'free';
                $credits = (int)($user['credits'] ?? 0);
            }
        }

        // Admin & Super Admin bypass all checks
        if (RoleManager::isAdmin($role)) {
            return true;
        }

        switch ($action) {
            case 'admin_tools':
                return RoleManager::isAdmin($role);

            case 'api_access':
                // Allowed for Premium roles, Starter, Professional, or Enterprise plans
                return in_array($role, [RoleManager::ROLE_PREMIUM], true) || 
                       in_array($plan, ['starter', 'professional', 'enterprise'], true);

            case 'upload':
                return self::checkUploadLimit($pdo, $userId, $role, $plan);

            case 'ai_request':
                return self::checkAICredits($pdo, $userId, $role, $plan, $credits);

            default:
                return false;
        }
    }

    /**
     * Checks upload usage against plan limits
     */
    private static function checkUploadLimit(PDO $pdo, ?int $userId, string $role, string $plan): bool {
        if ($role === RoleManager::ROLE_GUEST) {
            // Guest limit: Max 5 uploads per day (tracked by IP address in audit/usage logs or session)
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usage_logs 
                WHERE action = 'upload' AND user_id IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            $stmt->execute();
            return (int)$stmt->fetchColumn() < 5;
        }

        if ($plan === 'free') {
            // Free plan limit: Max 20 uploads per day
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usage_logs 
                WHERE user_id = ? AND action = 'upload' AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() < 20;
        }

        if ($plan === 'starter') {
            // Starter plan: Max 500 uploads per month
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usage_logs 
                WHERE user_id = ? AND action = 'upload' AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00')
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() < 500;
        }

        // Professional / Enterprise get unlimited uploads
        return true;
    }

    /**
     * Checks AI credit balance
     */
    private static function checkAICredits(PDO $pdo, ?int $userId, string $role, string $plan, int $credits): bool {
        if ($role === RoleManager::ROLE_GUEST) {
            return false; // Guests do not get AI access
        }

        if ($plan === 'free') {
            // Free plan: 5 AI operations/day max. Tracked daily in usage logs.
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM usage_logs 
                WHERE user_id = ? AND action = 'ai_request' AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() < 5;
        }

        // For Starter and Professional, they have a pool of credits stored in subscriptions table.
        // Credits are decremented when AI actions run. Check if they have credits > 0.
        return $credits > 0;
    }

    /**
     * Checks storage limits (Free: 50MB, Starter: 500MB, Professional: 5GB, Enterprise: 50GB)
     * 
     * @param int $userId
     * @return bool
     */
    public static function checkStorageLimit(int $userId, int $incomingBytes = 0): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }

        // Fetch user role & plan
        $stmt = $pdo->prepare("
            SELECT u.role, s.plan 
            FROM users u 
            LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return false;

        $role = $user['role'];
        $plan = $user['plan'] ?: 'free';

        if (RoleManager::isAdmin($role)) {
            return true; // Admins bypass limits
        }

        // Map limits in bytes
        $limits = [
            'free' => 50 * 1024 * 1024,         // 50 MB
            'starter' => 500 * 1024 * 1024,     // 500 MB
            'professional' => 5 * 1024 * 1024 * 1024, // 5 GB
            'enterprise' => 50 * 1024 * 1024 * 1024  // 50 GB
        ];

        $limitBytes = $limits[$plan] ?? $limits['free'];

        // Get total storage currently used by counting size of all files uploaded by user
        // (We can fetch this from the sum of bytes in usage_logs where action='upload')
        // Or scan their personal directory. Let's calculate from usage_logs.
        $stmt = $pdo->prepare("
            SELECT SUM(bytes) FROM usage_logs 
            WHERE user_id = ? AND action = 'upload'
        ");
        $stmt->execute([$userId]);
        $currentUsed = (int)$stmt->fetchColumn();

        return ($currentUsed + $incomingBytes) <= $limitBytes;
    }
}
