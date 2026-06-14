<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/SubscriptionService.php';

/**
 * BillingService Class
 * Simulates PayPal payment collections, generates invoices, and logs billing transactions.
 */
class BillingService {
    /**
     * Process simulated PayPal payment checkout
     * 
     * @param int $userId
     * @param string $plan Plan name ('starter', 'professional', 'enterprise')
     * @param string $mockTxId Mock PayPal transaction ID (e.g. PAYID-123456)
     * @return array Result metadata
     */
    public function processPayPalPayment(int $userId, string $plan, string $mockTxId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database offline.'];
        }

        // Plan pricing
        $prices = [
            'starter' => 9.99,
            'professional' => 29.99,
            'enterprise' => 99.99
        ];

        if (!array_key_exists($plan, $prices)) {
            return ['success' => false, 'message' => 'Invalid plan specified.'];
        }

        $amount = $prices[$plan];

        try {
            $pdo->beginTransaction();

            // 1. Log payment transaction
            $stmt = $pdo->prepare("
                INSERT INTO transactions (user_id, transaction_id, amount, currency, status, provider) 
                VALUES (?, ?, ?, 'USD', 'completed', 'paypal')
            ");
            $stmt->execute([$userId, $mockTxId, $amount]);

            // 2. Generate Invoice
            $invoiceNumber = 'INV-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('Y');
            $tax = round($amount * 0.1, 2); // 10% tax
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices (user_id, invoice_number, amount, tax, status) 
                VALUES (?, ?, ?, ?, 'paid')
            ");
            $stmt->execute([$userId, $invoiceNumber, $amount + $tax, $tax]);

            // 3. Update User Subscription Plan
            $subService = new SubscriptionService();
            if (!$subService->updatePlan($userId, $plan)) {
                throw new Exception("Failed to update subscription details.");
            }

            // 4. Create Notification
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (?, 'system', 'Payment Received', ?)
            ");
            $msg = "We received your payment of \${$amount} for the " . strtoupper($plan) . " plan. Invoice {$invoiceNumber} has been generated.";
            $stmt->execute([$userId, $msg]);

            $pdo->commit();
            return [
                'success' => true, 
                'message' => 'Payment processed and subscription updated!',
                'invoice' => $invoiceNumber
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()];
        }
    }

    /**
     * Get user transactions history
     */
    public function getTransactions(int $userId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get user invoices
     */
    public function getInvoices(int $userId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all invoices (Admin dashboard view)
     */
    public function getAllInvoices(): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->query("
            SELECT i.*, u.name as user_name, u.email as user_email
            FROM invoices i
            JOIN users u ON i.user_id = u.id
            ORDER BY i.id DESC
        ");
        return $stmt->fetchAll();
    }
}
