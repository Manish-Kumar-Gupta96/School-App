<?php

declare(strict_types=1);

/**
 * ==========================================================
 * VIC School ERP Enterprise
 * Remember Me Manager
 * ==========================================================
 */

final class RememberMe
{
    private PDO $db;
    private string $cookieName = 'VIC_REMEMBER';
    private int $expireDays = 30;
    private bool $secure;

    public function __construct(PDO $db, bool $secure = false)
    {
        $this->db = $db;
        $this->secure = $secure;
    }

    /**
     * ==========================================================
     * Create Remember Token
     * ==========================================================
     */
    public function create(int $userId): void
    {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expires = date('Y-m-d H:i:s', strtotime('+' . $this->expireDays . ' days'));

        $stmt = $this->db->prepare("
            INSERT INTO remember_tokens
            (
                user_id,
                selector,
                validator_hash,
                expires_at
            )
            VALUES
            (
                ?,?,?,?
            )
        ");

        $stmt->execute([
            $userId,
            $selector,
            $validatorHash,
            $expires
        ]);

        $cookie = $selector . ':' . $validator;

        setcookie(
            $this->cookieName,
            $cookie,
            [
                'expires' => strtotime($expires),
                'path' => '/',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * ==========================================================
     * Cookie Exists
     * ==========================================================
     */
    public function exists(): bool
    {
        return isset($_COOKIE[$this->cookieName]);
    }

    /**
     * ==========================================================
     * Get Cookie
     * ==========================================================
     */
    public function cookie(): ?string
    {
        return $_COOKIE[$this->cookieName] ?? null;
    }

    /**
     * ==========================================================
     * Delete Cookie
     * ==========================================================
     */
    public function clearCookie(): void
    {
        setcookie(
            $this->cookieName,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $this->secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * ==========================================================
     * Parse Cookie
     * ==========================================================
     */
    private function parseCookie(string $cookie): ?array
    {
        if (strpos($cookie, ':') === false) {
            return null;
        }

        [$selector, $validator] = explode(':', $cookie, 2);

        return [
            'selector' => $selector,
            'validator' => $validator
        ];
    }

    /**
     * ==========================================================
     * Automatic Login
     * ==========================================================
     */
    public function login(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        $cookie = $this->parseCookie($this->cookie());

        if (!$cookie) {
            $this->clearCookie();
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM remember_tokens
            WHERE selector = ?
            LIMIT 1
        ");

        $stmt->execute([
            $cookie['selector']
        ]);

        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$token) {
            $this->clearCookie();
            return null;
        }

        if (strtotime($token['expires_at']) < time()) {
            $this->deleteSelector($cookie['selector']);
            $this->clearCookie();
            return null;
        }

        $validatorHash = hash('sha256', $cookie['validator']);

        if (!hash_equals($token['validator_hash'], $validatorHash)) {
            // Possible replay attack
            $this->deleteUser((int)$token['user_id']);
            $this->clearCookie();
            return null;
        }

        // Rotate Token
        $this->rotate((int)$token['user_id'], $cookie['selector']);

        return (int)$token['user_id'];
    }

    /**
     * ==========================================================
     * Rotate Remember Token
     * ==========================================================
     */
    private function rotate(int $userId, string $oldSelector): void
    {
        $this->deleteSelector($oldSelector);
        $this->create($userId);
    }

    /**
     * ==========================================================
     * Delete Selector
     * ==========================================================
     */
    private function deleteSelector(string $selector): void
    {
        $stmt = $this->db->prepare("
            DELETE
            FROM remember_tokens
            WHERE selector = ?
        ");
        $stmt->execute([$selector]);
    }

    /**
     * ==========================================================
     * Delete User Tokens
     * ==========================================================
     */
    public function deleteUser(int $userId): void
    {
        $stmt = $this->db->prepare("
            DELETE
            FROM remember_tokens
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
    }

    /**
     * ==========================================================
     * Logout Current Device
     * ==========================================================
     */
    public function logoutCurrent(): void
    {
        if (!$this->exists()) {
            return;
        }

        $cookie = $this->parseCookie($this->cookie());

        if ($cookie) {
            $this->deleteSelector($cookie['selector']);
        }

        $this->clearCookie();
    }

    /**
     * ==========================================================
     * Logout All Devices
     * ==========================================================
     */
    public function logoutAll(int $userId): void
    {
        $this->deleteUser($userId);
        $this->clearCookie();
    }

    /**
     * ==========================================================
     * Cleanup Expired Tokens
     * ==========================================================
     */
    public function cleanup(): void
    {
        $this->db->exec("
            DELETE
            FROM remember_tokens
            WHERE expires_at < NOW()
        ");
    }

    /**
     * ==========================================================
     * Count Active Devices
     * ==========================================================
     */
    public function countDevices(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM remember_tokens
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * ==========================================================
     * List Active Remember Devices
     * ==========================================================
     */
    public function devices(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                selector,
                created_at,
                expires_at
            FROM remember_tokens
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
