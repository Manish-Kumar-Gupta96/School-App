<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$notification = null;

if ($id > 0 && isset($pdo)) {
    try {
        $stmt_read = $pdo->prepare("
            UPDATE notification_recipients 
            SET status = 'READ', read_at = NOW() 
            WHERE notification_id = ? AND user_type = 'ADMIN' AND status = 'PENDING'
        ");
        $stmt_read->execute([$id]);

        $stmt = $pdo->prepare("
            SELECT n.* 
            FROM notifications n
            WHERE n.id = ?
        ");
        $stmt->execute([$id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$page_title = "View Notification | VIC ERP";
$page_header = "Notification Details";
$active_menu = "notifications";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="main-dashboard p-4 text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔔 Notification Details</h2>
            <p class="text-muted mb-0">Read full message details for this notification alert.</p>
        </div>
        <a href="history.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to history
        </a>
    </div>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <?php if ($notification): ?>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; flex-shrink: 0;">
                    <i class="fa fa-bell fs-4"></i>
                </div>
                <div>
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($notification['title']) ?></h4>
                    <span class="text-muted small"><i class="fa fa-clock me-1"></i>Received on: <?= date('d M Y, h:i A', strtotime($notification['created_at'])) ?></span>
                </div>
            </div>
            <hr class="text-muted mt-0 mb-4">
            <div class="py-2">
                <h6 class="fw-bold text-muted mb-2">Message Content:</h6>
                <div class="text-dark bg-light p-4 rounded-3 border" style="font-size: 1.02rem; line-height: 1.6; white-space: pre-wrap;"><?= htmlspecialchars($notification['message']) ?></div>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa fa-exclamation-triangle fs-1 mb-3 text-warning"></i>
                <h5>Notification Not Found</h5>
                <p>The notification details could not be found or you do not have permission to view it.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
