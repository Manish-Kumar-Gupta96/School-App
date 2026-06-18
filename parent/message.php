<?php
require_once('../config/database.php');
$active_menu = "message";
$page_title = "Message Faculty | Parent Portal";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];
$message_log = '';
$error_log = '';

if(isset($_POST['send'])){
    $teacher_id = (int)$_POST['teacher_id'];
    $msg_text   = trim($_POST['message'] ?? '');

    if(empty($msg_text) || !$teacher_id){
        $error_log = "Please select a teacher and enter a valid message.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, message)
            VALUES (?,?,?)
        ");
        $stmt->execute([
            $parent_id,
            $teacher_id,
            $msg_text
        ]);
        $message_log = "Message sent successfully!";
    }
}

// Fetch all teachers in school
$stmt_t = $pdo->prepare("SELECT id, name, subject FROM teachers WHERE school_id=?");
$stmt_t->execute([CURRENT_SCHOOL_ID]);
$teachers = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

// Fetch conversation history
$stmt_msg = $pdo->prepare("
    SELECT m.*, t.name AS teacher_name 
    FROM messages m
    LEFT JOIN teachers t ON m.receiver_id = t.id
    WHERE m.sender_id = ?
    ORDER BY m.id DESC
");
$stmt_msg->execute([$parent_id]);
$msg_history = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">💬 Contact Faculty Teachers</h2>
        <p class="text-muted mb-0">Send messages directly to class mentors or subject faculty.</p>
    </div>
</div>

<?php if($message_log): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message_log) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error_log): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error_log) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 text-start">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
            <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-paper-plane me-2 text-primary"></i>Send New Message</h5>
            <form method="POST" class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Choose Faculty Teacher</label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Select Teacher</option>
                        <?php foreach($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>">
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['subject'] ?: 'Mentor') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Your Message</label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Write your question, comment, or query..." required></textarea>
                </div>

                <div class="col-md-12 mt-4 text-end">
                    <button type="submit" name="send" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa fa-paper-plane me-1"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Logs -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-history me-2 text-secondary"></i>Sent Message History</h5>
            </div>
            <div class="card-body p-4" style="max-height: 480px; overflow-y: auto;">
                <?php if (count($msg_history) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach($msg_history as $msg): ?>
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong class="text-dark">To: <?= htmlspecialchars($msg['teacher_name'] ?: 'Teacher') ?></strong>
                                    <span class="text-muted small"><?= date('d M Y, h:i A', strtotime($msg['created_at'])) ?></span>
                                </div>
                                <p class="text-secondary mb-0 small"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-5 mb-0">No messages sent yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
