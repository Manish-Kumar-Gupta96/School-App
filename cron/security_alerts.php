<?php
// cron/security_alerts.php
// Expected to be run by CRON every hour (e.g., 0 * * * *)
// This script checks for overstayed visitors and unauthorized attempts.

require_once(dirname(__DIR__) . '/config/database.php');
require_once(dirname(__DIR__) . '/includes/notifications.php');

$now = new DateTime();
$current_time = $now->format('Y-m-d H:i:s');
echo "Running Security Sweep at {$current_time}\n";

try {
    // Target all Admins and Security Guards for these alerts
    $recipients = [];
    
    // Admins
    $stmt_admins = $pdo->prepare("SELECT id FROM admins WHERE school_id = 1");
    $stmt_admins->execute();
    $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
    foreach($admins as $a) {
        $recipients[] = ['user_type' => 'admin', 'user_id' => $a['id']];
    }
    
    // Fallback if no specific admins found
    if (empty($recipients)) {
        $recipients[] = ['user_type' => 'admin', 'user_id' => 1];
    }

    // 1. Overstayed Visitors (Gate Pass Expired, but haven't exited)
    $stmt_overstay = $pdo->prepare("
        SELECT gp.gate_pass_no, gp.valid_until, v.visitor_name, v.mobile_no, ve.entry_time
        FROM gate_passes gp
        JOIN visitor_entries ve ON gp.visitor_entry_id = ve.id
        JOIN visitors v ON ve.visitor_id = v.id
        WHERE ve.exit_time IS NULL AND gp.valid_until < ? AND ve.status = 'approved'
    ");
    $stmt_overstay->execute([$current_time]);
    $overstayed = $stmt_overstay->fetchAll(PDO::FETCH_ASSOC);

    if (count($overstayed) > 0) {
        $msg = "ALERT: The following visitors have exceeded their valid gate pass time and have NOT exited campus:\n\n";
        foreach($overstayed as $ov) {
            $msg .= "- {$ov['visitor_name']} ({$ov['mobile_no']}) | Entered: {$ov['entry_time']} | Expired: {$ov['valid_until']}\n";
        }
        sendNotification($pdo, "Security Alert: Overstayed Visitors", $msg, "danger", $recipients, 1);
        echo "Overstayed Visitor alert dispatched.\n";
    }

    // 2. Pending Approvals Waiting Too Long (e.g. > 2 hours)
    $two_hours_ago = (clone $now)->modify('-2 hours')->format('Y-m-d H:i:s');
    $stmt_stale = $pdo->prepare("
        SELECT v.visitor_name, ve.entry_time, ve.purpose
        FROM visitor_entries ve
        JOIN visitors v ON ve.visitor_id = v.id
        WHERE ve.status = 'pending' AND ve.entry_time < ?
    ");
    $stmt_stale->execute([$two_hours_ago]);
    $stale_pending = $stmt_stale->fetchAll(PDO::FETCH_ASSOC);

    if (count($stale_pending) > 0) {
        $msg = "The following visitors have been waiting for approval for more than 2 hours:\n\n";
        foreach($stale_pending as $sp) {
            $msg .= "- {$sp['visitor_name']} | Waiting since: {$sp['entry_time']} | Purpose: {$sp['purpose']}\n";
        }
        sendNotification($pdo, "Security Reminder: Pending Visitors", $msg, "warning", $recipients, 1);
        echo "Stale Pending alert dispatched.\n";
    }

    // 3. Unreturned Students (Early Leave but didn't return if expected)
    // Note: If they took early leave, they usually go home for the day. 
    // We can just log if there are any pending early leave requests that expired.
    
    echo "Security sweep completed successfully.\n";

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
