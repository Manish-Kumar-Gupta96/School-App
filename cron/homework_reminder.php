<?php
// cron/homework_reminder.php
// Expected to be run by CRON every 15 minutes.
// e.g., */15 * * * * php /path/to/project/cron/homework_reminder.php

require_once(dirname(__DIR__) . '/config/database.php');
require_once(dirname(__DIR__) . '/includes/notifications.php');

$now = new DateTime();
$today_str = $now->format('Y-m-d');

// We track if a reminder was already sent to avoid spamming.
// Since we don't have a specific `assignment_reminders_log` table,
// we will rely on checking if it's "morning" (e.g., between 8 AM and 9 AM)
// for the "Due Today" reminder, and only running this script once a day for the 3-day and 1-day reminders,
// OR we can just execute the logic safely assuming it runs daily.
// But the prompt says "Runs every: 15 Minutes".
// To prevent 15-min spam, we'd normally need a log table.
// As a workaround, we will check if the current hour is exactly 08:00 to 08:14.
// If the cron runs every 15 mins, it will hit exactly ONCE during this window.

$current_hour = (int)$now->format('H');
$current_minute = (int)$now->format('i');

// Only process reminders between 08:00 and 08:14 to guarantee exactly 1 execution per day
// even if the CRON runs every 15 minutes.
if ($current_hour === 8 && $current_minute < 15) {

    echo "Running Daily Homework Reminder Sweep at " . $now->format('Y-m-d H:i:s') . "\n";

    // 1. Due Today Morning
    $stmt_today = $pdo->prepare("SELECT id, title, class_id, section_id FROM assignments WHERE due_date = ?");
    $stmt_today->execute([$today_str]);
    $due_today = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

    // 2. 1 Day Before
    $date_1_day = (clone $now)->modify('+1 day')->format('Y-m-d');
    $stmt_1d = $pdo->prepare("SELECT id, title, class_id, section_id FROM assignments WHERE due_date = ?");
    $stmt_1d->execute([$date_1_day]);
    $due_1_day = $stmt_1d->fetchAll(PDO::FETCH_ASSOC);

    // 3. 3 Days Before
    $date_3_days = (clone $now)->modify('+3 days')->format('Y-m-d');
    $stmt_3d = $pdo->prepare("SELECT id, title, class_id, section_id FROM assignments WHERE due_date = ?");
    $stmt_3d->execute([$date_3_days]);
    $due_3_days = $stmt_3d->fetchAll(PDO::FETCH_ASSOC);

    function notifyAssignmentGroup($pdo, $assignments, $prefix_msg) {
        foreach ($assignments as $a) {
            // Find students in this class/section who haven't submitted yet
            $stmt_missing = $pdo->prepare("
                SELECT s.id 
                FROM students s
                LEFT JOIN assignment_submissions sub 
                  ON s.id = sub.student_id AND sub.assignment_id = ?
                WHERE s.class_id = ? AND s.section_id = ? AND sub.id IS NULL
            ");
            $stmt_missing->execute([$a['id'], $a['class_id'], $a['section_id']]);
            $missing_students = $stmt_missing->fetchAll(PDO::FETCH_ASSOC);

            if (count($missing_students) > 0) {
                $recipients = [];
                foreach ($missing_students as $ms) {
                    $recipients[] = ['user_type' => 'student', 'user_id' => $ms['id']];
                    $recipients[] = ['user_type' => 'parent', 'user_id' => $ms['id']];
                }

                sendNotification(
                    $pdo,
                    "Homework Reminder",
                    "{$prefix_msg}: {$a['title']}",
                    "warning",
                    $recipients,
                    1 // Admin/System Sender ID
                );
                echo "Sent '{$prefix_msg}' reminder for assignment ID {$a['id']} to " . count($missing_students) . " students.\n";
            }
        }
    }

    notifyAssignmentGroup($pdo, $due_today, "DUE TODAY");
    notifyAssignmentGroup($pdo, $due_1_day, "Due Tomorrow");
    notifyAssignmentGroup($pdo, $due_3_days, "Due in 3 Days");

    echo "Sweep complete.\n";
} else {
    echo "Skipping... Reminder sweep only runs between 08:00 and 08:14.\n";
}
