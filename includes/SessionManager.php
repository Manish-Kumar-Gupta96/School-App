<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Session Manager
 * -------------------------------------------------------------
 */

class SessionManager
{
    private int $idleTimeout;
    private int $absoluteTimeout;
    private bool $secureCookie;

    public function __construct(
        int $idleTimeout = 1800,
        int $absoluteTimeout = 28800,
        bool $secureCookie = false
    ) {
        $this->idleTimeout = $idleTimeout;
        $this->absoluteTimeout = $absoluteTimeout;
        $this->secureCookie = $secureCookie;
    }

    /**
     * -------------------------------------------------------------
     * Start Secure Session
     * -------------------------------------------------------------
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $this->secureCookie ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');

        session_name('VICSESSID');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $this->secureCookie,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);

        session_start();

        $this->initialize();
        $this->validateFingerprint();
        $this->checkIdleTimeout();
        $this->checkAbsoluteTimeout();
        $this->autoRegenerate();
    }

    private function initialize(): void
    {
        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        }
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
        if (!isset($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $this->fingerprint();
        }
        if (!isset($_SESSION['regenerated'])) {
            $_SESSION['regenerated'] = time();
        }
    }

    private function fingerprint(): string
    {
        return hash(
            'sha256',
            ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? '')
        );
    }

    private function validateFingerprint(): void
    {
        if (!hash_equals($_SESSION['fingerprint'], $this->fingerprint())) {
            $this->destroy();
            header('Location: login.php');
            exit;
        }
    }

    private function checkIdleTimeout(): void
    {
        if (time() - $_SESSION['last_activity'] > $this->idleTimeout) {
            $this->destroy();
            header('Location: login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    private function checkAbsoluteTimeout(): void
    {
        if (time() - $_SESSION['created_at'] > $this->absoluteTimeout) {
            $this->destroy();
            header('Location: login.php?expired=1');
            exit;
        }
    }

    private function autoRegenerate(): void
    {
        if (time() - $_SESSION['regenerated'] >= 300) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = time();
        }
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'         => (int)($user['id'] ?? 0),
            'username'   => $user['username'] ?? '',
            'name'       => $user['name'] ?? '',
            'email'      => $user['email'] ?? '',
            'role_id'    => (int)($user['role_id'] ?? 0),
            'role_name'  => $user['role_name'] ?? '',
            'school_id'  => (int)($user['school_id'] ?? 0),
            'photo'      => $user['photo'] ?? '',
            'status'     => $user['status'] ?? 'active'
        ];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['regenerated'] = time();
        $_SESSION['fingerprint'] = $this->fingerprint();
    }

    public function logout(): void
    {
        $this->destroy();
    }

    public function isLoggedIn(): bool
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user']['id']);
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit;
        }
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function userId(): int
    {
        return (int)($_SESSION['user']['id'] ?? 0);
    }

    public function schoolId(): int
    {
        return (int)($_SESSION['user']['school_id'] ?? 0);
    }

    public function role(): string
    {
        return $_SESSION['user']['role_name'] ?? '';
    }

    public function hasRole(string|array $roles): bool
    {
        $currentRole = $this->role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }

    public function requireRole(string|array $roles): void
    {
        if (!$this->hasRole($roles)) {
            http_response_code(403);
            exit('Access Denied');
        }
    }

    public function setPermissions(array $permissions): void
    {
        $_SESSION['permissions'] = $permissions;
    }

    public function permissions(): array
    {
        return $_SESSION['permissions'] ?? [];
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            http_response_code(403);
            exit('Permission Denied');
        }
    }

    public function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public function getFlash(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    public function touch(): void
    {
        $_SESSION['last_activity'] = time();
    }

    public function loginTime(): ?int
    {
        return $_SESSION['login_time'] ?? null;
    }

    public function sessionAge(): int
    {
        return time() - ($_SESSION['created_at'] ?? time());
    }

    public function saveActiveSession(PDO $db): void
    {
        if (!$this->isLoggedIn()) {
            return;
        }

        $sessionId = session_id();
        $userId = $this->userId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $browser = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            $stmt = $db->prepare("
                INSERT INTO active_sessions (user_id, session_id, login_time, last_activity, ip_address, browser)
                VALUES (?, ?, NOW(), NOW(), ?, ?)
                ON DUPLICATE KEY UPDATE last_activity = NOW()
            ");
            $stmt->execute([$userId, $sessionId, $ip, $browser]);
        } catch (Throwable $e) {
            error_log("saveActiveSession failed: " . $e->getMessage());
        }
    }

    public function clearActiveSession(PDO $db): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionId = session_id();
            try {
                $stmt = $db->prepare("DELETE FROM active_sessions WHERE session_id = ?");
                $stmt->execute([$sessionId]);
            } catch (Throwable $e) {
                error_log("clearActiveSession failed: " . $e->getMessage());
            }
        }
    }
}
