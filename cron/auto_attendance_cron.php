<?php
// cron/auto_attendance_cron.php
// Expected to run every hour or after classes end
require_once(dirname(__DIR__) . '/config/database.php');

$now = date('Y-m-d H:i:s');
echo "Running Auto Attendance Calculation at {$now}\n";

try {
    $pdo->beginTransaction();

    // Ensure all duration_minutes are updated for students who forgot to hit 'leave'
    // If leave_time is null, set it to the class end_time if the class is completed
    $stmt_fix = $pdo->prepare("
        UPDATE live_class_attendance lca
        JOIN live_classes lc ON lca.live_class_id = lc.id
        SET 
            lca.leave_time = lc.end_time,
            lca.duration_minutes = TIMESTAMPDIFF(MINUTE, lca.join_time, lc.end_time)
        WHERE lca.leave_time IS NULL 
          AND lc.status = 'completed'
    ");
    $stmt_fix->execute();

    // 1. Present >= 45 minutes
    $stmt_p = $pdo->prepare("
        UPDATE live_class_attendance
        SET attendance_status = 'present'
        WHERE duration_minutes >= 45
    ");
    $stmt_p->execute();
    $p_count = $stmt_p->rowCount();

    // 2. Late 20-44 minutes
    $stmt_l = $pdo->prepare("
        UPDATE live_class_attendance
        SET attendance_status = 'late'
        WHERE duration_minutes BETWEEN 20 AND 44
    ");
    $stmt_l->execute();
    $l_count = $stmt_l->rowCount();

    // 3. Absent < 20 minutes
    $stmt_a = $pdo->prepare("
        UPDATE live_class_attendance
        SET attendance_status = 'absent'
        WHERE duration_minutes < 20
    ");
    $stmt_a->execute();
    $a_count = $stmt_a->rowCount();

    $pdo->commit();

    echo "Calculation completed.\n";
    echo "- Marked {$p_count} as Present.\n";
    echo "- Marked {$l_count} as Late.\n";
    echo "- Marked {$a_count} as Absent.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
}
