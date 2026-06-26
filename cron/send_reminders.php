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

// ==========================
// 3. LIBRARY BOOK DUE & OVERDUE REMINDERS
// ==========================
try {
    // Due tomorrow
    $stmt_due = $pdo->query("
        SELECT li.*, b.title 
        FROM library_issues li
        JOIN library_book_copies c ON li.book_copy_id = c.id
        JOIN library_books b ON c.book_id = b.id
        WHERE li.status IN ('issued') 
          AND DATE(li.due_date) = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    ");
    $due_tomorrow = $stmt_due->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($due_tomorrow) . " books due tomorrow.\n";

    foreach ($due_tomorrow as $due) {
        $user_id = (int)$due['user_id'];
        $user_type = $due['user_type'];
        $title = $due['title'];
        
        $ns->create(
            $user_id,
            $user_type,
            '📚 Library Book Due Tomorrow',
            "Your library book '{$title}' is due tomorrow. Please return it on time to avoid fines.",
            'library_reminder',
            (int)$due['id']
        );
        echo "[REMINDER] Sent due tomorrow alert for book '{$title}' to {$user_type} ID {$user_id}.\n";
    }

    // Overdue
    $stmt_overdue = $pdo->query("
        SELECT li.*, b.title, DATEDIFF(CURDATE(), li.due_date) as days_late 
        FROM library_issues li
        JOIN library_book_copies c ON li.book_copy_id = c.id
        JOIN library_books b ON c.book_id = b.id
        WHERE li.status IN ('issued', 'overdue') 
          AND DATE(li.due_date) < CURDATE()
          AND MOD(DATEDIFF(CURDATE(), li.due_date), 3) = 0 -- Remind every 3 days
    ");
    $overdue = $stmt_overdue->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($overdue) . " books overdue (reminding today).\n";

    foreach ($overdue as $od) {
        $user_id = (int)$od['user_id'];
        $user_type = $od['user_type'];
        $title = $od['title'];
        $days_late = $od['days_late'];
        
        $ns->create(
            $user_id,
            $user_type,
            '⚠️ Library Book Overdue',
            "Your library book '{$title}' is overdue by {$days_late} days. Please return it immediately. Daily fines are being applied.",
            'library_reminder',
            (int)$od['id']
        );
        echo "[REMINDER] Sent overdue alert for book '{$title}' to {$user_type} ID {$user_id}.\n";
    }

} catch (Exception $e) {
    echo "[ERROR] Library reminders query failed: " . $e->getMessage() . "\n";
}

// ==========================
// 4. INVENTORY LOW STOCK ALERTS
// ==========================
try {
    $stmt_stock = $pdo->query("
        SELECT item_name, current_stock, minimum_stock 
        FROM inventory_items 
        WHERE current_stock <= minimum_stock
    ");
    $low_stock = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);

    if (count($low_stock) > 0) {
        // Send a consolidated alert to Admin ID 1
        $item_list = array_map(function($i) { return $i['item_name'] . " (" . $i['current_stock'] . "/" . $i['minimum_stock'] . ")"; }, $low_stock);
        $msg = "Low Stock Alert for " . count($low_stock) . " items: " . implode(", ", $item_list) . ". Please create Purchase Orders.";
        
        $ns->create(1, 'admin', '📉 Low Inventory Stock Alert', $msg, 'inventory_alert', 0);
        echo "[REMINDER] Sent Low Stock Alert to Admin for " . count($low_stock) . " items.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Inventory reminders query failed: " . $e->getMessage() . "\n";
}

// ==========================
// 5. ASSET MAINTENANCE ALERTS
// ==========================
try {
    $stmt_assets = $pdo->query("
        SELECT id, asset_code, asset_name 
        FROM assets 
        WHERE status = 'maintenance'
    ");
    $maintenance_assets = $stmt_assets->fetchAll(PDO::FETCH_ASSOC);

    foreach ($maintenance_assets as $asset) {
        $ns->create(1, 'admin', '🔧 Asset In Maintenance', "Asset {$asset['asset_code']} ({$asset['asset_name']}) is currently in maintenance. Please track repair status.", 'asset_alert', $asset['id']);
        echo "[REMINDER] Sent Maintenance Alert for Asset {$asset['asset_code']}.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Asset reminders query failed: " . $e->getMessage() . "\n";
}

echo "=========================================\n";
?>
