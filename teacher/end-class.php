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

$error = '';
$success = '';
$teacher_id = $_SESSION['teacher_id'];

$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch and verify class ownership
try {
    $stmt_c = $pdo->prepare("SELECT * FROM online_classes WHERE id = ? AND teacher_id = ?");
    $stmt_c->execute([$class_id, $teacher_id]);
    $class = $stmt_c->fetch(PDO::FETCH_ASSOC);

    if (!$class) {
        die("Error: Class session not found or access denied.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch joined students for attendance preview
$attendees = [];
try {
    $stmt_a = $pdo->prepare("
        SELECT oca.*, s.first_name, s.last_name, s.roll_no
        FROM online_class_attendance oca
        JOIN students s ON oca.student_id = s.id
        WHERE oca.class_id = ?
        ORDER BY oca.join_time ASC
    ");
    $stmt_a->execute([$class_id]);
    $attendees = $stmt_a->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fail silently or log
}

// Process Post Session Completion
if (isset($_POST['end_session'])) {
    $recording_link = trim($_POST['recording_link'] ?? '');
    $assign_hw = isset($_POST['assign_homework']);
    $hw_title = trim($_POST['hw_title'] ?? '');
    $hw_desc = trim($_POST['hw_desc'] ?? '');
    $hw_due = $_POST['hw_due'] ?? '';

    try {
        $pdo->beginTransaction();

        // 1. Update online class status
        $stmt_upd = $pdo->prepare("UPDATE online_classes SET status = 'COMPLETED' WHERE id = ?");
        $stmt_upd->execute([$class_id]);

        // 2. Finalize attendance: set leave_time and duration for any students currently marked joined
        $stmt_att_fin = $pdo->prepare("
            UPDATE online_class_attendance 
            SET leave_time = NOW(),
                duration = TIMESTAMPDIFF(SECOND, join_time, NOW()),
                duration_minutes = TIMESTAMPDIFF(MINUTE, join_time, NOW())
            WHERE class_id = ? AND leave_time IS NULL
        ");
        $stmt_att_fin->execute([$class_id]);

        // 3. Save Recording Link
        if (!empty($recording_link)) {
            $stmt_rec = $pdo->prepare("
                INSERT INTO class_recordings (class_id, title, recording_link, uploaded_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt_rec->execute([$class_id, $class['title'] . " - Recording", $recording_link]);
        }

        // 4. Create Homework and Notifications
        if ($assign_hw && !empty($hw_title) && !empty($hw_due)) {
            // Save Homework
            $stmt_hw = $pdo->prepare("
                INSERT INTO assignments (class, subject, title, description, due_date, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt_hw->execute([$class['class_name'], $class['subject'], $hw_title, $hw_desc, $hw_due, $teacher_id]);
            $hw_id = $pdo->lastInsertId();

            // Notify students & parents
            require_once($root_path . 'helpers/notification_helper.php');
            
            // Resolve class name keys for notifications
            $class_name = $class['class_name'];
            $c_name = $class_name;
            $s_name = '';
            if (preg_match('/^([^-]+)-([A-Z])$/', $class_name, $m)) {
                $c_name = $m[1];
                $s_name = $m[2];
            }

            // Fetch students
            $stmt_stud = $pdo->prepare("SELECT id, email, phone AS mobile FROM students WHERE class = ? OR class = ? OR (class = ? AND section = ?)");
            $stmt_stud->execute([$class_name, $c_name, $c_name, $s_name]);
            $students_list = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

            $stud_notif_rec = [];
            foreach ($students_list as $st) {
                $stud_notif_rec[] = ['id' => $st['id'], 'type' => 'STUDENT', 'email' => $st['email'], 'mobile' => $st['mobile']];
            }

            if (!empty($stud_notif_rec)) {
                $title_notif = "📚 New Homework Assignment: " . $hw_title;
                $msg_notif = "Subject: {$class['subject']}\nTitle: $hw_title\nDue Date: " . date('d M Y', strtotime($hw_due)) . "\n\nPlease complete and submit on time.";
                sendSystemNotification($pdo, $title_notif, $msg_notif, $stud_notif_rec);
            }

            // Fetch parents
            $stmt_parents = $pdo->prepare("
                SELECT DISTINCT u.id, u.email, p.phone AS mobile
                FROM users u
                JOIN parent_student_map psm ON u.id = psm.parent_id
                JOIN students s ON psm.student_id = s.id
                LEFT JOIN parents p ON p.email = u.email
                WHERE s.class = ? OR s.class = ? OR (s.class = ? AND s.section = ?)
            ");
            $stmt_parents->execute([$class_name, $c_name, $c_name, $s_name]);
            $parents_list = $stmt_parents->fetchAll(PDO::FETCH_ASSOC);

            $parent_notif_rec = [];
            foreach ($parents_list as $pr) {
                $parent_notif_rec[] = ['id' => $pr['id'], 'type' => 'PARENT', 'email' => $pr['email'], 'mobile' => $pr['mobile']];
            }

            if (!empty($parent_notif_rec)) {
                $title_p_notif = "📚 New Assignment Assigned to Child";
                $msg_p_notif = "Dear Parent,\n\nA new assignment '{$hw_title}' has been published for your child in {$class['subject']}.\n\nDue Date: " . date('d M Y', strtotime($hw_due)) . "\n\nPlease ensure your child completes it on time.";
                sendSystemNotification($pdo, $title_p_notif, $msg_p_notif, $parent_notif_rec);
            }
        }

        $pdo->commit();
        header("Location: online-classes.php");
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Failed to end class and save tasks: ' . $e->getMessage();
    }
}

$page_title = "End Live Class Session";
$page_header = "Virtual Class Coordinator";
$active_menu = "online-classes";

require_once('includes/header.php');
?>

<div class="main-dashboard p-4 text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">End Class Session</h2>
            <p class="text-muted mb-0">Topic: <strong><?= htmlspecialchars($class['title']) ?></strong> | Subject: <?= htmlspecialchars($class['subject']) ?></p>
        </div>
        <a href="online-classes.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mb-4" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Attendance Roster -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-users text-primary me-2"></i>Participant Attendance Preview</h5>
                <hr class="text-muted mt-0 mb-3">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Join Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($attendees) > 0): ?>
                                <?php foreach ($attendees as $a): ?>
                                    <tr>
                                        <td><strong>#<?= htmlspecialchars($a['roll_no'] ?: '-') ?></strong></td>
                                        <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></td>
                                        <td class="small text-muted"><?= date('h:i A', strtotime($a['join_time'])) ?></td>
                                        <td>
                                            <?php if ($a['leave_time'] === null): ?>
                                                <span class="badge bg-success-subtle text-success">In Class</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><?= round($a['duration'] / 60) ?> Min</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted small">
                                        <i class="fa fa-user-slash d-block mb-1 fs-5"></i>
                                        No student joined this session.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Post Class Completion Forms -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-circle-check text-success me-2"></i>Class Completion Actions</h5>
                <hr class="text-muted mt-0 mb-4">

                <form method="POST" action="">
                    <!-- Recording link -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Upload Recording Link (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted"><i class="fa fa-clapperboard"></i></span>
                            <input type="url" name="recording_link" class="form-control" placeholder="https://drive.google.com/..." style="border-radius: 0 8px 8px 0;">
                        </div>
                        <div class="form-text text-muted small">Link will be accessible immediately in student archives.</div>
                    </div>

                    <!-- Assign homework toggle -->
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="assign_homework" name="assign_homework" onchange="toggleHomeworkBlock()">
                        <label class="form-check-label fw-bold text-dark small" for="assign_homework">Assign Post-Class Homework Tasks</label>
                    </div>

                    <!-- Homework fields block -->
                    <div id="homework_block" class="d-none border p-3 rounded mb-4" style="background-color: #fafbfd; border-radius: 10px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Homework Title *</label>
                            <input type="text" name="hw_title" id="hw_title" class="form-control" placeholder="e.g. Algebra Exercise 3.2" style="border-radius: 8px;">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Instructions / Description</label>
                            <textarea name="hw_desc" id="hw_desc" rows="3" class="form-control" placeholder="Provide guidelines for completion..." style="border-radius: 8px;"></textarea>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Submission Due Date *</label>
                            <input type="date" name="hw_due" id="hw_due" class="form-control" style="border-radius: 8px;">
                        </div>
                    </div>

                    <button type="submit" name="end_session" class="btn btn-danger w-100 py-2.5 fw-semibold" style="border-radius: 8px;" onclick="return confirm('Are you sure you want to end this live session?')">
                        <i class="fa fa-circle-xmark me-2"></i> End Class & Save Actions
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleHomeworkBlock() {
    const check = document.getElementById('assign_homework');
    const block = document.getElementById('homework_block');
    const hwTitle = document.getElementById('hw_title');
    const hwDue = document.getElementById('hw_due');

    if (check.checked) {
        block.classList.remove('d-none');
        hwTitle.setAttribute('required', 'required');
        hwDue.setAttribute('required', 'required');
    } else {
        block.classList.add('d-none');
        hwTitle.removeAttribute('required');
        hwDue.removeAttribute('required');
    }
}
</script>

<?php
require_once('includes/footer.php');
?>
