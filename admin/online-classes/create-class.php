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

// Fetch Teachers
$teachers = [];
try {
    $teachers = $pdo->query("SELECT id, name FROM teachers")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading teachers: ' . $e->getMessage();
}

// Handle Form Submission
if (isset($_POST['save_class'])) {
    $title = trim($_POST['title']);
    $class_name = trim($_POST['class_name']);
    $subject = trim($_POST['subject']);
    $teacher_id = (int)$_POST['teacher_id'];
    $platform = $_POST['platform'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $auto_gen = isset($_POST['auto_generate']);
    $meeting_link = trim($_POST['meeting_link']);

    if (empty($title) || empty($class_name) || empty($start_time) || empty($end_time)) {
        $error = 'Please fill out all required fields.';
    } else {
        $meeting_id = '';
        if ($auto_gen) {
            if ($platform === 'ZOOM') {
                // Fetch Zoom credentials to verify
                $zoom = $pdo->query("SELECT * FROM zoom_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if (!$zoom) {
                    $error = 'Zoom credentials not configured. Please configure them first.';
                } else {
                    // Simulate Zoom API response
                    $meeting_id = rand(100000000, 999999999);
                    $meeting_link = "https://zoom.us/j/" . $meeting_id;
                }
            } else {
                // Fetch Google credentials
                $google = $pdo->query("SELECT * FROM google_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if (!$google) {
                    $error = 'Google Meet credentials not configured. Please configure them first.';
                } else {
                    // Simulate Google calendar Meet response
                    $chars = 'abcdefghijklmnopqrstuvwxyz';
                    $g_code = substr(str_shuffle($chars), 0, 3) . '-' . substr(str_shuffle($chars), 0, 4) . '-' . substr(str_shuffle($chars), 0, 3);
                    $meeting_id = $g_code;
                    $meeting_link = "https://meet.google.com/" . $g_code;
                }
            }
        }

        if (empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO online_classes (title, class_name, subject, teacher_id, platform, meeting_link, meeting_id, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'UPCOMING')
                ");
                $stmt->execute([$title, $class_name, $subject, $teacher_id, $platform, $meeting_link, $meeting_id, $start_time, $end_time]);
                $success = 'Online class scheduled and links generated successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to schedule class: ' . $e->getMessage();
            }
        }
    }
}

$page_title = "Schedule Class | VIC ERP";
$page_header = "Online Classes Integration";
$active_menu = "online-classes";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Schedule a New Live Class</h2>
        <a href="class-list.php" class="btn btn-outline-secondary">
            <i class="fa fa-list me-1"></i> Scheduled Classes
        </a>
    </div>

    <!-- Sub links panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="class-list.php" class="btn btn-sm btn-light">Classes Register</a>
            <a href="create-class.php" class="btn btn-sm btn-primary">Schedule Class</a>
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

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <form method="POST" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Class Title / Topic <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Algebra Chapter 3 Revision" required style="border-radius: 8px;">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Target Class Room <span class="text-danger">*</span></label>
                <select name="class_name" class="form-select" required style="border-radius: 8px;">
                    <option value="">-- Choose Class --</option>
                    <option value="Class 10-A">Class 10-A</option>
                    <option value="Class 11-B">Class 11-B</option>
                    <option value="Class 9-C">Class 9-C</option>
                    <option value="Class 12-Science">Class 12-Science</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Subject / Domain</label>
                <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics" style="border-radius: 8px;">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Assigned Teacher faculty</label>
                <select name="teacher_id" class="form-select" required style="border-radius: 8px;">
                    <option value="">-- Select Teacher --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Streaming Platform</label>
                <select name="platform" id="platform" class="form-select" style="border-radius: 8px;">
                    <option value="ZOOM">Zoom Meetings</option>
                    <option value="GOOGLE_MEET">Google Meet</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Start Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="start_time" class="form-control" required style="border-radius: 8px;">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">End Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="end_time" class="form-control" required style="border-radius: 8px;">
            </div>

            <div class="col-12 mt-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="auto_generate" name="auto_generate" checked>
                    <label class="form-check-label fw-semibold small" for="auto_generate">Auto-Generate Link via API Credentials Integration</label>
                </div>
                <div id="manual_link_group" class="d-none">
                    <label class="form-label fw-semibold small">Manual Link Input</label>
                    <input type="url" name="meeting_link" id="meeting_link" class="form-control" placeholder="https://..." style="border-radius: 8px;">
                </div>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" name="save_class" class="btn btn-primary px-4 fw-semibold" style="border-radius: 8px;">
                    <i class="fa fa-calendar-plus me-2"></i> Schedule Class
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('auto_generate').addEventListener('change', function() {
    const manualGroup = document.getElementById('manual_link_group');
    if (this.checked) {
        manualGroup.classList.add('d-none');
    } else {
        manualGroup.classList.remove('d-none');
    }
});
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
