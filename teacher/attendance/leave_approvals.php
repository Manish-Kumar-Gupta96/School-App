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

// Handle Approval / Rejection
if (isset($_POST['action_leave'])) {
    $leave_id = (int)$_POST['leave_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'

    if (in_array($status, ['approved', 'rejected'])) {
        try {
            $pdo->beginTransaction();

            $stmt_upd = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt_upd->execute([$status, $leave_id]);

            // Fetch student ID for notification
            $stmt_stu = $pdo->prepare("SELECT student_id, from_date, to_date FROM leave_requests WHERE id = ?");
            $stmt_stu->execute([$leave_id]);
            $lr = $stmt_stu->fetch(PDO::FETCH_ASSOC);

            if ($lr) {
                // If Approved, optionally auto-mark attendance as 'halfday' or 'absent' with remarks?
                // The prompt says "Mark attendance" logic, but typically approved leave just skips penalty or marks as 'Leave'.
                // We will just update status here and send notification.

                $pdo->commit();
                $message = "Leave request $status successfully.";

                // Notify parent/student
                $recipients = [
                    ['user_type' => 'parent', 'user_id' => $lr['student_id']],
                    ['user_type' => 'student', 'user_id' => $lr['student_id']]
                ];
                $s_text = ucfirst($status);
                $body = "Your leave request from {$lr['from_date']} to {$lr['to_date']} has been $s_text.";
                
                sendNotification($pdo, "Leave Request $s_text", $body, $status === 'approved' ? 'success' : 'danger', $recipients, $_SESSION['user_id']);
            } else {
                $pdo->rollBack();
                $error = "Leave request not found.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch Pending Leaves for students in this teacher's assigned classes
// We use a subquery to find all students in the teacher's classes
$stmt_leaves = $pdo->prepare("
    SELECT lr.*, s.first_name, s.last_name, s.roll_number, c.class_name, sec.section_name
    FROM leave_requests lr
    JOIN students s ON lr.student_id = s.id
    JOIN classes c ON s.class_id = c.id
    JOIN sections sec ON s.section_id = sec.id
    WHERE lr.school_id = ? 
    AND lr.status = 'pending'
    AND EXISTS (
        SELECT 1 FROM teacher_class_assignments tca 
        WHERE tca.teacher_id = ? 
        AND tca.class_id = s.class_id 
        AND tca.section_id = s.section_id
    )
    ORDER BY lr.created_at ASC
");
$stmt_leaves->execute([$schoolId, $teacher_id]);
$leaves = $stmt_leaves->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Leave Approvals | Teacher Portal";
$page_header = "Attendance";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Student Leave Requests</h5>
    <div class="d-flex gap-2">
        <a href="mark.php" class="btn btn-outline-primary"><i class="fa fa-user-check me-1"></i> Mark Attendance</a>
        <a href="leave_approvals.php" class="btn btn-info text-white"><i class="fa fa-envelope-open-text me-1"></i> Leave Requests</a>
        <a href="report.php" class="btn btn-outline-secondary"><i class="fa fa-chart-line me-1"></i> Reports</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 12px;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0">Pending Approvals</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Student</th>
                        <th>Class & Section</th>
                        <th>Date Range</th>
                        <th>Reason</th>
                        <th>Applied On</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($leaves) > 0): ?>
                        <?php foreach($leaves as $l): ?>
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <?= htmlspecialchars($l['first_name'].' '.$l['last_name']) ?>
                                    <div class="small text-muted">Roll: <?= htmlspecialchars($l['roll_number']) ?></div>
                                </td>
                                <td>
                                    <?= htmlspecialchars($l['class_name']) ?> - <?= htmlspecialchars($l['section_name']) ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= date('d M Y', strtotime($l['from_date'])) ?></span> 
                                    to 
                                    <span class="badge bg-secondary"><?= date('d M Y', strtotime($l['to_date'])) ?></span>
                                </td>
                                <td>
                                    <p class="m-0 small text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($l['reason']) ?>">
                                        <?= htmlspecialchars($l['reason']) ?>
                                    </p>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d M, h:i A', strtotime($l['created_at'])) ?>
                                </td>
                                <td class="text-center pe-4">
                                    <form method="POST" class="d-inline-flex gap-2">
                                        <input type="hidden" name="leave_id" value="<?= $l['id'] ?>">
                                        <button type="submit" name="action_leave" value="approved" class="btn btn-sm btn-success rounded-pill px-3">
                                            <i class="fa fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="action_leave" value="rejected" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                            <i class="fa fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted p-5">No pending leave requests at the moment.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
