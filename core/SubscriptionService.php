<?php

require_once __DIR__ . '/Database.php';

/**
 * SubscriptionService Class
 * Manages SaaS tiers, subscription billing schedules, credits resets, and plan modifications.
 */
class SubscriptionService {
    /**
     * Get active subscription profile for a user
     */
    public function getSubscription(int $userId): ?array {
        $pdo = Database::getConnection();
        if ($pdo === null) return null;

        $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$userId]);
        $sub = $stmt->fetch();
        return $sub ?: null;
    }

    /**
     * Subscribe user to a plan or upgrade existing
     */
    public function updatePlan(int $userId, string $plan): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $plan = strtolower($plan);
        if (!in_array($plan, ['free', 'starter', 'professional', 'enterprise'], true)) {
            return false;
        }

        // Set monthly or daily credits allocations
        $credits = 5; // Default free
        $endsAt = null; // Free has no expiry
        
        if ($plan === 'starter') {
            $credits = 100;
            $endsAt = date('Y-m-d H:i:s', strtotime('+1 month'));
        } elseif ($plan === 'professional') {
            $credits = 1000; // Large pool
            $endsAt = date('Y-m-d H:i:s', strtotime('+1 month'));
        } elseif ($plan === 'enterprise') {
            $credits = 99999;
            $endsAt = date('Y-m-d H:i:s', strtotime('+1 year'));
        }

        $useTransaction = !$pdo->inTransaction();
        try {
            if ($useTransaction) {
                $pdo->beginTransaction();
            }

            // Deactivate previous active subscriptions
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'expired', ends_at = NOW() WHERE user_id = ? AND status = 'active'");
            $stmt->execute([$userId]);

            // Insert new subscription
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (user_id, plan, status, credits, starts_at, ends_at) 
                VALUES (?, ?, 'active', ?, NOW(), ?)
            ");
            $stmt->execute([$userId, $plan, $credits, $endsAt]);

            // Update user role to 'premium' if they bought a paid plan, or revert to 'user' if free
            $newRole = ($plan === 'free') ? 'user' : 'premium';
            
            // Do not demote admins/super_admins!
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $currentRole = $stmt->fetchColumn();

            if ($currentRole === 'user' || $currentRole === 'premium') {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
            }

            // Create default notification alert
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (?, 'system', 'Subscription Updated', ?)
            ");
            $msg = "Your plan has been updated to " . strtoupper($plan) . ". Enjoy your new limits!";
            $stmt->execute([$userId, $msg]);

            // Audit logging
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, ip_address, details) 
                VALUES (?, 'subscription_change', ?, ?)
            ");
            $stmt->execute([$userId, $ip, "Upgraded/Downgraded plan to {$plan}"]);

            if ($useTransaction) {
                $pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($useTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Consume 1 AI credit when user runs an AI job
     */
    public function consumeAICredit(int $userId): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        // Fetch subscription
        $sub = $this->getSubscription($userId);
        if (!$sub) return false;

        // Free plan has daily logs instead of column decrement, but premium consumes stored credits
        if ($sub['plan'] === 'free') {
            return true; // daily count checked in PermissionManager, not decremented here
        }

        if ((int)$sub['credits'] <= 0) {
            return false;
        }

        $stmt = $pdo->prepare("UPDATE subscriptions SET credits = credits - 1 WHERE id = ?");
        return $stmt->execute([$sub['id']]);
    }
}
