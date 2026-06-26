<?php

function sendNotification(PDO $pdo, string $title, string $message, string $type = 'info', array $recipients = [], int $createdBy = 1)
{
    // Define CURRENT_SCHOOL_ID if it exists, otherwise default to 1
    $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

    try {
        $pdo->beginTransaction();

        // 1. Create the base notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (school_id, title, message, type, created_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $schoolId,
            $title,
            $message,
            $type,
            $createdBy
        ]);
        
        $notificationId = $pdo->lastInsertId();

        // 2. Add recipients (In-App)
        // Expected format of $recipients: [['user_type' => 'student', 'user_id' => 123], ...]
        if (!empty($recipients)) {
            $stmt_rec = $pdo->prepare("
                INSERT INTO notification_recipients (notification_id, user_type, user_id)
                VALUES (?, ?, ?)
            ");
            foreach ($recipients as $rec) {
                $stmt_rec->execute([
                    $notificationId,
                    $rec['user_type'],
                    $rec['user_id']
                ]);
            }
        }

        $pdo->commit();

        // 3. Trigger External Channels (Email, SMS, WhatsApp)
        // Note: In a real system, these would push to a queue or call APIs.
        triggerExternalChannels($title, $message, $recipients);

        return $notificationId;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

function triggerExternalChannels(string $title, string $message, array $recipients)
{
    // Dummy function mimicking external API calls
    // In production, loop through recipients, fetch their phone/email, and send via Twilio/WhatsApp API/SMTP
    
    // Example:
    // foreach($recipients as $rec) {
    //    sendEmail($userEmail, $title, $message);
    //    sendWhatsApp($userPhone, $title, $message);
    // }
}
