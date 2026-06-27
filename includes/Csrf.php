<?php
class Csrf {
    /**
     * Secure session-bound token generator
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate incoming request tokens with timing-attack safety
     */
    public static function validateToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        
        // hash_equals protects against timing side-channel attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Helper to inject hidden input into forms automatically
     */
    public static function injectInput() {
        $token = self::generateToken();
        echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}
