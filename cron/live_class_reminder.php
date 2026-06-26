<?php
// cron/live_class_reminder.php
// Expected to run every minute
require_once(dirname(__DIR__) . '/config/database.php');
require_once(dirname(__DIR__) . '/includes/notifications.php');

$now = date('Y-m-d H:i:s');
echo "Running Live Class 15-Min Reminders at {$now}\n";

try {
    // Find classes scheduled exactly 15 minutes from now
    // Using MySQL TIMESTAMPDIFF to find classes between 14 to 16 minutes away to account for cron slight delays
    $stmt = $pdo->prepare("
        SELECT id, class_id, section_id, title, start_time, meeting_link 
        FROM live_classes 
        WHERE status = 'scheduled' 
          AND TIMESTAMPDIFF(MINUTE, NOW(), start_time) BETWEEN 14 AND 16
    ");
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $notified_count = 0;

    foreach ($classes as $class) {
        // Find all students in this class/section
        $stmt_stu = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND section_id = ?");
        $stmt_stu->execute([$class['class_id'], $class['section_id']]);
        $students = $stmt_stu->fetchAll(PDO::FETCH_ASSOC);

        $recipients = [];
        foreach ($students as $stu) {
            // Add Student
            $recipients[] = ['user_type' => 'student', 'user_id' => $stu['id']];
        }

        // Parent notification queue could also go here if we fetch parent_id from student mapping
        $stmt_par = $pdo->prepare("SELECT parent_id FROM parent_student_map WHERE student_id IN (SELECT id FROM students WHERE class_id = ? AND section_id = ?)");
        $stmt_par->execute([$class['class_id'], $class['section_id']]);
        $parents = $stmt_par->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($parents as $par) {
            $recipients[] = ['user_type' => 'parent', 'user_id' => $par['parent_id']];
        }

        if (count($recipients) > 0) {
            $msg = "Reminder: The Live Class '{$class['title']}' is starting in 15 minutes at " . date('h:i A', strtotime($class['start_time'])) . ". Link: {$class['meeting_link']}";
            
            // Queue Notification via existing notification system
            // In a real app this would insert into notification_queue for SMS/Push/Email
            sendNotification($pdo, "Live Class Starting Soon", $msg, "info", $recipients, 1);
            $notified_count += count($recipients);
            
            // Also explicitly log to notification_queue as per user specs for workers
            foreach ($recipients as $rec) {
                // To avoid duplication, we just insert one push and one web notification
                $stmt_q = $pdo->prepare("INSERT INTO notification_queue (user_id, channel, title, message) VALUES (?, 'push', ?, ?)");
                $stmt_q->execute([$rec['user_id'], "Live Class Starting Soon", $msg]);
            }
        }
    }

    echo "Successfully generated reminders for {$notified_count} recipients across " . count($classes) . " classes.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
