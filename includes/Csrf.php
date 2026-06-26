<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * CSRF Protection
 * -------------------------------------------------------------
 */

class Csrf
{
    private const TOKEN_LENGTH = 32;

    public function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateToken(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public function tokenField(): string
    {
        $token = $this->generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public function requireValidToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!$this->validateToken($token)) {
                http_response_code(403);
                die('CSRF token validation failed.');
            }
        }
    }
}
