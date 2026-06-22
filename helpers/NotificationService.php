<?php

class NotificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Creates a notification in the system, populating both single-table and broker-table formats,
     * and queues async notifications (Email/WhatsApp) in notification_queue.
     */
    public function create(
        int $userId,
        string $userType,
        string $title,
        string $message,
        string $type = 'general',
        ?int $referenceId = null
    ): bool {
        $school_id = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
        $userTypeLower = strtolower($userType);

        try {
            $inTransaction = $this->pdo->inTransaction();
            if (!$inTransaction) {
                $this->pdo->beginTransaction();
            }

            // 1. Insert into notifications table (populating ALL columns for new single-table AND old broker-table compatibility)
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (
                    school_id,
                    user_id,
                    user_type,
                    title,
                    message,
                    type,
                    reference_id,
                    is_read,
                    sender_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)
            ");
            
            // set sender_id to user_id or a system default if needed
            $sender_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

            $stmt->execute([
                $school_id,
                $userId,
                $userTypeLower,
                $title,
                $message,
                $type,
                $referenceId,
                $sender_id
            ]);
            $notification_id = $this->pdo->lastInsertId();

            // 2. Insert into old notification_recipients table for backwards compatibility
            $stmt_rec = $this->pdo->prepare("
                INSERT INTO notification_recipients (
                    notification_id,
                    user_id,
                    user_type,
                    status
                ) VALUES (?, ?, ?, 'PENDING')
            ");
            $stmt_rec->execute([
                $notification_id,
                $userId,
                strtoupper($userType)
            ]);

            // 3. Fetch contact details for async queuing (Email & WhatsApp)
            $recipient_email = '';
            $recipient_phone = '';

            if ($userTypeLower === 'student') {
                $stmt_u = $this->pdo->prepare("SELECT email, phone FROM students WHERE id = ?");
                $stmt_u->execute([$userId]);
                $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $recipient_email = $u['email'] ?? '';
                    $recipient_phone = $u['phone'] ?? '';
                }
            } elseif ($userTypeLower === 'parent') {
                // Get parent details
                $stmt_u = $this->pdo->prepare("
                    SELECT p.email, p.phone 
                    FROM parents p
                    JOIN parent_student_map psm ON p.id = psm.parent_id
                    WHERE psm.parent_id = ? LIMIT 1
                ");
                $stmt_u->execute([$userId]);
                $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $recipient_email = $u['email'] ?? '';
                    $recipient_phone = $u['phone'] ?? '';
                } else {
                    // fallback via students relation
                    $stmt_u = $this->pdo->prepare("SELECT email, phone FROM students WHERE id = (SELECT student_id FROM parent_student_map WHERE parent_id = ? LIMIT 1)");
                    $stmt_u->execute([$userId]);
                    $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                    if ($u) {
                        $recipient_email = $u['email'] ?? '';
                        $recipient_phone = $u['phone'] ?? '';
                    }
                }
            } elseif ($userTypeLower === 'teacher') {
                $stmt_u = $this->pdo->prepare("SELECT email, phone FROM teachers WHERE id = ?");
                $stmt_u->execute([$userId]);
                $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $recipient_email = $u['email'] ?? '';
                    $recipient_phone = $u['phone'] ?? '';
                }
            } elseif ($userTypeLower === 'admin') {
                $stmt_u = $this->pdo->prepare("SELECT email FROM admins WHERE id = ?");
                $stmt_u->execute([$userId]);
                $u = $stmt_u->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $recipient_email = $u['email'] ?? '';
                }
            }

            // 4. Queue Email notification
            if (!empty($recipient_email)) {
                $stmt_q = $this->pdo->prepare("
                    INSERT INTO notification_queue (channel, recipient, subject, message, status)
                    VALUES ('email', ?, ?, ?, 'pending')
                ");
                $stmt_q->execute([$recipient_email, $title, $message]);
            }

            // 5. Queue WhatsApp notification
            if (!empty($recipient_phone)) {
                $clean_phone = preg_replace('/[^0-9]/', '', $recipient_phone);
                if (strlen($clean_phone) === 10) {
                    $clean_phone = '91' . $clean_phone;
                }
                if (!empty($clean_phone)) {
                    $stmt_q = $this->pdo->prepare("
                        INSERT INTO notification_queue (channel, recipient, subject, message, status)
                        VALUES ('whatsapp', ?, NULL, ?, 'pending')
                    ");
                    $stmt_q->execute([$clean_phone, $message]);
                }
            }

            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if (!$inTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Failed to insert/queue notification: " . $e->getMessage());
            return false;
        }
    }
}
?>
