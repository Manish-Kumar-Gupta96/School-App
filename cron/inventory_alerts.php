<?php
// cron/inventory_alerts.php
// Expected to be run by CRON daily at 9:00 AM (e.g., 0 9 * * *)
// This script checks inventory conditions and sends system alerts to admins.

require_once(dirname(__DIR__) . '/config/database.php');
require_once(dirname(__DIR__) . '/includes/notifications.php');

$now = new DateTime();
$today_str = $now->format('Y-m-d');
echo "Running Daily Inventory Sweep at " . $now->format('Y-m-d H:i:s') . "\n";

try {
    // We will target all Admins for these alerts
    $stmt_admins = $pdo->prepare("SELECT id FROM admins WHERE school_id = 1"); // Assuming school_id = 1 for simplicity in CRON
    $stmt_admins->execute();
    $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
    
    $recipients = [];
    foreach($admins as $a) {
        $recipients[] = ['user_type' => 'admin', 'user_id' => $a['id']];
    }
    
    // Fallback if no specific admins found, system might just log it, but we'll queue them
    if (empty($recipients)) {
        $recipients[] = ['user_type' => 'admin', 'user_id' => 1]; // Fallback to Super Admin ID 1
    }

    // 1. Low Stock Check
    $stmt_low = $pdo->prepare("
        SELECT i.item_name, i.item_code, i.quantity, sa.minimum_stock
        FROM inventory_items i
        JOIN stock_alerts sa ON i.id = sa.inventory_item_id
        WHERE i.quantity <= sa.minimum_stock
    ");
    $stmt_low->execute();
    $low_stock = $stmt_low->fetchAll(PDO::FETCH_ASSOC);

    if (count($low_stock) > 0) {
        $msg = "The following items have dropped below their minimum stock thresholds:\n\n";
        foreach($low_stock as $ls) {
            $msg .= "- {$ls['item_name']} ({$ls['item_code']}): {$ls['quantity']} left (Min: {$ls['minimum_stock']})\n";
        }
        sendNotification($pdo, "Low Stock Alert", $msg, "warning", $recipients, 1);
        echo "Low Stock alert dispatched.\n";
    }

    // 2. Overdue Asset Returns
    $stmt_overdue = $pdo->prepare("
        SELECT aa.assigned_to_type, aa.assigned_to_id, aa.expected_return_date, i.item_name, i.item_code
        FROM asset_assignments aa
        JOIN inventory_items i ON aa.inventory_item_id = i.id
        WHERE aa.status = 'issued' AND aa.expected_return_date < ?
    ");
    $stmt_overdue->execute([$today_str]);
    $overdue_assets = $stmt_overdue->fetchAll(PDO::FETCH_ASSOC);

    if (count($overdue_assets) > 0) {
        $msg = "The following assets are overdue for return:\n\n";
        foreach($overdue_assets as $oa) {
            $msg .= "- {$oa['item_name']} ({$oa['item_code']}) was due on {$oa['expected_return_date']}. Assigned to: {$oa['assigned_to_type']} ID {$oa['assigned_to_id']}\n";
        }
        sendNotification($pdo, "Overdue Assets Alert", $msg, "danger", $recipients, 1);
        echo "Overdue Assets alert dispatched.\n";
    }

    // 3. Warranty Expiry (Next 30 Days)
    $thirty_days_later = (clone $now)->modify('+30 days')->format('Y-m-d');
    $stmt_warranty = $pdo->prepare("
        SELECT item_name, item_code, warranty_expiry
        FROM inventory_items
        WHERE warranty_expiry IS NOT NULL AND warranty_expiry BETWEEN ? AND ?
    ");
    $stmt_warranty->execute([$today_str, $thirty_days_later]);
    $expiring_warranty = $stmt_warranty->fetchAll(PDO::FETCH_ASSOC);

    if (count($expiring_warranty) > 0) {
        $msg = "The following assets have warranties expiring in the next 30 days:\n\n";
        foreach($expiring_warranty as $ew) {
            $msg .= "- {$ew['item_name']} ({$ew['item_code']}) expires on {$ew['warranty_expiry']}\n";
        }
        sendNotification($pdo, "Warranty Expiry Alert", $msg, "info", $recipients, 1);
        echo "Warranty Expiry alert dispatched.\n";
    }

    echo "Inventory sweep completed successfully.\n";

} catch (Exception $e) {
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
