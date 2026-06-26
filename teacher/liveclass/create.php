<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/notifications.php');

$message = '';
$error = '';

if (isset($_POST['create_live_class'])) {
    $teacher_id = $_SESSION['teacher_id'];
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title']);
    $platform = trim($_POST['platform']);
    $meeting_link = trim($_POST['meeting_link']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);

    // Validation
    // Note: We check `teacher_class_assignments` (using existing mapping in DB)
    $stmt_check = $pdo->prepare("
        SELECT id FROM teacher_class_assignments 
        WHERE teacher_id = ? AND class_id = ? AND section_id = ? AND subject_id = ?
    ");
    $stmt_check->execute([$teacher_id, $class_id, $section_id, $subject_id]);
    if (!$stmt_check->fetch()) {
        $error = "Unauthorized. You are not assigned to this Class/Section/Subject.";
    } else {
        $stmt_insert = $pdo->prepare("
            INSERT INTO live_classes (school_id, teacher_id, class_id, section_id, subject_id, title, platform, meeting_link, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        
        $schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

        if ($stmt_insert->execute([$schoolId, $teacher_id, $class_id, $section_id, $subject_id, $title, $platform, $meeting_link, $start_time, $end_time])) {
            $live_class_id = $pdo->lastInsertId();

            // Notify students (Mock logic: finding all students in this class/section)
            $stmt_stu = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND section_id = ?");
            $stmt_stu->execute([$class_id, $section_id]);
            $students = $stmt_stu->fetchAll(PDO::FETCH_ASSOC);

            $recipients = [];
            foreach ($students as $stu) {
                $recipients[] = ['user_type' => 'student', 'user_id' => $stu['id']];
            }

            sendNotification(
                $pdo,
                "New Live Class Scheduled",
                "Subject: {$title} | Date: {$start_time}",
                "info",
                $recipients,
                $_SESSION['user_id']
            );

            $message = "Live class scheduled successfully and students have been notified!";
        } else {
            $error = "Database error while scheduling class.";
        }
    }
}

// Fetch assigned subjects for the form
$stmt_assigned = $pdo->prepare("
    SELECT tca.class_id, tca.section_id, tca.subject_id, c.class_name, s.section_name, sub.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    JOIN subjects sub ON tca.subject_id = sub.id
    WHERE tca.teacher_id = ?
");
$stmt_assigned->execute([$_SESSION['teacher_id']]);
$assigned_classes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Schedule Live Class | Teacher Portal";
$page_header = "Live Classes";
$active_menu = "liveclass";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Schedule a new live class session</h5>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-primary">
            <i class="fa fa-list me-1"></i> My Live Classes
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Schedule New Class</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Class & Subject <span class="text-danger">*</span></label>
                        <select name="assigned_mapping" id="assigned_mapping" class="form-select" required onchange="updateHiddenFields()">
                            <option value="">Select your assigned class/subject...</option>
                            <?php foreach($assigned_classes as $ac): ?>
                                <option value="<?= $ac['class_id'].'-'.$ac['section_id'].'-'.$ac['subject_id'] ?>">
                                    <?= htmlspecialchars($ac['class_name']) ?> - <?= htmlspecialchars($ac['section_name']) ?> (<?= htmlspecialchars($ac['subject_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="class_id" id="h_class_id">
                        <input type="hidden" name="section_id" id="h_section_id">
                        <input type="hidden" name="subject_id" id="h_subject_id">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class Title / Topic <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Platform <span class="text-danger">*</span></label>
                            <select name="platform" class="form-select" required>
                                <option value="google_meet">Google Meet</option>
                                <option value="zoom">Zoom</option>
                                <option value="jitsi">Jitsi</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-4">
                            <label class="form-label fw-semibold">Meeting Link <span class="text-danger">*</span></label>
                            <input type="url" name="meeting_link" class="form-control" placeholder="https://meet.google.com/..." required>
                        </div>
                    </div>

                    <button type="submit" name="create_live_class" class="btn btn-primary w-100">
                        <i class="fa fa-calendar-plus me-1"></i> Schedule Class & Notify Students
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateHiddenFields() {
    const val = document.getElementById('assigned_mapping').value;
    if (val) {
        const parts = val.split('-');
        document.getElementById('h_class_id').value = parts[0];
        document.getElementById('h_section_id').value = parts[1];
        document.getElementById('h_subject_id').value = parts[2];
    }
}
</script>

<?php require_once('../includes/footer.php'); ?>
