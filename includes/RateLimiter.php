<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Rate Limiter
 * -------------------------------------------------------------
 */

class RateLimiter
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function recordFailedLogin(string $username, string $ip, string $browser): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO failed_logins (username, ip_address, attempt_time, browser)
                VALUES (?, ?, NOW(), ?)
            ");
            $stmt->execute([$username, $ip, $browser]);
        } catch (Throwable $e) {
            error_log("Failed to record login attempt: " . $e->getMessage());
        }
    }

    public function getAttempts(string $username, int $minutes = 15): int
    {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM failed_logins 
                WHERE username = ? AND attempt_time >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$username, $minutes]);
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log("Failed to get login attempts: " . $e->getMessage());
            return 0;
        }
    }

    public function isBlocked(string $username, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return $this->getAttempts($username, $minutes) >= $maxAttempts;
    }
}
