<?php
// Secure Automation Engine — Output header explicitly set to JSON
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

// Authorization Check: Sirf Local Server Cron Call ya logged-in Superadmin hi ise trigger kar sakta hai
if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
        http_response_code(403);
        echo json_encode(['status' => false, 'message' => 'Unauthorized automated endpoint access denied.']);
        exit;
    }
}

try {
    $db = getDBConnection();
    
    // 1. Run Core Query: Jo bhi delegation end_date cross kar chuki hai aur abhi bhi active dikh rahi hai, unhe un-verify karein
    $query = "UPDATE access_delegation 
              SET is_verified = 0 
              WHERE end_date <= NOW() AND is_verified = 1";
              
    $stmt = $db->prepare($query);
    $stmt->execute();
    $expiredCount = $stmt->rowCount();

    // 2. System audit track record log karein agar koi account clean hua hai
    if ($expiredCount > 0) {
        AuditLogger::log('CRON_DELEGATION_CLEANUP', "Automated cron engine cleaned up {$expiredCount} expired time-bound access routes.");
    }

    // Return Success Response Payload
    echo json_encode([
        'status' => true,
        'message' => 'Global access delegation registry cleaned up successfully.',
        'expired_links_removed' => $expiredCount
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Automation Interruption: ' . $e->getMessage()]);
}
?>
