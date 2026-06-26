<?php
// cron/update_live_status.php
// Expected to run every minute
require_once(dirname(__DIR__) . '/config/database.php');

$now = date('Y-m-d H:i:s');
echo "Running Live Class Status Update at {$now}\n";

try {
    $pdo->beginTransaction();

    // 1. Mark as LIVE
    $stmt_live = $pdo->prepare("
        UPDATE live_classes 
        SET status = 'live' 
        WHERE status = 'scheduled' 
          AND NOW() BETWEEN start_time AND end_time
    ");
    $stmt_live->execute();
    $live_count = $stmt_live->rowCount();
    echo "- Updated {$live_count} classes to LIVE.\n";

    // 2. Mark as COMPLETED
    $stmt_comp = $pdo->prepare("
        UPDATE live_classes 
        SET status = 'completed' 
        WHERE status IN ('scheduled', 'live') 
          AND NOW() > end_time
    ");
    $stmt_comp->execute();
    $comp_count = $stmt_comp->rowCount();
    echo "- Updated {$comp_count} classes to COMPLETED.\n";

    $pdo->commit();
    echo "Live status sync successful.\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
