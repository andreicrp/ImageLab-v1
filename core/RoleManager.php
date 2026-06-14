<?php

/**
 * RoleManager Class
 * Defines roles and mapping for user access control.
 */
class RoleManager {
    public const ROLE_GUEST = 'guest';
    public const ROLE_USER = 'user';
    public const ROLE_PREMIUM = 'premium';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Get list of roles in order of hierarchy/privilege
     */
    public static function getRoles(): array {
        return [
            self::ROLE_GUEST,
            self::ROLE_USER,
            self::ROLE_PREMIUM,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN
        ];
    }

    /**
     * Check if a role has admin privileges
     */
    public static function isAdmin(string $role): bool {
        return in_array($role, [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN], true);
    }
}
