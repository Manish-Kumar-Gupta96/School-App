<?php

function addAuditLog(PDO $pdo, int $userId, string $module, string $action, string $description)
{
    // Define CURRENT_SCHOOL_ID if it exists, otherwise default to 1
    $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (school_id, user_id, module_name, action, description, ip_address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $schoolId,
        $userId,
        $module,
        $action,
        $description,
        $ipAddress
    ]);
}
