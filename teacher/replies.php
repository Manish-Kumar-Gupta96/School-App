<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Ticket Chat | Teacher Portal";
$active_menu = "helpdesk";
require_once('includes/header.php');

$teacher_id = $_SESSION['teacher_id'];
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Teacher name
$stmt_tea = $pdo->prepare("SELECT name FROM teachers WHERE id = ?");
$stmt_tea->execute([$teacher_id]);
$teacher = $stmt_tea->fetch(PDO::FETCH_ASSOC);
$teacherName = $teacher['name'] ?? 'Teacher';

// Fetch Ticket and ensure it belongs to the logged in teacher
$stmt = $pdo->prepare("
    SELECT t.*, tc.category_name, a.name as assignee_name
    FROM tickets t
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id
    LEFT JOIN admins a ON t.assigned_to = a.id
    WHERE t.id = ? AND t.user_id = ? AND t.user_type = 'TEACHER'
");
$stmt->execute([$ticket_id, $teacher_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    echo "<div class='alert alert-danger my-4'>Ticket not found or unauthorized access.</div>";
    require_once('includes/footer.php');
    exit;
}

// Handle posting a Reply
if (isset($_POST['post_reply'])) {
    $message_content = trim($_POST['message']);

    if (!empty($message_content)) {
        try {
            $stmt_reply = $pdo->prepare("
                INSERT INTO ticket_replies (ticket_id, user_id, user_type, message)
                VALUES (?, ?, 'TEACHER', ?)
            ");
            $stmt_reply->execute([$ticket_id, $teacher_id, $message_content]);
            
            // Log Audit
            require_once('../admin/includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $teacher_id,
                $teacherName,
                'Teacher',
                'Posted Reply on Ticket #' . $ticket['ticket_no'],
                'Helpdesk',
                $ticket_id
            );
            
            header("Location: replies.php?id=" . $ticket_id);
            exit;
        } catch (Exception $e) {
            error_log("Failed to post reply: " . $e->getMessage());
        }
    }
}

// Fetch all replies
$stmt_replies = $pdo->prepare("
    SELECT tr.*, 
        CASE 
            WHEN tr.user_type = 'ADMIN' THEN adm.name
            WHEN tr.user_type = 'TEACHER' THEN tea.name
            WHEN tr.user_type = 'STUDENT' THEN CONCAT(std.first_name, ' ', std.last_name)
            WHEN tr.user_type = 'PARENT' THEN par.parent_name
        END as sender_name
    FROM ticket_replies tr
    LEFT JOIN admins adm ON tr.user_type = 'ADMIN' AND tr.user_id = adm.id
    LEFT JOIN teachers tea ON tr.user_type = 'TEACHER' AND tr.user_id = tea.id
    LEFT JOIN students std ON tr.user_type = 'STUDENT' AND tr.user_id = std.id
    LEFT JOIN parents par ON tr.user_type = 'PARENT' AND tr.user_id = par.id
    WHERE tr.ticket_id = ?
    ORDER BY tr.id ASC
");
$stmt_replies->execute([$ticket_id]);
$replies = $stmt_replies->fetchAll(PDO::FETCH_ASSOC);

// Fetch ticket attachment
$stmt_attach = $pdo->prepare("SELECT * FROM ticket_attachments WHERE ticket_id = ?");
$stmt_attach->execute([$ticket_id]);
$attachment = $stmt_attach->fetch(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start" style="padding: 20px 0;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">💬 Ticket Conversation: <?= htmlspecialchars($ticket['ticket_no']) ?></h2>
            <p class="text-muted mb-0">Follow updates from the administration regarding this support request thread.</p>
        </div>
        <a href="tickets.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Support List
        </a>
    </div>

    <div class="row g-4">
        <!-- Ticket Info Column -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; height: 100%;">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2 text-primary" style="color: #198754 !important;"><i class="fa fa-info-circle me-2"></i>Ticket Metadata</h5>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Subject</label>
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($ticket['subject']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Category</label>
                    <span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($ticket['category_name'] ?: 'General') ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Priority</label>
                    <span class="badge bg-primary px-2 py-1" style="background-color: #198754 !important;"><?= htmlspecialchars($ticket['priority']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Status</label>
                    <span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><?= htmlspecialchars($ticket['status']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Assigned Representative</label>
                    <span class="text-dark fw-bold"><i class="fa fa-circle-user text-muted me-1"></i> <?= htmlspecialchars($ticket['assignee_name'] ?: 'Awaiting Assignment') ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Description</label>
                    <div class="p-3 bg-light rounded border small text-secondary" style="white-space: pre-wrap;"><?= htmlspecialchars($ticket['description']) ?></div>
                </div>

                <?php if ($attachment): ?>
                    <div class="mb-3">
                        <label class="text-muted small fw-bold uppercase d-block">Attachment</label>
                        <a href="../admin/helpdesk/attachments/<?= htmlspecialchars($attachment['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline-success mt-2">
                            <i class="fa fa-download me-1"></i> View Attachment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chat / Replies Column -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 d-flex flex-column" style="border-radius: 12px; height: 100%; min-height: 500px;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-comments text-warning me-2"></i>Communication History</h5>
                </div>
                
                <!-- Chat messages container -->
                <div class="card-body flex-grow-1 p-4" style="background-color: #fafbfc; max-height: 400px; overflow-y: auto;">
                    <?php if (count($replies) > 0): ?>
                        <?php foreach($replies as $rep): ?>
                            <?php
                            $is_self = $rep['user_type'] === 'TEACHER' && $rep['user_id'] == $teacher_id;
                            ?>
                            <div class="d-flex mb-3 <?= $is_self ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div class="p-3 rounded shadow-sm border" style="max-width: 70%; border-radius: 15px !important; background-color: <?= $is_self ? '#f0fdf4' : '#ffffff' ?>;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small fw-bold <?= $is_self ? 'text-success' : 'text-dark' ?>" style="<?= $is_self ? 'color: #198754 !important;' : '' ?>"><?= htmlspecialchars($rep['sender_name'] ?: 'User') ?></span>
                                        <span class="badge bg-light text-muted border ms-2" style="font-size: 0.65rem;"><?= htmlspecialchars($rep['user_type']) ?></span>
                                    </div>
                                    <div class="text-secondary small" style="white-space: pre-wrap; font-size: 0.9rem;"><?= htmlspecialchars($rep['message']) ?></div>
                                    <div class="text-end text-muted" style="font-size: 0.65rem; margin-top: 4px;"><?= htmlspecialchars($rep['created_at']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa fa-comments fs-1 d-block mb-2 text-secondary"></i>
                            No replies posted yet. Use the form below to respond.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Input form container -->
                <div class="card-footer bg-white border-0 p-4">
                    <?php if ($ticket['status'] !== 'CLOSED'): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="3" placeholder="Compose message to school support staff..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="post_reply" class="btn btn-primary px-4 fw-semibold" style="background-color: #198754; border-color: #198754;">
                                    <i class="fa fa-reply me-1"></i> Send Message
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-secondary text-center mb-0" style="border-radius: 8px;">
                            <i class="fa fa-lock me-1"></i> This ticket is closed. Reply submissions are locked.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
