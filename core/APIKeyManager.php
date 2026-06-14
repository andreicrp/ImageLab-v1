<?php

require_once __DIR__ . '/Database.php';

/**
 * APIKeyManager Class
 * Generates, revokes, and authenticates API keys for public REST gateway.
 */
class APIKeyManager {
    /**
     * Create/Register a new API key for a user
     */
    public function generateKey(int $userId, string $name = 'Default Key'): string|bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        // Generate cryptographically secure API key
        $key = 'il_' . bin2hex(random_bytes(24));

        $stmt = $pdo->prepare("
            INSERT INTO api_keys (user_id, api_key, name, status) 
            VALUES (?, ?, ?, 'active')
        ");
        if ($stmt->execute([$userId, $key, $name])) {
            return $key;
        }
        return false;
    }

    /**
     * Get list of keys for a user
     */
    public function getUserKeys(int $userId): array {
        $pdo = Database::getConnection();
        if ($pdo === null) return [];

        $stmt = $pdo->prepare("SELECT id, name, api_key, status, created_at, last_used_at FROM api_keys WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Revoke / Deactivate an API Key
     */
    public function revokeKey(int $userId, int $keyId): bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("UPDATE api_keys SET status = 'revoked' WHERE id = ? AND user_id = ?");
        return $stmt->execute([$keyId, $userId]);
    }

    /**
     * Validate an incoming API key request
     * 
     * @param string $apiKey Raw key string
     * @return array|bool User details array on success, false on failure
     */
    public function validateKey(string $apiKey): array|bool {
        $pdo = Database::getConnection();
        if ($pdo === null) return false;

        $stmt = $pdo->prepare("
            SELECT k.id as key_id, k.user_id, u.role, u.name, u.email 
            FROM api_keys k
            JOIN users u ON k.user_id = u.id
            WHERE k.api_key = ? AND k.status = 'active'
        ");
        $stmt->execute([$apiKey]);
        $result = $stmt->fetch();

        if ($result) {
            // Update last used timestamp
            $stmtUpdate = $pdo->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$result['key_id']]);
            return $result;
        }
        return false;
    }
}
