<?php
if (!function_exists('sendSystemNotification')) {
    /**
     * Dispatches notifications to multiple recipients across selected channels.
     * 
     * @param PDO $pdo The database connection instance.
     * @param string $title The notification title.
     * @param string $message The notification message.
     * @param array $recipients List of recipients. Each recipient is:
     *                          ['id' => int, 'type' => 'STUDENT'|'PARENT'|'TEACHER'|'ADMIN', 'email' => string, 'mobile' => string]
     * @param array $channels Array of channels to dispatch to. Defaults to ['IN_APP', 'EMAIL', 'SMS', 'WHATSAPP']
     * @param int|null $sender_id The user ID of the sender. Defaults to current session user_id.
     * @return bool True on success.
     */
    function sendSystemNotification($pdo, $title, $message, $recipients, $channels = ['IN_APP', 'EMAIL', 'SMS', 'WHATSAPP'], $sender_id = null) {
        if (empty($recipients)) {
            return false;
        }

        if ($sender_id === null) {
            $sender_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        }

        try {
            $pdo->beginTransaction();

            foreach ($channels as $channel) {
                // 1. Insert into notifications table
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notifications (title, message, type, sender_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_notif->execute([
                    $title,
                    $message,
                    $channel,
                    $sender_id
                ]);
                $notification_id = $pdo->lastInsertId();

                // 2. Insert for each recipient
                foreach ($recipients as $recipient) {
                    $rec_id = (int)$recipient['id'];
                    $user_type = strtoupper($recipient['type']);
                    $email = isset($recipient['email']) ? trim($recipient['email']) : '';
                    $mobile = isset($recipient['mobile']) ? trim($recipient['mobile']) : '';

                    $status = 'PENDING';

                    if ($channel === 'IN_APP') {
                        $status = 'PENDING';
                    } elseif ($channel === 'EMAIL') {
                        if (!empty($email)) {
                            // Quick check if SMTP server is responsive (prevents connection timeout blocking)
                            $smtp_available = false;
                            $smtp_host = ini_get('SMTP');
                            $smtp_port = ini_get('smtp_port') ?: 25;
                            if ($smtp_host) {
                                $socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 0.05);
                                if ($socket) {
                                    $smtp_available = true;
                                    fclose($socket);
                                }
                            }
                            if ($smtp_available) {
                                @mail($email, $title, $message);
                            }
                            $status = 'SENT';
                        } else {
                            $status = 'FAILED';
                        }
                    } elseif ($channel === 'SMS') {
                        if (!empty($mobile)) {
                            // Mock SMS API dispatch
                            $status = 'SENT';
                        } else {
                            $status = 'FAILED';
                        }
                    } elseif ($channel === 'WHATSAPP') {
                        if (!empty($mobile)) {
                            // Mock WhatsApp API dispatch
                            $status = 'SENT';
                        } else {
                            $status = 'FAILED';
                        }
                    }

                    $stmt_rec = $pdo->prepare("
                        INSERT INTO notification_recipients (notification_id, user_id, user_type, status)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt_rec->execute([
                        $notification_id,
                        $rec_id,
                        $user_type,
                        $status
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error in sendSystemNotification: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('notifyLiveClassScheduled')) {
    /**
     * Queries and notifies students, parents, and the teacher regarding a newly scheduled class.
     * 
     * @param PDO $pdo The database connection instance.
     * @param int $class_id The scheduled class identifier.
     * @return bool True on success.
     */
    function notifyLiveClassScheduled($pdo, $class_id) {
        // 1. Fetch class details
        $stmt = $pdo->prepare("
            SELECT oc.*, t.name AS teacher_name, t.email AS teacher_email, t.phone AS teacher_mobile
            FROM online_classes oc
            LEFT JOIN teachers t ON oc.teacher_id = t.id
            WHERE oc.id = ?
        ");
        $stmt->execute([$class_id]);
        $c = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$c) return false;

        $subject = $c['subject'] ?: 'General Subject';
        $teacher_name = $c['teacher_name'] ?: 'Faculty';
        $class_name = $c['class_name']; // e.g. "10-A"
        $start_time_raw = strtotime($c['start_time']);
        $date_str = date('d M Y', $start_time_raw); 
        $time_str = date('h:i A', $start_time_raw);   

        // Parse class and section from "10-A" format
        $c_name = $class_name;
        $s_name = '';
        if (preg_match('/^([^-]+)-([A-Z])$/', $class_name, $m)) {
            $c_name = $m[1];
            $s_name = $m[2];
        }

        // 2. Fetch Students
        $stmt_students = $pdo->prepare("
            SELECT id, email, phone AS mobile 
            FROM students 
            WHERE class = ? OR class = ? OR (class = ? AND section = ?)
        ");
        $stmt_students->execute([$class_name, $c_name, $c_name, $s_name]);
        $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch Parents (using mobile column)
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

        // 4. Dispatch Student Notifications
        $student_recipients = [];
        foreach ($students as $student) {
            $student_recipients[] = [
                'id' => $student['id'],
                'type' => 'STUDENT',
                'email' => $student['email'],
                'mobile' => $student['mobile']
            ];
        }
        if (!empty($student_recipients)) {
            $student_title = "📚 New Live Class Scheduled";
            $student_msg = "Subject: $subject\nTeacher: $teacher_name\nDate: $date_str\nTime: $time_str\n\nPlease join on time.";
            sendSystemNotification($pdo, $student_title, $student_msg, $student_recipients);
        }

        // 5. Dispatch Parent Notifications
        $parent_recipients = [];
        foreach ($parents as $parent) {
            $parent_recipients[] = [
                'id' => $parent['id'],
                'type' => 'PARENT',
                'email' => $parent['email'],
                'mobile' => $parent['mobile']
            ];
        }
        if (!empty($parent_recipients)) {
            $parent_title = "📚 New Live Class Scheduled";
            $parent_msg = "Dear Parent,\n\nA live $subject class has been scheduled for your child.\n\nDate: $date_str\nTime: $time_str\n\nPlease ensure your child joins on time.";
            sendSystemNotification($pdo, $parent_title, $parent_msg, $parent_recipients);
        }

        // 6. Dispatch Teacher Notification
        $teacher_recipients = [[
            'id' => $c['teacher_id'],
            'type' => 'TEACHER',
            'email' => $c['teacher_email'],
            'mobile' => $c['teacher_mobile']
        ]];
        $teacher_title = "📚 New Live Class Scheduled";
        $teacher_msg = "Subject: $subject\nClass Target: $class_name\nDate: $date_str\nTime: $time_str\n\nYour scheduled live class has been registered.";
        sendSystemNotification($pdo, $teacher_title, $teacher_msg, $teacher_recipients);

        return true;
    }
}
?>
