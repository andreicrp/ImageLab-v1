<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Config.php';

/**
 * AuthService Class
 * Manages user logins, registration, lockouts, password resets, secure session cookies, and CSRF protection.
 */
class AuthService {
    /**
     * Start secure session
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            // Check if HTTPS is active to enforce secure cookies
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            
            session_start([
                'cookie_lifetime' => 86400 * 30, // 30 days remember option max
                'cookie_path' => '/',
                'cookie_secure' => $secure,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
        
        // Generate CSRF token if not set
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Get active CSRF token
     */
    public static function getCsrfToken(): string {
        self::startSession();
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Verify CSRF token from inputs
     */
    public static function validateCsrfToken(?string $token): bool {
        self::startSession();
        if ($token === null || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Register a new user
     */
    public function register(string $name, string $email, string $password): array {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database offline. Try again later.'];
        }

        // Validate formats
        $email = trim(strtolower($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address format.'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email address is already registered.'];
        }

        // Hash password securely
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Verification token
        $verificationToken = bin2hex(random_bytes(32));

        try {
            $pdo->beginTransaction();

            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, email_verified, verification_token) 
                VALUES (?, ?, ?, 'user', 1, ?)
            ");
            $stmt->execute([$name, $email, $hashedPassword, $verificationToken]);
            $userId = (int)$pdo->lastInsertId();

            // Create Free Subscription by default
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (user_id, plan, status, credits) 
                VALUES (?, 'free', 'active', 5)
            ");
            $stmt->execute([$userId]);

            // Add Audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) 
                VALUES (?, 'register', ?, ?, 'New user registration')
            ");
            $stmt->execute([$userId, $ip, $ua]);

            $pdo->commit();

            return [
                'success' => true, 
                'message' => 'Registration successful! You can now log in.',
                'user_id' => $userId,
                'verification_token' => $verificationToken
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user with rate limiting and lockout protection
     */
    public function login(string $email, string $password, bool $rememberMe = false): array {
        self::startSession();
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return ['success' => false, 'message' => 'Database offline. Try again later.'];
        }

        $email = trim(strtolower($email));

        // Fetch user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        $userId = (int)$user['id'];

        // Check Lockout Status
        if ($user['lockout_until'] !== null) {
            $lockoutTime = strtotime($user['lockout_until']);
            if (time() < $lockoutTime) {
                $remSec = $lockoutTime - time();
                return ['success' => false, 'message' => "Account locked due to multiple failed login attempts. Try again in {$remSec} seconds."];
            } else {
                // Lockout expired, reset attempts
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
                $stmt->execute([$userId]);
            }
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            $attempts = (int)$user['failed_attempts'] + 1;
            $lockoutUntil = null;
            if ($attempts >= 5) {
                // Lockout account for 15 minutes
                $lockoutUntil = date('Y-m-d H:i:s', time() + 900);
            }

            $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, lockout_until = ? WHERE id = ?");
            $stmt->execute([$attempts, $lockoutUntil, $userId]);

            // Add failed audit log
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) 
                VALUES (?, 'login_failed', ?, ?, 'Failed login attempt')
            ");
            $stmt->execute([$userId, $ip, $ua]);

            if ($attempts >= 5) {
                return ['success' => false, 'message' => 'Account locked for 15 minutes due to 5 failed login attempts.'];
            }
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Login success: Reset failed attempts
        $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_until = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        // Establish session data
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // Remember Me cookie token mapping
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $userId]);
            setcookie('remember_me', $token, time() + 86400 * 30, '/', '', false, true);
        }

        // Add Audit log
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) 
            VALUES (?, 'login_success', ?, ?, 'Successful login')
        ");
        $stmt->execute([$userId, $ip, $ua]);

        return [
            'success' => true, 
            'user' => [
                'id' => $userId,
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
    }

    /**
     * Check if user is logged in via Session or Auto-login Remember Me cookie
     */
    public static function checkAuth(): ?array {
        self::startSession();
        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role']
            ];
        }

        // Fallback to Remember Me cookie
        if (isset($_COOKIE['remember_me'])) {
            $pdo = Database::getConnection();
            if ($pdo !== null) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
                $stmt->execute([$_COOKIE['remember_me']]);
                $user = $stmt->fetch();
                if ($user) {
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    return [
                        'id' => (int)$user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Log user out
     */
    public function logout(): bool {
        self::startSession();
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $pdo = Database::getConnection();
            if ($pdo !== null) {
                // Clear Remember Me token
                $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = ?");
                $stmt->execute([$userId]);

                // Audit log
                $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, ip_address, user_agent, details) 
                    VALUES (?, 'logout', ?, ?, 'User logged out')
                ");
                $stmt->execute([$userId, $ip, $ua]);
            }
        }

        // Clear session cookies and session files
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        setcookie('remember_me', '', time() - 42000, '/');
        session_destroy();
        return true;
    }
}
