<?php

require_once __DIR__ . '/Database.php';

/**
 * NotificationService Class
 * Simulates transaction email dispatching (saves log in logs/mail.log) and records DB alerts.
 */
class NotificationService {
    /**
     * Send email notifications (simulated logger)
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email content/template
     * @return bool
     */
    public function sendEmail(string $to, string $subject, string $body): bool {
        $logMsg = sprintf(
            "========================================\nDATE: %s\nTO: %s\nSUBJECT: %s\nBODY:\n%s\n========================================\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $body
        );

        // Append to logs/mail.log
        $mailLogPath = __DIR__ . '/../logs/mail.log';
        return @file_put_contents($mailLogPath, $logMsg, FILE_APPEND) !== false;
    }

    /**
     * Create in-app system alert in database
     */
    public function createSystemAlert(int $userId, string $title, string $message): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, is_read) 
            VALUES (?, 'alert', ?, ?, 0)
        ");
        return $stmt->execute([$userId, $title, $message]);
    }

    /**
     * Fetch unread alerts for a user
     */
    public function getAlerts(int $userId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark alerts as read
     */
    public function markAsRead(int $userId, int $notificationId): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    }
}
