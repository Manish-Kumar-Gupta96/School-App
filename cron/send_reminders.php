<?php
// Scheduled Reminder Cron Engine
ignore_user_abort(true);
set_time_limit(300);

if (php_sapi_name() === 'cli') {
    $root_path = dirname(__DIR__) . "/";
} else {
    $root_path = "../";
}

require_once($root_path . 'config/database.php');
require_once($root_path . 'app/Services/NotificationService.php');

echo "=========================================\n";
echo "VIC ERP Unified Scheduled Reminders Engine\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "=========================================\n";

$ns = new NotificationService($pdo);

// ==========================
// 1. LIVE CLASS REMINDERS (15 MIN WINDOW)
// ==========================
try {
    // Select upcoming classes starting in the next 15 minutes that haven't sent reminders yet
    $stmt_classes = $pdo->query("
        SELECT oc.*, t.name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_mobile
        FROM online_classes oc
        LEFT JOIN teachers t ON oc.teacher_id = t.id
        WHERE oc.status IN ('scheduled', 'UPCOMING')
          AND oc.reminder_15m_sent = 0
          AND oc.start_time BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 MINUTE)
    ");
    $upcoming_classes = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($upcoming_classes) . " classes starting in the next 15 minutes.\n";

    foreach ($upcoming_classes as $c) {
        $class_id = (int)$c['id'];
        $subject = $c['subject'] ?: 'Subject';
        $teacher_name = $c['teacher_name'] ?: 'Faculty';
        $class_name = $c['class_name'] ?: 'Class';
        $start_time_raw = strtotime($c['start_time']);
        $time_str = date('h:i A', $start_time_raw);

        // Resolve class targets
        $c_name = $class_name;
        $s_name = '';
        if (preg_match('/^([^-]+)-([A-Z])$/', $class_name, $m)) {
            $c_name = $m[1];
            $s_name = $m[2];
        }

        // Fetch students
        $stmt_stud = $pdo->prepare("SELECT id FROM students WHERE class = ? OR class = ? OR (class = ? AND section = ?)");
        $stmt_stud->execute([$class_name, $c_name, $c_name, $s_name]);
        $students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

        // Fetch parents
        $stmt_parents = $pdo->prepare("
            SELECT DISTINCT u.id
            FROM users u
            JOIN parent_student_map psm ON u.id = psm.parent_id
            JOIN students s ON psm.student_id = s.id
            WHERE s.class = ? OR s.class = ? OR (s.class = ? AND s.section = ?)
        ");
        $stmt_parents->execute([$class_name, $c_name, $c_name, $s_name]);
        $parents = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

        // 1. Notify Students
        $student_title = "⏰ Live Class Starts in 15 Mins";
        $student_msg = "Your {$subject} class with {$teacher_name} starts at {$time_str} (in 15 mins). Please join on time.";
        foreach ($students as $st) {
            $ns->create((int)$st['id'], 'student', $student_title, $student_msg, 'live_class', $class_id);
        }

        // 2. Notify Parents
        $parent_title = "⏰ Child Live Class Starts in 15 Mins";
        $parent_msg = "Dear Parent, child's {$subject} class with {$teacher_name} starts at {$time_str}. Ensure they join on time.";
        foreach ($parents as $pr) {
            $ns->create((int)$pr['id'], 'parent', $parent_title, $parent_msg, 'live_class', $class_id);
        }

        // 3. Notify Teacher
        $teacher_title = "⏰ Live Class Starting in 15 Mins";
        $teacher_msg = "Your scheduled {$subject} class for {$class_name} starts at {$time_str} (in 15 mins). Please join and host the meeting.";
        $ns->create((int)$c['teacher_id'], 'teacher', $teacher_title, $teacher_msg, 'live_class', $class_id);

        // Mark 15m reminder as sent
        $pdo->prepare("UPDATE online_classes SET reminder_15m_sent = 1 WHERE id = ?")->execute([$class_id]);
        echo "[REMINDER] Sent 15m alerts for class ID #{$class_id}.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Live class reminders query failed: " . $e->getMessage() . "\n";
}

// ==========================
// 2. ADMISSION CRM DAILY FOLLOW-UP ALERTS
// ==========================
try {
    // Select lead followups scheduled for today
    $stmt_followups = $pdo->query("
        SELECT f.*, l.student_name, l.parent_name, l.assigned_to 
        FROM crm_followups f
        JOIN crm_leads l ON f.lead_id = l.id
        WHERE DATE(f.next_followup) = CURDATE()
           OR DATE(f.next_followup_date) = CURDATE()
    ");
    $upcoming_followups = $stmt_followups->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($upcoming_followups) . " follow-up schedules due today.\n";

    foreach ($upcoming_followups as $f) {
        $staff_id = $f['staff_id'] ?: $f['assigned_to'] ?: 1; // Fallback to admin #1
        $lead_name = $f['student_name'] ?: 'Prospect Student';
        
        $ns->create(
            (int)$staff_id,
            'admin',
            '📞 Follow-up Due Today',
            "Admission follow-up is due today for prospect lead: {$lead_name}.",
            'crm_followup',
            (int)$f['lead_id']
        );
        echo "[REMINDER] Created follow-up alert for Lead ID #{$f['lead_id']} assigned to User ID {$staff_id}.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] CRM followups reminders query failed: " . $e->getMessage() . "\n";
}

echo "=========================================\n";
?>
