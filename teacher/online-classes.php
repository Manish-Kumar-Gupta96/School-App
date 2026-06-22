<?php
$root_path = "../";
require_once($root_path . 'config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$teacher_id = $_SESSION['teacher_id'];

// Handle class status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $act = $_GET['action'];
    $status = 'UPCOMING';
    
    if ($act === 'start') {
        $status = 'LIVE';
        $success = 'Class set to LIVE. Live streaming active.';
    } else if ($act === 'complete') {
        $status = 'COMPLETED';
        $success = 'Class marked as completed.';
    }

    try {
        $stmt = $pdo->prepare("UPDATE online_classes SET status = ? WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$status, $id, $teacher_id]);
    } catch (PDOException $e) {
        $error = 'Failed to update meeting: ' . $e->getMessage();
    }
}

// Fetch teacher classes
$classes = [];
try {
    $stmt_c = $pdo->prepare("
        SELECT * FROM online_classes 
        WHERE teacher_id = ? AND status != 'COMPLETED'
        ORDER BY start_time ASC
    ");
    $stmt_c->execute([$teacher_id]);
    $classes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading classroom list: ' . $e->getMessage();
}

$page_title = "Online Classes | Teacher Portal";
$page_header = "Virtual Class Coordinator";
$active_menu = "online-classes";

require_once('includes/header.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">My Scheduled Live Classes</h2>
        <a href="create-class.php" class="btn btn-primary">
            <i class="fa fa-calendar-plus me-1"></i> Schedule Live Class
        </a>
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

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark mb-3"><i class="fa fa-chalkboard me-2 text-primary"></i>Today's Class Schedule</h5>
        <hr class="text-muted mt-0 mb-4">

        <?php if (count($classes) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Topic / Subject</th>
                            <th>Class Target</th>
                            <th>Platform</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($c['title']) ?></span>
                                    <div class="text-muted small"><?= htmlspecialchars($c['subject'] ?: '-') ?></div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($c['class_name']) ?></span></td>
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
                                        <span class="badge bg-danger text-white animate-pulse"><i class="fa fa-circle-play me-1"></i> Streaming Live</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if ($c['status'] === 'UPCOMING'): ?>
                                            <a href="?action=start&id=<?= $c['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="fa fa-circle-play"></i> Start Meeting
                                            </a>
                                        <?php elseif ($c['status'] === 'LIVE'): ?>
                                            <a href="end-class.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger">
                                                <i class="fa fa-circle-check"></i> End Class
                                            </a>
                                        <?php endif; ?>

                                        <?php if (!empty($c['meeting_link'])): ?>
                                            <a href="<?= htmlspecialchars($c['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">
                                                <i class="fa fa-arrow-up-right-from-square"></i> Launch
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                No classes scheduled for today.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
