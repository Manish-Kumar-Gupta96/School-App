<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "My Notifications | Student Portal";
$active_menu = "notifications";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Handle Mark as Read
if (isset($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    $stmt = $pdo->prepare("
        UPDATE notification_recipients
        SET status = 'READ', read_at = NOW()
        WHERE id = ? AND user_id = ? AND user_type = 'STUDENT'
    ");
    $stmt->execute([$read_id, $student_id]);
    header("Location: notifications.php");
    exit;
}

// Fetch Student Notifications
$stmt = $pdo->prepare("
    SELECT r.id as recipient_id, r.status, n.title, n.message, n.created_at
    FROM notification_recipients r
    JOIN notifications n ON r.notification_id = n.id
    WHERE r.user_id = ? AND r.user_type = 'STUDENT'
    ORDER BY n.id DESC
");
$stmt->execute([$student_id]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start" style="padding: 20px 0;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔔 My Notifications</h2>
            <p class="text-muted mb-0">View all school alerts, broadcast announcements, and exam notices.</p>
        </div>
    </div>

    <!-- NOTIFICATION CARDS -->
    <div class="row">
        <div class="col-xl-8">
            <?php if (count($notifs) > 0): ?>
                <?php foreach($notifs as $n): ?>
                    <div class="card shadow-sm border-0 mb-3 p-4 <?= $n['status'] === 'PENDING' ? 'border-start border-primary border-4 bg-primary-subtle' : 'bg-white' ?>" style="border-radius: 12px; transition: all 0.3s ease;">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($n['title']) ?></h5>
                                <div class="text-secondary mb-2" style="white-space: pre-wrap; font-size: 0.95rem;"><?= htmlspecialchars($n['message']) ?></div>
                                <span class="text-muted small"><i class="fa fa-clock me-1"></i> Received: <?= htmlspecialchars($n['created_at']) ?></span>
                            </div>
                            <?php if ($n['status'] === 'PENDING'): ?>
                                <a href="?read_id=<?= $n['recipient_id'] ?>" class="btn btn-sm btn-primary px-3 rounded-pill fw-semibold">
                                    <i class="fa fa-check me-1"></i> Mark Read
                                </a>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fa fa-envelope-open me-1"></i> Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-info py-5 text-center shadow border-0" style="border-radius: 12px;">
                    <i class="fa fa-bell-slash fs-1 text-secondary mb-3"></i>
                    <h5>No Notifications Yet</h5>
                    <p class="text-muted mb-0">All clear! You do not have any active notifications or alerts.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
