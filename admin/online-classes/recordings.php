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

// Handle recording upload
if (isset($_POST['upload_recording'])) {
    $class_id = (int)$_POST['class_id'];
    $title = trim($_POST['title']);
    $recording_link = trim($_POST['recording_link']);

    if (empty($title) || empty($recording_link) || $class_id <= 0) {
        $error = 'All fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO class_recordings (class_id, title, recording_link) VALUES (?, ?, ?)");
            $stmt->execute([$class_id, $title, $recording_link]);
            $success = 'Class video recording added successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving recording: ' . $e->getMessage();
        }
    }
}

// Handle delete recording
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM class_recordings WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Recording entry deleted.';
    } catch (PDOException $e) {
        $error = 'Failed to delete: ' . $e->getMessage();
    }
}

// Fetch Completed Classes to link
$classes = [];
try {
    $classes = $pdo->query("SELECT id, title, class_name FROM online_classes ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore
}

// Fetch Recordings
$recordings = [];
try {
    $recordings = $pdo->query("
        SELECT cr.*, oc.title AS class_title, oc.class_name, oc.subject 
        FROM class_recordings cr
        LEFT JOIN online_classes oc ON cr.class_id = oc.id
        ORDER BY cr.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Failed to load recordings: ' . $e->getMessage();
}

$page_title = "Class Recordings | VIC ERP";
$page_header = "Recordings Repository";
$active_menu = "online-classes";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Class Video Recordings</h2>
    </div>

    <!-- Sub links panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="class-list.php" class="btn btn-sm btn-light">Classes Register</a>
            <a href="create-class.php" class="btn btn-sm btn-light">Schedule Class</a>
            <a href="zoom-meetings.php" class="btn btn-sm btn-light">Zoom Settings</a>
            <a href="google-meet.php" class="btn btn-sm btn-light">Google Meet Settings</a>
            <a href="recordings.php" class="btn btn-sm btn-primary">Class Recordings</a>
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

    <div class="row g-4">
        <!-- Upload Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-circle-arrow-up text-primary me-2"></i>Add Recorded Session</h5>
                <hr class="text-muted mt-0 mb-4">
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Select Class Session</label>
                        <select name="class_id" class="form-select" required style="border-radius: 8px;">
                            <option value="">-- Choose Class --</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['title']) ?> (<?= htmlspecialchars($c['class_name']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Chapter / Topic Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1 Trigonometry Intro" required style="border-radius: 8px;">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Video URL / Cloud Link (Google Drive, AWS, Youtube)</label>
                        <input type="url" name="recording_link" class="form-control" placeholder="https://..." required style="border-radius: 8px;">
                    </div>

                    <button type="submit" name="upload_recording" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                        <i class="fa fa-plus-circle me-2"></i> Add Recording
                    </button>
                </form>
            </div>
        </div>

        <!-- Recordings Inventory -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-clapperboard me-2 text-success"></i>Archived Class Playbacks</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Topic / Session</th>
                                <th>Class Info</th>
                                <th>Access Link</th>
                                <th class="pe-4 text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recordings) > 0): ?>
                                <?php foreach ($recordings as $rec): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($rec['title']) ?></span>
                                            <div class="text-muted small">Session: <?= htmlspecialchars($rec['class_title'] ?: '-') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($rec['class_name'] ?: '-') ?></span>
                                            <div class="text-muted small"><?= htmlspecialchars($rec['subject'] ?: '-') ?></div>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($rec['recording_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-play-circle me-1"></i> Watch Video
                                            </a>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="?delete=1&id=<?= $rec['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this recording?')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No recordings archived. Use the form to add one.
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
require_once($root_path . 'admin/includes/footer.php');
?>
