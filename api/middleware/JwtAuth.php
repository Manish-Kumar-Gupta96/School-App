<?php
/**
 * Simple JSON Web Token (JWT) Implementation
 * Uses HS256 algorithm.
 */
class JwtAuth {
    
    // Secret key for signing the JWT (in production, load from environment variables)
    private static $secret_key = 'VIC_SCHOOL_ERP_SECRET_KEY_123456789';
    
    /**
     * Generate a JWT token
     */
    public static function generateToken($payload, $expiry = 86400) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + $expiry;
        $payload['iat'] = time();
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secret_key, true);
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Validate and decode a JWT token
     */
    public static function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = $parts[0];
        $payload = $parts[1];
        $signature_provided = $parts[2];
        
        // Re-create the signature
        $signature_calculated = self::base64UrlEncode(hash_hmac('sha256', $header . "." . $payload, self::$secret_key, true));
        
        // Verify signature
        if (!hash_equals($signature_calculated, $signature_provided)) {
            return false;
        }
        
        $decoded_payload = json_decode(self::base64UrlDecode($payload), true);
        
        // Verify expiration
        if (isset($decoded_payload['exp']) && $decoded_payload['exp'] < time()) {
            return false; // Token has expired
        }
        
        return $decoded_payload;
    }
    
    /**
     * Helper middleware to protect API routes
     */
    public static function protectRoute($pdo) {
        $headers = apache_request_headers();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : '');
        
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $token = $matches[1];
            $payload = self::validateToken($token);
            
            if ($payload) {
                // Optional: Check if token is revoked in database
                $stmt = $pdo->prepare("SELECT is_revoked FROM user_tokens WHERE token = ? AND is_revoked = 1");
                $stmt->execute([$token]);
                if ($stmt->fetch()) {
                    self::jsonError("Token has been revoked", 401);
                }
                
                return $payload; // Valid token
            }
        }
        
        self::jsonError("Unauthorized or invalid token", 401);
    }
    
    public static function jsonError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    private static function base64UrlDecode($data) {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
?>
