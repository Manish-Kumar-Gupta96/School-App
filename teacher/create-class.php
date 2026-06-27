<?php
$root_path = "../";
require_once($root_path . 'config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Only allow logged-in teachers
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit;
}

$success = '';
$error = '';
$teacher_id = $_SESSION['teacher_id'];

// Fetch teacher's assigned classes and subjects
$assignments = [];
$unique_classes = [];
try {
    $stmt_a = $pdo->prepare("
        SELECT ta.class_id, ta.section_id, ta.subject_id,
               c.class_name, s.section_name, sub.subject_name
        FROM teacher_class_assignments ta
        JOIN classes c ON ta.class_id = c.id
        JOIN sections s ON ta.section_id = s.id
        JOIN subjects sub ON ta.subject_id = sub.id
        WHERE ta.teacher_id = ?
    ");
    $stmt_a->execute([$teacher_id]);
    $assignments = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

    // Group unique class + sections for dropdown
    foreach ($assignments as $a) {
        $key = $a['class_id'] . '-' . $a['section_id'];
        $unique_classes[$key] = "Class " . $a['class_name'] . " - " . $a['section_name'];
    }
} catch (PDOException $e) {
    $error = 'Error loading assignments: ' . $e->getMessage();
}

// Handle Form Submission
if (isset($_POST['save_class'])) {
    $title = trim($_POST['title']);
    $class_section = $_POST['class_section_id'] ?? ''; // e.g. "14-1"
    $subject_id = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;
    $platform = $_POST['platform'] ?? 'ZOOM';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $auto_gen = isset($_POST['auto_generate']);
    $meeting_link = trim($_POST['meeting_link'] ?? '');

    if (empty($title) || empty($class_section) || empty($subject_id) || empty($start_time) || empty($end_time)) {
        $error = 'Please fill out all required fields.';
    } else {
        list($class_id, $section_id) = explode('-', $class_section);
        $class_id = (int)$class_id;
        $section_id = (int)$section_id;

        // Security check: Automatically validate assignment exists
        $stmt_check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM teacher_class_assignments 
            WHERE teacher_id = ? AND class_id = ? AND section_id = ? AND subject_id = ?
        ");
        $stmt_check->execute([$teacher_id, $class_id, $section_id, $subject_id]);
        $assigned = (bool)$stmt_check->fetchColumn();

        if (!$assigned) {
            $error = 'Access Denied: You are not assigned to this Class, Section, or Subject.';
        } else {
            // Resolve text labels for backwards compatibility
            $stmt_c = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
            $stmt_c->execute([$class_id]);
            $c_name = $stmt_c->fetchColumn() ?: '';

            $stmt_s = $pdo->prepare("SELECT section_name FROM sections WHERE id = ?");
            $stmt_s->execute([$section_id]);
            $s_name = $stmt_s->fetchColumn() ?: '';

            $stmt_sub = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?");
            $stmt_sub->execute([$subject_id]);
            $sub_name = $stmt_sub->fetchColumn() ?: '';

            $class_name = $c_name . "-" . $s_name;
            $subject = $sub_name;

            $meeting_id = '';
            if ($auto_gen) {
                if ($platform === 'ZOOM') {
                    $meeting_id = rand(100000000, 999999999);
                    $meeting_link = "https://zoom.us/j/" . $meeting_id;
                } else if ($platform === 'GOOGLE_MEET') {
                    $chars = 'abcdefghijklmnopqrstuvwxyz';
                    $g_code = substr(str_shuffle($chars), 0, 3) . '-' . substr(str_shuffle($chars), 0, 4) . '-' . substr(str_shuffle($chars), 0, 3);
                    $meeting_id = $g_code;
                    $meeting_link = "https://meet.google.com/" . $g_code;
                }
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO online_classes (title, class_name, subject, teacher_id, platform, meeting_link, meeting_id, start_time, end_time, status, class_id, section_id, subject_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'UPCOMING', ?, ?, ?)
                ");
                $stmt->execute([$title, $class_name, $subject, $teacher_id, $platform, $meeting_link, $meeting_id, $start_time, $end_time, $class_id, $section_id, $subject_id]);
                
                $new_class_id = $pdo->lastInsertId();
                require_once($root_path . 'helpers/notification_helper.php');
                notifyLiveClassScheduled($pdo, $new_class_id);

                $success = 'Online class scheduled and links generated successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to schedule class: ' . $e->getMessage();
            }
        }
    }
}

$page_title = "Schedule Class | Teacher Portal";
$page_header = "Virtual Class Coordinator";
$active_menu = "online-classes";

require_once('includes/header.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Schedule a New Live Class</h2>
        <a href="online-classes.php" class="btn btn-outline-secondary">
            <i class="fa fa-list me-1"></i> Back to Classes
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
        <form method="POST" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Class Title / Topic <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Algebra Chapter 3 Revision" required style="border-radius: 8px;">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Target Class Room <span class="text-danger">*</span></label>
                <select name="class_section_id" id="class_section_id" class="form-select" required onchange="updateSubjects()" style="border-radius: 8px;">
                    <option value="">-- Choose Class --</option>
                    <?php foreach ($unique_classes as $val => $lbl): ?>
                        <option value="<?= $val ?>"><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small">Subject <span class="text-danger">*</span></label>
                <select name="subject_id" id="subject_id" class="form-select" required style="border-radius: 8px;">
                    <option value="">-- Choose Subject --</option>
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
// Load assignments array from PHP
const assignments = <?= json_encode($assignments) ?>;

function updateSubjects() {
    const classSectionVal = document.getElementById('class_section_id').value;
    const subjectSelect = document.getElementById('subject_id');
    
    // Clear previous options
    subjectSelect.innerHTML = '<option value="">-- Choose Subject --</option>';
    
    if (!classSectionVal) return;
    
    const parts = classSectionVal.split('-');
    const classId = parseInt(parts[0]);
    const sectionId = parseInt(parts[1]);
    
    // Filter and add subjects associated with this class and section
    const filtered = assignments.filter(a => parseInt(a.class_id) === classId && parseInt(a.section_id) === sectionId);
    
    filtered.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.subject_id;
        opt.textContent = a.subject_name;
        subjectSelect.appendChild(opt);
    });
}

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
require_once('includes/footer.php');
?>
