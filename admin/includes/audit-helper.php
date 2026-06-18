<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function addAuditLog($pdo, $userId, $userName, $roleName, $action, $module, $recordId = null) {
    try {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown CLI/Agent';

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (
                user_id,
                user_name,
                role_name,
                action,
                module_name,
                record_id,
                ip_address,
                user_agent
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $userName,
            $roleName,
            $action,
            $module,
            $recordId,
            $ip_address,
            $user_agent
        ]);
    } catch (Exception $e) {
        error_log("Failed to insert audit log: " . $e->getMessage());
    }
}
