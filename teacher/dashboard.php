<?php
require_once('../config/database.php');
require_once('includes/header.php');

$teacher_id = $_SESSION['teacher_id'];

/* ==========================
TEACHER INFO
========================== */
$stmt = $pdo->prepare("SELECT * FROM teachers WHERE id=?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

/* ==========================
ASSIGNED CLASSES & SUBJECTS
========================== */
$stmt_tca = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name, sub.subject_name
    FROM teacher_class_assignments tca
    JOIN classes c ON tca.class_id = c.id
    JOIN sections s ON tca.section_id = s.id
    JOIN subjects sub ON tca.subject_id = sub.id
    WHERE tca.teacher_id = ?
");
$stmt_tca->execute([$teacher_id]);
$subjectClasses = $stmt_tca->fetchAll(PDO::FETCH_ASSOC);

$stmt_ct = $pdo->prepare("
    SELECT DISTINCT c.class_name, s.section_name
    FROM class_teachers ct
    JOIN classes c ON ct.class_id = c.id
    JOIN sections s ON ct.section_id = s.id
    WHERE ct.teacher_id = ?
");
$stmt_ct->execute([$teacher_id]);
$inchargeClasses = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
STATS
========================== */
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalAttendanceMarked = $pdo->query("SELECT COUNT(*) FROM student_attendance")->fetchColumn();
$totalMarksEntered = $pdo->query("SELECT COUNT(*) FROM student_progress")->fetchColumn();

/* ==========================
LEAVE STATUS
========================== */
$leaves = $pdo->prepare("
    SELECT * 
    FROM employee_leaves 
    WHERE employee_id=? 
    ORDER BY id DESC LIMIT 5
");
$leaves->execute([$teacher_id]);
$leaveList = $leaves->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">Welcome, <?= htmlspecialchars($teacher['name']) ?>!</h2>
        <p class="text-muted mb-0">Here is your daily class schedule and management summary</p>
    </div>
    <span class="badge bg-success px-3 py-2 fw-semibold">ID: <?= htmlspecialchars($teacher['employee_id']) ?></span>
</div>

<!-- STATS CARDS -->
<div class="row g-4 mb-4">
    <!-- Total Students -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Total Students</h6>
                <i class="fa fa-user-graduate text-primary fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalStudents ?></h3>
            <p class="text-muted small mb-0 mt-1">Total active enrollments in ERP</p>
        </div>
    </div>

    <!-- Attendance Entries -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Attendance Entries</h6>
                <i class="fa fa-calendar-check text-success fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalAttendanceMarked ?></h3>
            <p class="text-muted small mb-0 mt-1">Total attendance marks saved</p>
        </div>
    </div>

    <!-- Marks Entries -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm card-hover p-4 text-start" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-muted small text-uppercase fw-bold mb-0">Marks Entries</h6>
                <i class="fa fa-edit text-warning fs-4"></i>
            </div>
            <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalMarksEntered ?></h3>
            <p class="text-muted small mb-0 mt-1">Total exam marks files saved</p>
        </div>
    </div>
</div>

<!-- CLASSES & LEAVE STATUS -->
<div class="row g-4">
    <!-- Assigned Classes -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 p-4 text-start" style="border-radius: 15px; height: 100%;">
            <h5 class="fw-bold text-dark mb-4"><i class="fa fa-school me-2 text-primary"></i>Assigned Classes</h5>
            
            <?php if (count($inchargeClasses) > 0): ?>
                <div class="mb-3">
                    <span class="text-muted small fw-bold text-uppercase d-block mb-2">Class Incharge (Class Teacher)</span>
                    <div class="row g-2">
                        <?php foreach($inchargeClasses as $ic): ?>
                            <div class="col-12">
                                <div class="alert alert-success d-flex align-items-center mb-0 fw-bold border-0 py-2 px-3" style="border-radius: 8px; font-size: 0.9rem;">
                                    <i class="fa fa-award me-2 text-success"></i> Class <?= htmlspecialchars($ic['class_name']) ?> - Section <?= htmlspecialchars($ic['section_name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <span class="text-muted small fw-bold text-uppercase d-block mb-2">Subject Assignments</span>
                <div class="row g-2">
                    <?php if (count($subjectClasses) > 0): ?>
                        <?php foreach($subjectClasses as $sc): ?>
                            <div class="col-12">
                                <div class="alert alert-primary d-flex align-items-center justify-content-between mb-0 fw-bold border-0 py-2 px-3" style="border-radius: 8px; font-size: 0.9rem;">
                                    <span><i class="fa fa-book-open me-2 text-primary"></i> Class <?= htmlspecialchars($sc['class_name']) ?> - Sec <?= htmlspecialchars($sc['section_name']) ?></span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= htmlspecialchars($sc['subject_name']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-4 text-muted small">No subject assignments.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Requests -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden; height: 100%;">
            <div class="card-header bg-white border-0 py-3 ps-4 text-start">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-plane-departure me-2 text-success"></i>Recent Leave Requests</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th class="pe-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($leaveList) > 0): ?>
                                <?php foreach($leaveList as $l): ?>
                                    <?php
                                    $badge = 'warning';
                                    if($l['status'] == 'Approved') $badge = 'success';
                                    elseif($l['status'] == 'Rejected') $badge = 'danger';
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($l['leave_type']) ?></td>
                                        <td><?= date('d M Y', strtotime($l['from_date'])) ?></td>
                                        <td><?= date('d M Y', strtotime($l['to_date'])) ?></td>
                                        <td class="pe-4 text-center">
                                            <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                                <?= htmlspecialchars($l['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No recent leave requests logged.
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
require_once('includes/footer.php');
?>
