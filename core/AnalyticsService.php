<?php

require_once __DIR__ . '/Database.php';

/**
 * AnalyticsService Class
 * Computes platform analytics: revenue, user trends, storage growth, and feature usage.
 */
class AnalyticsService {
    /**
     * Fetch platform summary stats (for Admin dashboard cards)
     */
    public function getSystemSummary(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return ['users' => 0, 'revenue' => 0, 'storage' => 0, 'jobs' => 0];
        }

        // Total users
        $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
        $totalUsers = (int)$stmtUsers->fetchColumn();

        // Total revenue (paid invoices sum)
        $stmtRev = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status = 'paid'");
        $totalRevenue = (float)$stmtRev->fetchColumn() ?: 0.00;

        // Total storage used
        $stmtStorage = $pdo->query("SELECT SUM(bytes) FROM usage_logs WHERE action = 'upload'");
        $totalStorage = (int)$stmtStorage->fetchColumn() ?: 0;

        // Total AI requests
        $stmtAI = $pdo->query("SELECT COUNT(*) FROM usage_logs WHERE action = 'ai_request'");
        $totalAI = (int)$stmtAI->fetchColumn();

        return [
            'users' => $totalUsers,
            'revenue' => $totalRevenue,
            'storage' => $totalStorage,
            'ai_requests' => $totalAI
        ];
    }

    /**
     * Get monthly revenue breakdown
     */
    public function getRevenueTimeline(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT DATE_FORMAT(billing_date, '%Y-%m') as month, SUM(amount) as revenue
            FROM invoices
            WHERE status = 'paid'
            GROUP BY month
            ORDER BY month ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get most used features breakdown
     */
    public function getFeatureUsageBreakdown(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT action, COUNT(*) as count
            FROM usage_logs
            GROUP BY action
            ORDER BY count DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get daily active users timeline (unique login successes)
     */
    public function getActiveUsersTimeline(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT DATE(created_at) as date, COUNT(DISTINCT user_id) as active_users
            FROM audit_logs
            WHERE action = 'login_success'
            GROUP BY date
            ORDER BY date ASC
            LIMIT 30
        ");
        return $stmt->fetchAll();
    }

    /**
     * Get storage growth timeline
     */
    public function getStorageGrowthTimeline(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT DATE(created_at) as date, SUM(bytes) as added_bytes
            FROM usage_logs
            WHERE action = 'upload'
            GROUP BY date
            ORDER BY date ASC
            LIMIT 30
        ");
        return $stmt->fetchAll();
    }
}
