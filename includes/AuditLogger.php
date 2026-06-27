<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/BrowserDetector.php';
require_once __DIR__ . '/IpResolver.php';

class AuditLogger {
    /**
     * Dispatch and capture system events safely into relational schemas
     */
    public static function log($actionType, $description) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['user_role'] ?? 'GUEST';
        
        // Resolve target environment properties dynamically
        $ipAddress = IpResolver::resolve();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        $browserProfile = BrowserDetector::detect($userAgent);

        try {
            $db = getDBConnection();
            $query = "INSERT INTO audit_logs (user_id, role, action_type, description, ip_address, browser, user_agent, created_at) 
                      VALUES (:user_id, :role, :action_type, :description, :ip_address, :browser, :user_agent, NOW())";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':user_id'     => $userId,
                ':role'        => $userRole,
                ':action_type' => substr($actionType, 0, 50),
                ':description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8'),
                ':ip_address'  => $ipAddress,
                ':browser'     => $browserProfile,
                ':user_agent'  => substr($userAgent, 0, 255)
            ]);
            return true;
        } catch (PDOException $e) {
            // Fallback fail-safe mechanism: Write directly to filesystem dump if database queries lock up
            $errorDump = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'audit_fallback.log';
            $metaData = sprintf("[%s] DB_FAIL - Action: %s | Description: %s | Error: %s\n", date('Y-m-d H:i:s'), $actionType, $description, $e->getMessage());
            file_put_contents($errorDump, $metaData, FILE_APPEND);
            return false;
        }
    }
}
