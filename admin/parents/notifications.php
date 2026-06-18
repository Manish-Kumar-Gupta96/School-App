<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
CREATE NOTIFICATION
========================== */
if(isset($_POST['send_notification'])){
    $title        = trim($_POST['title']);
    $msg          = trim($_POST['message']);
    $type         = $_POST['type'];
    $target_class = trim($_POST['target_class']);

    if(empty($title) || empty($msg)){
        $error = "Title and Message are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO parent_notifications(title, message, type, target_class)
            VALUES(?,?,?,?)
        ");
        $stmt->execute([
            $title,
            $msg,
            $type,
            !empty($target_class) ? $target_class : 'All'
        ]);
        $message = "Parent Notification Published Successfully!";
    }
}

/* ==========================
LOAD NOTIFICATIONS
========================== */
$notifications = $pdo->query("
    SELECT *
    FROM parent_notifications
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Parent Notifications | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Publish notice board updates, holiday alerts, and fee reminders to parents</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-outline-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-outline-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="notifications.php" class="btn btn-primary">
            <i class="fa fa-bullhorn me-1"></i> Notifications
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
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

<div class="row">
    <!-- SEND NOTIFICATION -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Publish Alert</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Alert Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Summer Vacation, Fee Pending" required>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Notification Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="General">General Notice</option>
                            <option value="Exam">Exam Schedule</option>
                            <option value="Fee">Fee Reminder</option>
                            <option value="Attendance">Attendance Alert</option>
                            <option value="Holiday">Holiday Declaration</option>
                        </select>
                    </div>

                    <div class="mb-3 text-start">
                        <label class="form-label fw-semibold">Target Class Filter</label>
                        <input type="text" name="target_class" class="form-control" placeholder="e.g. 10-A, 9-B (Leave blank for 'All')">
                    </div>

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">Notice Message <span class="text-danger">*</span></label>
                        <textarea name="message" rows="4" class="form-control" placeholder="Type notification body details..." required></textarea>
                    </div>

                    <button type="submit" name="send_notification" class="btn btn-primary w-100">
                        <i class="fa fa-paper-plane me-1"></i> Send Announcement
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION HISTORY -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start">Announcement Broadcast History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Sent Date</th>
                                <th>Notification Title</th>
                                <th>Type</th>
                                <th>Target Group</th>
                                <th class="pe-4">Message Snippet</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($notifications) > 0): ?>
                                <?php foreach($notifications as $n): ?>
                                    <?php
                                    $typeBadge = match($n['type']){
                                        'Holiday' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'Fee' => 'bg-warning-subtle text-warning border-warning-subtle',
                                        'Exam' => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'Attendance' => 'bg-info-subtle text-info border-info-subtle',
                                        default => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                    };
                                    ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?= date('d M Y, h:i A', strtotime($n['created_at'])) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($n['title']) ?></td>
                                        <td>
                                            <span class="badge border px-3 py-1 <?= $typeBadge ?>">
                                                <?= htmlspecialchars($n['type']) ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($n['target_class'] ?: 'All Classes') ?></span></td>
                                        <td class="pe-4"><span class="text-muted small" title="<?= htmlspecialchars($n['message']) ?>"><?= htmlspecialchars(substr($n['message'], 0, 40)) ?><?= strlen($n['message']) > 40 ? '...' : '' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-bullhorn fs-2 mb-2 d-block"></i>
                                        No announcements broadcasted yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
