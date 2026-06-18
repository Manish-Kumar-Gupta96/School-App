<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied: Admin authorization required.");
}

try {
    $stmt = $pdo->query("
        DELETE FROM audit_logs
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    
    // Log the prune action itself so there's always a record of cleanup
    require_once('../includes/audit-helper.php');
    addAuditLog(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['name'] ?? 'Admin',
        $_SESSION['role'],
        'Audit logs pruned (older than 1 year deleted)',
        'System'
    );
    
    header("Location: logs.php?deleted=1");
} catch (Exception $e) {
    die("An error occurred while pruning logs: " . $e->getMessage());
}
exit;
