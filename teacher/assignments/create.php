<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/notifications.php');

$message = '';
$error = '';

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

if (isset($_POST['create_assignment'])) {
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = trim($_POST['due_date']);
    $total_marks = (int)$_POST['total_marks'];

    // Validation (Must be assigned to class)
    $stmt_check = $pdo->prepare("SELECT id FROM teacher_class_assignments WHERE teacher_id = ? AND class_id = ? AND section_id = ? AND subject_id = ?");
    $stmt_check->execute([$teacher_id, $class_id, $section_id, $subject_id]);
    
    if (!$stmt_check->fetch()) {
        $error = "Unauthorized. You are not assigned to this class.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt_insert = $pdo->prepare("
                INSERT INTO assignments (school_id, teacher_id, class_id, section_id, subject_id, title, description, due_date, total_marks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt_insert->execute([$schoolId, $teacher_id, $class_id, $section_id, $subject_id, $title, $description, $due_date, $total_marks]);
            $assignment_id = $pdo->lastInsertId();

            // Multi-File Upload handling
            if (isset($_FILES['assignment_files']) && !empty($_FILES['assignment_files']['name'][0])) {
                $upload_dir = '../../uploads/assignments/teacher_files/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $stmt_file = $pdo->prepare("INSERT INTO assignment_files (assignment_id, file_name, file_path) VALUES (?, ?, ?)");

                foreach ($_FILES['assignment_files']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['assignment_files']['error'][$key] == UPLOAD_ERR_OK) {
                        $original_name = basename($_FILES['assignment_files']['name'][$key]);
                        $file_name = time() . '_' . rand(100, 999) . '_' . preg_replace("/[^a-zA-Z0-9.-]/", "_", $original_name);
                        $target_file = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $file_path = 'uploads/assignments/teacher_files/' . $file_name;
                            $stmt_file->execute([$assignment_id, $original_name, $file_path]);
                        }
                    }
                }
            }

            $pdo->commit();
            $message = "Assignment created successfully!";

            // Notify students & parents
            $stmt_stu = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND section_id = ?");
            $stmt_stu->execute([$class_id, $section_id]);
            $students = $stmt_stu->fetchAll(PDO::FETCH_ASSOC);

            $recipients = [];
            foreach ($students as $stu) {
                $recipients[] = ['user_type' => 'student', 'user_id' => $stu['id']];
                $recipients[] = ['user_type' => 'parent', 'user_id' => $stu['id']]; // Assuming logic maps parent from student ID in real app
            }

            if (!empty($recipients)) {
                sendNotification(
                    $pdo,
                    "New Homework Assigned",
                    "Subject: {$title} | Due: {$due_date}",
                    "info",
                    $recipients,
                    $_SESSION['user_id']
                );
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch assigned subjects
$stmt_assigned = $pdo->prepare("
    SELECT tca.class_id, tca.section_id, tca.subject_id, c.class_name, s.section_name, sub.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    JOIN subjects sub ON tca.subject_id = sub.id
    WHERE tca.teacher_id = ?
");
$stmt_assigned->execute([$teacher_id]);
$assigned_classes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Create Assignment | Teacher Portal";
$page_header = "Assignments";
$active_menu = "assignments";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Post a new homework or assignment</h5>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-primary"><i class="fa fa-chart-pie me-1"></i> Dashboard</a>
        <a href="create.php" class="btn btn-primary"><i class="fa fa-plus me-1"></i> New Assignment</a>
        <a href="review.php" class="btn btn-outline-success"><i class="fa fa-check-double me-1"></i> Review Submissions</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">New Assignment Details</h5>
        <button class="btn btn-sm btn-outline-secondary" onclick="alert('AI Generation coming soon!')"><i class="fa fa-robot"></i> Use AI Generator</button>
    </div>
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
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
                <label class="form-label fw-semibold">Title / Chapter <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 5: Force and Motion" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description / Instructions</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                    <input type="date" name="due_date" class="form-control" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Total Marks <span class="text-danger">*</span></label>
                    <input type="number" name="total_marks" class="form-control" required value="20">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Attachments (PDF, DOCX, Images)</label>
                    <input type="file" name="assignment_files[]" class="form-control" multiple>
                    <small class="text-muted">You can select multiple files.</small>
                </div>
            </div>

            <button type="submit" name="create_assignment" class="btn btn-primary w-100">
                <i class="fa fa-paper-plane me-1"></i> Create & Notify Students
            </button>
        </form>
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
