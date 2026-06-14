<?php

require_once __DIR__ . '/Database.php';

/**
 * UserService Class
 * Manages user CRUD, status lookups, and administrator dashboards.
 */
class UserService {
    /**
     * Get user profile by ID
     */
    public function getUser(int $userId): ?array {
        $pdo = Database::getConnection();
        if ($pdo === null) return null;

        $stmt = $pdo->prepare("SELECT id, name, email, role, email_verified, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Get list of all users (for Admin dashboard)
     */
    public function getAllUsers(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT u.id, u.name, u.email, u.role, u.email_verified, u.created_at, s.plan, s.status as subscription_status
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Update user role (admin action)
     */
    public function updateRole(int $adminId, int $userId, string $newRole): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        if (!in_array($newRole, ['user', 'premium', 'admin', 'super_admin'], true)) {
            return false;
        }

        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        if ($stmt->execute([$newRole, $userId])) {
            // Audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, ip_address, details) 
                VALUES (?, 'role_change', ?, ?)
            ");
            $stmt->execute([$adminId, $ip, "Changed role of user ID {$userId} to {$newRole}"]);
            return true;
        }
        return false;
    }

    /**
     * Delete a user account (admin action)
     */
    public function deleteUser(int $adminId, int $userId): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$userId])) {
            // Audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, ip_address, details) 
                VALUES (?, 'delete_user', ?, ?)
            ");
            $stmt->execute([$adminId, $ip, "Deleted user ID {$userId}"]);
            return true;
        }
        return false;
    }
}
