<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle status change simulation
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $act = $_GET['action'];
    $status = 'UPCOMING';
    if ($act === 'start') {
        $status = 'LIVE';
        $success = 'Class has been set to LIVE status. Students notified.';
    } else if ($act === 'complete') {
        $status = 'COMPLETED';
        $success = 'Class marked as completed.';
    } else if ($act === 'cancel') {
        try {
            $stmt = $pdo->prepare("DELETE FROM online_classes WHERE id = ?");
            $stmt->execute([id]);
            $success = 'Class session cancelled and removed.';
        } catch (PDOException $e) {
            $error = 'Error deleting session: ' . $e->getMessage();
        }
    }

    if ($act === 'start' || $act === 'complete') {
        try {
            $stmt = $pdo->prepare("UPDATE online_classes SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
        } catch (PDOException $e) {
            $error = 'Error updating status: ' . $e->getMessage();
        }
    }
}

// Fetch Classes
$classes = [];
try {
    $classes = $pdo->query("
        SELECT oc.*, t.name AS teacher_name 
        FROM online_classes oc
        LEFT JOIN teachers t ON oc.teacher_id = t.id
        ORDER BY oc.start_time ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error listing sessions: ' . $e->getMessage();
}

$page_title = "Online Classes | VIC ERP";
$page_header = "Live Classroom Schedule";
$active_menu = "online-classes";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Scheduled Online Sessions</h2>
        <a href="create-class.php" class="btn btn-primary">
            <i class="fa fa-calendar-plus me-1"></i> Schedule Live Class
        </a>
    </div>

    <!-- Sub links panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="class-list.php" class="btn btn-sm btn-primary">Classes Register</a>
            <a href="create-class.php" class="btn btn-sm btn-light">Schedule Class</a>
            <a href="zoom-meetings.php" class="btn btn-sm btn-light">Zoom Settings</a>
            <a href="google-meet.php" class="btn btn-sm btn-light">Google Meet Settings</a>
            <a href="recordings.php" class="btn btn-sm btn-light">Class Recordings</a>
            <a href="reports.php" class="btn btn-sm btn-light">Attendance Reports</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Classes Register List -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-video me-2 text-primary"></i>Live Classes Registry</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Topic / Subject</th>
                        <th>Class target</th>
                        <th>Instructor</th>
                        <th>Platform</th>
                        <th>Time Range</th>
                        <th>Status</th>
                        <th class="pe-4 text-end">Meeting Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($classes) > 0): ?>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($c['title']) ?></span>
                                    <div class="text-muted small"><?= htmlspecialchars($c['subject'] ?: 'General Subject') ?></div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($c['class_name']) ?></span></td>
                                <td><?= htmlspecialchars($c['teacher_name'] ?: 'External faculty') ?></td>
                                <td>
                                    <?php if ($c['platform'] === 'ZOOM'): ?>
                                        <span class="badge bg-primary-subtle text-primary"><i class="fa fa-video me-1"></i> Zoom</span>
                                    <?php elseif ($c['platform'] === 'GOOGLE_MEET'): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="fa fa-calendar me-1"></i> Meet</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning"><i class="fa fa-circle-nodes me-1"></i> Jitsi</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d M, h:i A', strtotime($c['start_time'])) ?><br>to <?= date('h:i A', strtotime($c['end_time'])) ?>
                                </td>
                                <td>
                                    <?php if ($c['status'] === 'UPCOMING'): ?>
                                        <span class="badge bg-info-subtle text-info"><i class="fa fa-clock me-1"></i> Upcoming</span>
                                    <?php elseif ($c['status'] === 'LIVE'): ?>
                                        <span class="badge bg-danger-subtle text-danger animate-pulse"><i class="fa fa-circle-play me-1"></i> Live</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="fa fa-circle-check me-1"></i> Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($c['status'] === 'UPCOMING'): ?>
                                            <a href="?action=start&id=<?= $c['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="fa fa-circle-play"></i> Start
                                            </a>
                                        <?php elseif ($c['status'] === 'LIVE'): ?>
                                            <a href="?action=complete&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-circle-check"></i> Complete
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($c['meeting_link'])): ?>
                                            <a href="<?= htmlspecialchars($c['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa fa-arrow-up-right-from-square"></i> Join
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="?action=cancel&id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this online class session?')">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                No online classes scheduled. Click 'Schedule Live Class' to start.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
