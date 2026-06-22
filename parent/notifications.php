<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Child Notifications | Parent Portal";
$active_menu = "notifications";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];

// Handle Mark as Read
if (isset($_GET['id']) || isset($_GET['read_id'])) {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $read_id = isset($_GET['read_id']) ? (int)$_GET['read_id'] : 0;

    try {
        $pdo->beginTransaction();
        
        if ($id > 0) {
            // Update single table status
            $stmt = $pdo->prepare("
                UPDATE notifications
                SET is_read = 1
                WHERE id = ? AND user_id = ? AND user_type = 'parent'
            ");
            $stmt->execute([$id, $parent_id]);

            // Update broker table status
            $stmt2 = $pdo->prepare("
                UPDATE notification_recipients
                SET status = 'READ', read_at = NOW()
                WHERE notification_id = ? AND user_id = ? AND user_type = 'PARENT'
            ");
            $stmt2->execute([$id, $parent_id]);
        }

        if ($read_id > 0) {
            // Update broker table status by recipient ID
            $stmt_rec = $pdo->prepare("
                UPDATE notification_recipients
                SET status = 'READ', read_at = NOW()
                WHERE id = ? AND user_id = ? AND user_type = 'PARENT'
            ");
            $stmt_rec->execute([$read_id, $parent_id]);

            // Sync back to notifications table
            $stmt_sync = $pdo->prepare("
                UPDATE notifications n
                JOIN notification_recipients r ON r.notification_id = n.id
                SET n.is_read = 1
                WHERE r.id = ? AND n.user_id = ?
            ");
            $stmt_sync->execute([$read_id, $parent_id]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
    header("Location: notifications.php");
    exit;
}

// Fetch Parent Notifications from either notifications directly (single-table style) OR notification_recipients (broker-table style)
$stmt = $pdo->prepare("
    SELECT n.id, n.title, n.message, n.created_at, 
           COALESCE(r.id, 0) AS recipient_id,
           COALESCE(r.status, IF(n.is_read=1, 'READ', 'PENDING')) AS status
    FROM notifications n
    LEFT JOIN notification_recipients r ON r.notification_id = n.id AND r.user_id = ? AND r.user_type = 'PARENT'
    WHERE (n.user_id = ? AND n.user_type = 'parent') 
       OR (r.user_id = ? AND r.user_type = 'PARENT')
    ORDER BY n.id DESC
");
$stmt->execute([$parent_id, $parent_id, $parent_id]);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start" style="padding: 20px 0;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔔 Family Alerts & Notifications</h2>
            <p class="text-muted mb-0">Stay updated with academic bulletins, holiday schedules, and student remarks.</p>
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
                                <a href="?id=<?= $n['id'] ?>" class="btn btn-sm btn-primary px-3 rounded-pill fw-semibold">
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
                    <p class="text-muted mb-0">All clear! You do not have any active alerts for your child.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
