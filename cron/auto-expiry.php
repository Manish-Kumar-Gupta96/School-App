<?php
// Auto Expiry Cron Script
// Target: Can be scheduled to run daily via CLI or web request.

if (php_sapi_name() === 'cli') {
    $root_path = dirname(__DIR__) . "/";
} else {
    $root_path = "../";
}

require_once($root_path . 'config/database.php');

echo "=========================================\n";
echo "SaaS Subscription Expiry Automation Engine\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n";

try {
    // Select all active subscriptions that are past their end date
    $today = date('Y-m-d');
    $stmt_find = $pdo->prepare("SELECT * FROM subscriptions WHERE status = 'ACTIVE' AND end_date < ?");
    $stmt_find->execute([$today]);
    $expired_subs = $stmt_find->fetchAll(PDO::FETCH_ASSOC);

    if (count($expired_subs) > 0) {
        $pdo->beginTransaction();

        $stmt_expire = $pdo->prepare("UPDATE subscriptions SET status = 'EXPIRED' WHERE id = ?");
        $stmt_update_school = $pdo->prepare("UPDATE schools SET subscription_plan = 'Free' WHERE id = ?");

        foreach ($expired_subs as $sub) {
            $stmt_expire->execute([$sub['id']]);
            $stmt_update_school->execute([$sub['school_id']]);
            echo "[EXPIRED] Subscription ID #{$sub['id']} for School ID {$sub['school_id']} has expired.\n";
        }

        $pdo->commit();
        echo "Successfully processed " . count($expired_subs) . " expired subscriptions.\n";
    } else {
        echo "No expired subscriptions found today.\n";
    }
} catch (PDOException $e) {
    echo "[ERROR] Database execution failed: " . $e->getMessage() . "\n";
}

echo "=========================================\n";
?>
