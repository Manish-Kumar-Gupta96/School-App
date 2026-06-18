<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Broadcast Alert | Admin Control";
$active_menu = "notifications";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

// Load Templates for dropdown selector
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY template_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Broadcast Submission
if (isset($_POST['broadcast'])) {
    $title = trim($_POST['title']);
    $msg_body = trim($_POST['message']);
    $target_role = $_POST['target_role']; // 'admin', 'teacher', 'parent', 'student'
    $channels = $_POST['channels'] ?? []; // Array of selected channels: IN_APP, EMAIL, SMS, WHATSAPP

    if (empty($title) || empty($msg_body) || empty($target_role) || empty($channels)) {
        $error = "Subject, Message, Target Role, and at least one Broadcast Channel are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert into notifications table for each selected channel
            foreach ($channels as $channel) {
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notifications (title, message, type, sender_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_notif->execute([
                    $title,
                    $msg_body,
                    $channel,
                    $_SESSION['user_id']
                ]);
                $notification_id = $pdo->lastInsertId();

                // 2. Fetch recipients based on target role
                $recipients = [];
                if ($target_role === 'student') {
                    $recipients = $pdo->query("SELECT id, email, phone as mobile FROM students")->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($target_role === 'teacher') {
                    $recipients = $pdo->query("SELECT id, email, phone as mobile FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($target_role === 'parent') {
                    $recipients = $pdo->query("SELECT id, email, mobile FROM parents")->fetchAll(PDO::FETCH_ASSOC);
                } elseif ($target_role === 'admin') {
                    $recipients = $pdo->query("SELECT id, email, '' as mobile FROM admins")->fetchAll(PDO::FETCH_ASSOC);
                }

                // 3. Dispatch & write recipient records
                foreach ($recipients as $recipient) {
                    $rec_id = $recipient['id'];
                    $status = 'PENDING';

                    // Simulate/Perform delivery
                    if ($channel === 'IN_APP') {
                        $status = 'PENDING'; // Display as unread in user portal
                    } elseif ($channel === 'EMAIL' && !empty($recipient['email'])) {
                        // Basic PHP mail wrapper
                        @mail($recipient['email'], $title, $msg_body);
                        $status = 'SENT';
                    } elseif ($channel === 'SMS' && !empty($recipient['mobile'])) {
                        // Twilio or MSG91 mockup entry
                        $status = 'SENT';
                    } elseif ($channel === 'WHATSAPP' && !empty($recipient['mobile'])) {
                        // WhatsApp Cloud API mockup entry
                        $status = 'SENT';
                    } else {
                        $status = 'FAILED';
                    }

                    // Insert record
                    $stmt_rec = $pdo->prepare("
                        INSERT INTO notification_recipients (notification_id, user_id, user_type, status)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt_rec->execute([
                        $notification_id,
                        $rec_id,
                        strtoupper($target_role),
                        $status
                    ]);
                }
            }

            $pdo->commit();
            $message = "Broadcast alerts dispatched successfully to " . count($recipients) . " recipients!";

            // Log Audit trail
            require_once('../includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'],
                'Dispatched Notification Broadcast: ' . $title . ' to ' . ucfirst($target_role) . 's',
                'Notifications'
            );
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to dispatch broadcast: " . $e->getMessage();
        }
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📢 Dispatch Broadcast Alert</h2>
            <p class="text-muted mb-0">Create emergency announcements, fee due notifications, holiday alerts, and dispatch them instantly.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="templates.php" class="btn btn-outline-primary"><i class="fa fa-file-invoice me-1"></i> Templates</a>
            <a href="settings.php" class="btn btn-outline-primary"><i class="fa fa-cogs me-1"></i> Settings</a>
            <a href="history.php" class="btn btn-outline-primary"><i class="fa fa-history me-1"></i> History</a>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card shadow border-0" style="border-radius: 15px;">
                <div class="card-header bg-primary text-white py-3" style="border-radius: 15px 15px 0 0;">
                    <h5 class="fw-bold mb-0"><i class="fa fa-paper-plane me-2"></i>Compose Alert Message</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <!-- Template quick selector using JavaScript -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Load Template Message</label>
                            <select id="templateSelector" class="form-select" onchange="loadTemplate(this.value)">
                                <option value="">Select template to load...</option>
                                <?php foreach($templates as $t): ?>
                                    <option value="<?= $t['id'] ?>" data-title="<?= htmlspecialchars($t['title']) ?>" data-body="<?= htmlspecialchars($tpl_msg = str_replace(array("\r", "\n"), array("", '\n'), $t['message'])) ?>"><?= htmlspecialchars($t['template_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Subject / Title *</label>
                            <input type="text" name="title" id="titleInput" class="form-control" placeholder="e.g. Critical Fee Notice" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Message Text *</label>
                            <textarea name="message" id="messageInput" class="form-control" rows="8" placeholder="Type notification details here..." required></textarea>
                        </div>

                        <div class="row mb-4">
                            <!-- Target Role -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Recipient Role Group *</label>
                                <select name="target_role" class="form-select" required>
                                    <option value="">Select Target Group...</option>
                                    <option value="student">Students</option>
                                    <option value="teacher">Teachers</option>
                                    <option value="parent">Parents</option>
                                    <option value="admin">Admins / Staff</option>
                                </select>
                            </div>

                            <!-- Channels -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Broadcast Channels *</label>
                                <div class="d-flex flex-wrap gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="IN_APP" id="chanInApp" checked>
                                        <label class="form-check-label small" for="chanInApp">In-App Alert</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="EMAIL" id="chanEmail">
                                        <label class="form-check-label small" for="chanEmail">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="SMS" id="chanSMS">
                                        <label class="form-check-label small" for="chanSMS">SMS</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="channels[]" value="WHATSAPP" id="chanWhatsApp">
                                        <label class="form-check-label small" for="chanWhatsApp">WhatsApp</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="broadcast" class="btn btn-success px-5 py-2 fw-semibold">
                                <i class="fa fa-paper-plane me-1"></i> Send Broadcast
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadTemplate(tplId) {
    if (!tplId) {
        document.getElementById('titleInput').value = '';
        document.getElementById('messageInput').value = '';
        return;
    }
    const select = document.getElementById('templateSelector');
    const option = select.options[select.selectedIndex];
    
    const title = option.getAttribute('data-title');
    const body = option.getAttribute('data-body');
    
    document.getElementById('titleInput').value = title;
    // Replace the javascript escaped \n strings back to real newlines
    document.getElementById('messageInput').value = body.replace(/\\n/g, "\n");
}
</script>
