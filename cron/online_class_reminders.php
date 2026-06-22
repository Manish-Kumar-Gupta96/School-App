<?php
// Set execution configuration
ignore_user_abort(true);
set_time_limit(300);

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../helpers/notification_helper.php');

echo "=== STARTING ONLINE CLASS REMINDERS CRON ===\n";

try {
    // 1. Fetch upcoming active classes in the next 26 hours (and past 1 hour in case cron was delayed)
    $stmt = $pdo->query("
        SELECT oc.*, t.name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_mobile
        FROM online_classes oc
        LEFT JOIN teachers t ON oc.teacher_id = t.id
        WHERE oc.status = 'UPCOMING'
          AND oc.start_time BETWEEN DATE_SUB(NOW(), INTERVAL 1 HOUR) AND DATE_ADD(NOW(), INTERVAL 26 HOUR)
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($classes) . " classes in active reminder windows.\n";

    foreach ($classes as $c) {
        $class_id = (int)$c['id'];
        $subject = $c['subject'] ?: 'General Subject';
        $teacher_name = $c['teacher_name'] ?: 'Faculty';
        $class_name = $c['class_name'];
        $start_time_raw = strtotime($c['start_time']);
        $diff_seconds = $start_time_raw - time();

        echo "Processing Class ID {$class_id} ('{$c['title']}'): Starts in " . round($diff_seconds / 60) . " minutes.\n";

        // Resolve recipients (students & parents)
        $c_name = $class_name;
        $s_name = '';
        if (preg_match('/^([^-]+)-([A-Z])$/', $class_name, $m)) {
            $c_name = $m[1];
            $s_name = $m[2];
        }

        // Fetch students
        $stmt_stud = $pdo->prepare("SELECT id, email, phone AS mobile FROM students WHERE class = ? OR class = ? OR (class = ? AND section = ?)");
        $stmt_stud->execute([$class_name, $c_name, $c_name, $s_name]);
        $students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

        // Fetch parents (using mobile column)
        $stmt_parents = $pdo->prepare("
            SELECT DISTINCT u.id, u.email, p.mobile
            FROM users u
            JOIN parent_student_map psm ON u.id = psm.parent_id
            JOIN students s ON psm.student_id = s.id
            LEFT JOIN parents p ON p.email = u.email
            WHERE s.class = ? OR s.class = ? OR (s.class = ? AND s.section = ?)
        ");
        $stmt_parents->execute([$class_name, $c_name, $c_name, $s_name]);
        $parents = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

        // Compile recipient arrays
        $student_recs = [];
        foreach ($students as $st) {
            $student_recs[] = ['id' => $st['id'], 'type' => 'STUDENT', 'email' => $st['email'], 'mobile' => $st['mobile']];
        }

        $parent_recs = [];
        foreach ($parents as $pr) {
            $parent_recs[] = ['id' => $pr['id'], 'type' => 'PARENT', 'email' => $pr['email'], 'mobile' => $pr['mobile']];
        }

        $teacher_recs = [[
            'id' => $c['teacher_id'],
            'type' => 'TEACHER',
            'email' => $c['teacher_email'],
            'mobile' => $c['teacher_mobile']
        ]];

        // Send Reminders Based on Windows
        // --- 24 Hours Before ---
        if ($diff_seconds <= 86400 && $diff_seconds > 0 && (int)$c['reminder_24h_sent'] === 0) {
            echo "   -> Triggering 24 Hours reminder.\n";
            $title = "⏰ Live Class Reminder: 24 Hours Remaining";
            $time_str = date('h:i A', $start_time_raw);
            $msg_student = "Tomorrow at {$time_str}, {$subject} live class by {$teacher_name}. Please be ready.";
            $msg_parent = "Dear Parent, your child has a scheduled {$subject} live class tomorrow at {$time_str} by {$teacher_name}.";
            
            if (!empty($student_recs)) sendSystemNotification($pdo, $title, $msg_student, $student_recs);
            if (!empty($parent_recs)) sendSystemNotification($pdo, $title, $msg_parent, $parent_recs);
            sendSystemNotification($pdo, $title, "Reminder: You have a scheduled {$subject} live class tomorrow at {$time_str} for {$class_name}.", $teacher_recs);

            $pdo->prepare("UPDATE online_classes SET reminder_24h_sent = 1 WHERE id = ?")->execute([$class_id]);
        }

        // --- 1 Hour Before ---
        if ($diff_seconds <= 3600 && $diff_seconds > 0 && (int)$c['reminder_1h_sent'] === 0) {
            echo "   -> Triggering 1 Hour reminder.\n";
            $title = "⏰ Live Class Starts in 1 Hour";
            $msg_student = "Your {$subject} live class by {$teacher_name} starts in 1 hour. Get ready!";
            $msg_parent = "Dear Parent, child's {$subject} live class by {$teacher_name} starts in 1 hour.";

            if (!empty($student_recs)) sendSystemNotification($pdo, $title, $msg_student, $student_recs);
            if (!empty($parent_recs)) sendSystemNotification($pdo, $title, $msg_parent, $parent_recs);
            sendSystemNotification($pdo, $title, "Reminder: Your {$subject} live class for {$class_name} starts in 1 hour.", $teacher_recs);

            $pdo->prepare("UPDATE online_classes SET reminder_1h_sent = 1 WHERE id = ?")->execute([$class_id]);
        }

        // --- 15 Minutes Before ---
        if ($diff_seconds <= 900 && $diff_seconds > 0 && (int)$c['reminder_15m_sent'] === 0) {
            echo "   -> Triggering 15 Minutes reminder.\n";
            $title = "⏰ Live Class Starts in 15 Minutes";
            $msg_student = "Your {$subject} live class by {$teacher_name} starts in 15 minutes. Please log in.";
            $msg_parent = "Dear Parent, child's {$subject} live class by {$teacher_name} starts in 15 minutes. Ensure they join.";

            if (!empty($student_recs)) sendSystemNotification($pdo, $title, $msg_student, $student_recs);
            if (!empty($parent_recs)) sendSystemNotification($pdo, $title, $msg_parent, $parent_recs);
            sendSystemNotification($pdo, $title, "Reminder: Your {$subject} live class for {$class_name} starts in 15 minutes.", $teacher_recs);

            $pdo->prepare("UPDATE online_classes SET reminder_15m_sent = 1 WHERE id = ?")->execute([$class_id]);
        }

        // --- At Start Time (Join Now) ---
        if ($diff_seconds <= 0 && (int)$c['reminder_start_sent'] === 0) {
            echo "   -> Triggering Start / Join Now reminder.\n";
            $title = "🚀 Live Class Started: Join Now!";
            $msg_student = "Mathematics/Live Class '{$c['title']}' is now live. Click 'Join Class' on your dashboard to join immediately.";
            $msg_parent = "Dear Parent, child's live class '{$c['title']}' has started. Please verify they have joined.";

            if (!empty($student_recs)) sendSystemNotification($pdo, $title, $msg_student, $student_recs);
            if (!empty($parent_recs)) sendSystemNotification($pdo, $title, $msg_parent, $parent_recs);
            sendSystemNotification($pdo, $title, "Your live class '{$c['title']}' is scheduled to start now.", $teacher_recs);

            // Automatically set status to LIVE on start time
            $pdo->prepare("UPDATE online_classes SET status = 'LIVE', reminder_start_sent = 1 WHERE id = ?")->execute([$class_id]);
        }
    }

    echo "=== REMINDERS CRON COMPLETED SUCCESSFULLY ===\n";
} catch (Exception $e) {
    echo "Cron error: " . $e->getMessage() . "\n";
}
?>
