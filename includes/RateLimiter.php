<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/IpResolver.php';

class RateLimiter {
    /**
     * Check request thresholds dynamically matching target endpoints
     * Example: RateLimiter::check('login', 5, 60); // Max 5 requests per 60 seconds
     */
    public static function check($endpoint, $maxAttempts = 5, $decaySeconds = 60) {
        $ipAddress = IpResolver::resolve();
        $db = getDBConnection();
        $currentTime = time();
        $windowStart = $currentTime - $decaySeconds;

        try {
            // 1. Clean old expired entry rows to maintain optimized dataset bounds
            $cleanStmt = $db->prepare("DELETE FROM rate_limits WHERE endpoint = :ep AND ip_address = :ip AND attempt_time < :ws");
            $cleanStmt->execute([':ep' => $endpoint, ':ip' => $ipAddress, ':ws' => date('Y-m-d H:i:s', $windowStart)]);

            // 2. Count active hits inside the security timeframe window
            $countStmt = $db->prepare("SELECT COUNT(*) FROM rate_limits WHERE endpoint = :ep AND ip_address = :ip AND attempt_time >= :ws");
            $countStmt->execute([':ep' => $endpoint, ':ip' => $ipAddress, ':ws' => date('Y-m-d H:i:s', $windowStart)]);
            $attempts = $countStmt->fetchColumn();

            if ($attempts >= $maxAttempts) {
                http_response_code(429);
                header("Retry-After: " . $decaySeconds);
                die(json_encode([
                    'status' => false, 
                    'message' => "Too many requests. Please slow down and try again after {$decaySeconds} seconds."
                ]));
            }

            // 3. Log current valid attempt into state registries
            $logStmt = $db->prepare("INSERT INTO rate_limits (endpoint, ip_address, attempt_time) VALUES (:ep, :ip, NOW())");
            $logStmt->execute([':ep' => $endpoint, ':ip' => $ipAddress]);
            
            return true;
        } catch (PDOException $e) {
            // Fail-safe logic: if database locks up under severe hits, allow script continuation
            return true;
        }
    }
}
?>
