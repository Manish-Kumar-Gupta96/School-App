<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');
require_once('../../includes/notifications.php');

if ($_SESSION['role'] !== 'teacher' && $_SESSION['role'] !== 'Teacher') {
    die("Access Denied.");
}

$teacher_id = $_SESSION['teacher_id'];
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Fetch assigned classes (Group by Class & Section to avoid duplicates if multiple subjects)
$stmt_assigned = $pdo->prepare("
    SELECT DISTINCT tca.class_id, tca.section_id, c.class_name, s.section_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    WHERE tca.teacher_id = ?
");
$stmt_assigned->execute([$teacher_id]);
$assigned_classes = $stmt_assigned->fetchAll(PDO::FETCH_ASSOC);

$selected_class_sec = isset($_GET['class_sec']) ? $_GET['class_sec'] : '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$students = [];

if ($selected_class_sec) {
    list($class_id, $section_id) = explode('-', $selected_class_sec);
    
    // Verify assignment security
    $valid = false;
    foreach($assigned_classes as $ac) {
        if ($ac['class_id'] == $class_id && $ac['section_id'] == $section_id) {
            $valid = true; break;
        }
    }

    if ($valid) {
        // Fetch Students and existing attendance
        $stmt_stu = $pdo->prepare("
            SELECT s.id, s.first_name, s.last_name, s.roll_number, a.status, a.remarks
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
            WHERE s.class_id = ? AND s.section_id = ? AND s.school_id = ?
            ORDER BY s.roll_number ASC
        ");
        $stmt_stu->execute([$selected_date, $class_id, $section_id, $schoolId]);
        $students = $stmt_stu->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Unauthorized. You are not assigned to this class.";
    }
}

// Handle Bulk Save
if (isset($_POST['save_attendance'])) {
    $class_id = (int)$_POST['class_id'];
    $section_id = (int)$_POST['section_id'];
    $att_date = $_POST['attendance_date'];
    $attendance_data = $_POST['attendance'] ?? []; // student_id => ['status' => x, 'remarks' => y]
    
    try {
        $pdo->beginTransaction();
        
        $stmt_ins = $pdo->prepare("
            INSERT INTO attendance (school_id, student_id, class_id, section_id, attendance_date, status, remarks, marked_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks), marked_by = VALUES(marked_by)
        ");

        $absent_students = [];

        foreach ($attendance_data as $stu_id => $data) {
            $status = $data['status'];
            $remarks = trim($data['remarks']);
            
            $stmt_ins->execute([$schoolId, $stu_id, $class_id, $section_id, $att_date, $status, $remarks, $teacher_id]);

            // If absent, queue for notification
            if ($status === 'absent') {
                $absent_students[] = $stu_id;
            }
        }

        $pdo->commit();
        $message = "Attendance saved successfully for $att_date.";

        // Send Parent Notifications for Absentees
        if (!empty($absent_students)) {
            foreach ($absent_students as $abs_id) {
                // Fetch student name
                $stmt_n = $pdo->prepare("SELECT first_name, last_name FROM students WHERE id = ?");
                $stmt_n->execute([$abs_id]);
                $stu_name = $stmt_n->fetch(PDO::FETCH_ASSOC);
                $full_name = $stu_name['first_name'] . ' ' . $stu_name['last_name'];
                
                $recipients = [
                    ['user_type' => 'parent', 'user_id' => $abs_id] // Targeting parent via student ID proxy
                ];

                $body = "Dear Parent,\nYour child {$full_name} was marked ABSENT today ({$att_date}).\nSchool ERP";
                
                sendNotification($pdo, "Attendance Alert: Absent", $body, "danger", $recipients, $_SESSION['user_id']);
            }
        }
        
        // Refresh student list
        header("Location: mark.php?class_sec={$class_id}-{$section_id}&date={$att_date}&success=1");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving attendance: " . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $message = "Attendance saved successfully!";
}

$root_path = "../../";
$page_title = "Mark Attendance | Teacher Portal";
$page_header = "Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Daily Bulk Attendance Marking</h5>
    <div class="d-flex gap-2">
        <a href="mark.php" class="btn btn-primary"><i class="fa fa-user-check me-1"></i> Mark Attendance</a>
        <a href="leave_approvals.php" class="btn btn-outline-info"><i class="fa fa-envelope-open-text me-1"></i> Leave Requests</a>
        <a href="report.php" class="btn btn-outline-secondary"><i class="fa fa-chart-line me-1"></i> Reports</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filter Card -->
<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row align-items-end g-3">
            <div class="col-md-4">
                <label class="form-label fw-bold">Select Class & Section <span class="text-danger">*</span></label>
                <select name="class_sec" class="form-select" required>
                    <option value="">-- Choose Assigned Class --</option>
                    <?php foreach($assigned_classes as $ac): ?>
                        <option value="<?= $ac['class_id'].'-'.$ac['section_id'] ?>" <?= ($selected_class_sec === $ac['class_id'].'-'.$ac['section_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ac['class_name']) ?> - <?= htmlspecialchars($ac['section_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Attendance Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($selected_date) ?>" max="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search me-1"></i> Fetch Students</button>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_class_sec && !empty($students)): ?>
    <div class="card shadow border-0" style="border-radius: 12px;">
        <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0">Student List (<?= count($students) ?>)</h5>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('present')">Mark All Present</button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAll('absent')">Mark All Absent</button>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="POST">
                <?php list($cid, $sid) = explode('-', $selected_class_sec); ?>
                <input type="hidden" name="class_id" value="<?= $cid ?>">
                <input type="hidden" name="section_id" value="<?= $sid ?>">
                <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($selected_date) ?>">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Roll No</th>
                                <th>Student Name</th>
                                <th>Attendance Status</th>
                                <th>Remarks (Optional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $s): ?>
                                <?php $stat = $s['status'] ?? 'present'; // Default to present if null ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= htmlspecialchars($s['roll_number']) ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $s['id'] ?>][status]" id="p_<?= $s['id'] ?>" value="present" <?= $stat === 'present' ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-success btn-sm" for="p_<?= $s['id'] ?>">Present</label>

                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $s['id'] ?>][status]" id="a_<?= $s['id'] ?>" value="absent" <?= $stat === 'absent' ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-danger btn-sm" for="a_<?= $s['id'] ?>">Absent</label>

                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $s['id'] ?>][status]" id="l_<?= $s['id'] ?>" value="late" <?= $stat === 'late' ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-warning btn-sm" for="l_<?= $s['id'] ?>">Late</label>
                                            
                                            <input type="radio" class="btn-check status-radio" name="attendance[<?= $s['id'] ?>][status]" id="h_<?= $s['id'] ?>" value="halfday" <?= $stat === 'halfday' ? 'checked' : '' ?>>
                                            <label class="btn btn-outline-info btn-sm" for="h_<?= $s['id'] ?>">Halfday</label>
                                        </div>
                                    </td>
                                    <td class="pe-4">
                                        <input type="text" name="attendance[<?= $s['id'] ?>][remarks]" class="form-control form-control-sm" placeholder="e.g. Sick, Family issue" value="<?= htmlspecialchars($s['remarks'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light p-3 text-end">
                    <button type="submit" name="save_attendance" class="btn btn-primary px-5 fw-bold"><i class="fa fa-save me-1"></i> Save Attendance</button>
                </div>
            </form>
        </div>
    </div>
<?php elseif ($selected_class_sec): ?>
    <div class="alert alert-info border-0 shadow-sm" style="border-radius: 10px;">
        No students found in this class/section.
    </div>
<?php endif; ?>

<script>
function markAll(status) {
    document.querySelectorAll('.status-radio[value="'+status+'"]').forEach(radio => {
        radio.checked = true;
    });
}
</script>

<?php require_once('../includes/footer.php'); ?>
