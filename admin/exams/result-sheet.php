<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['student_id']) || !isset($_GET['exam_id'])){
    die("Invalid Request. Student ID and Exam ID are required.");
}

$student_id = (int)$_GET['student_id'];
$exam_id    = (int)$_GET['exam_id'];

/* ==========================
STUDENT INFO
========================== */
$studentStmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$studentStmt->execute([$student_id]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student Not Found");
}

/* ==========================
EXAM INFO
========================== */
$examStmt = $pdo->prepare("SELECT * FROM exams WHERE id=?");
$examStmt->execute([$exam_id]);
$exam = $examStmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam Not Found");
}

/* ==========================
MARKS DATA
========================== */
$marksStmt = $pdo->prepare("
    SELECT m.*, s.subject_name, s.subject_code, s.passing_marks as subj_pass_marks
    FROM marks m
    LEFT JOIN subjects s ON m.subject_id=s.id
    WHERE m.student_id=? AND m.exam_id=?
");
$marksStmt->execute([$student_id, $exam_id]);
$marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

$totalObtained = 0;
$totalMarks    = 0;
$isFailed      = false;

foreach($marks as $row){
    $totalObtained += $row['obtained_marks'];
    $totalMarks += $row['total_marks'];

    // Check if obtained marks is below subject passing marks
    $pass_threshold = $row['subj_pass_marks'] ?: 33;
    if($row['obtained_marks'] < $pass_threshold){
        $isFailed = true;
    }
}

$percentage = $totalMarks ? round(($totalObtained/$totalMarks)*100, 2) : 0;

function calculateGrade($percentage){
    if($percentage>=90) return "A+";
    if($percentage>=80) return "A";
    if($percentage>=70) return "B+";
    if($percentage>=60) return "B";
    if($percentage>=50) return "C";
    if($percentage>=33) return "D";
    return "F";
}

$grade = calculateGrade($percentage);
$result = $isFailed ? "FAIL" : "PASS";

// Layout setup
$root_path = "../../";
$page_title = "Student Result Sheet | VIC ERP";
$page_header = "Academic Result Sheet";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<style>
    @media print {
        .sidebar, .topbar, .no-print, .btn, footer {
            display: none !important;
        }
        .main {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="text-muted mb-0">Browse and print individual exam summary</h5>
    <div>
        <a href="class-result.php?exam_id=<?= $exam_id ?>&class=<?= urlencode($student['class']) ?>" class="btn btn-secondary me-2">
            <i class="fa fa-arrow-left me-1"></i> Back to Ledger
        </a>
        <button onclick="window.print()" class="btn btn-success me-2">
            <i class="fa fa-print me-1"></i> Print Result
        </button>
        <a href="report-card.php?student_id=<?= $student_id ?>&exam_id=<?= $exam_id ?>" class="btn btn-primary">
            <i class="fa fa-file-invoice me-1"></i> Premium Report Card
        </a>
    </div>
</div>

<div class="card shadow border-0" style="border-radius: 15px;">
    <div class="card-body p-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark mb-1">VIC SCHOOL</h2>
            <h5 class="text-muted tracking-widest text-uppercase">Academic Result Slip</h5>
        </div>

        <hr class="mb-4">

        <div class="row g-3 text-dark mb-4">
            <div class="col-md-6">
                <strong>Student Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
            </div>
            <div class="col-md-6">
                <strong>Class:</strong> <?= htmlspecialchars($student['class']) ?>
            </div>
            <div class="col-md-6">
                <strong>Admission No:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($student['admission_no']) ?></span>
            </div>
            <div class="col-md-6">
                <strong>Exam Name:</strong> <?= htmlspecialchars($exam['exam_name']) ?>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th class="text-start ps-4">Subject</th>
                        <th>Obtained Marks</th>
                        <th>Total Marks</th>
                        <th>Grade</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($marks) > 0): ?>
                        <?php foreach($marks as $row): 
                            $subj_pct = $row['total_marks'] ? round(($row['obtained_marks'] / $row['total_marks']) * 100, 2) : 0;
                            $subj_grade = calculateGrade($subj_pct);
                            $is_failed_subj = $row['obtained_marks'] < ($row['subj_pass_marks'] ?: 33);
                        ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['subject_name']) ?> (<?= htmlspecialchars($row['subject_code']) ?>)</td>
                                <td class="text-center <?= $is_failed_subj ? 'text-danger fw-bold' : '' ?>"><?= htmlspecialchars($row['obtained_marks']) ?></td>
                                <td class="text-center"><?= htmlspecialchars($row['total_marks']) ?></td>
                                <td class="text-center"><span class="badge bg-<?= $is_failed_subj ? 'danger' : 'success' ?>-subtle text-<?= $is_failed_subj ? 'danger' : 'success' ?> px-2 py-1"><?= $subj_grade ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No marks registered for this exam.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td class="text-start ps-4">Total Aggregate</td>
                        <td class="text-center text-primary"><?= $totalObtained ?></td>
                        <td class="text-center"><?= $totalMarks ?></td>
                        <td class="text-center"><span class="badge bg-primary px-3 py-2 fs-6"><?= $grade ?></span></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row text-center g-3 mt-4">
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block">Percentage</span>
                    <span class="fw-bold text-success fs-5"><?= $percentage ?>%</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block">Grade Assigned</span>
                    <span class="fw-bold text-primary fs-5"><?= $grade ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block">Result Status</span>
                    <span class="fw-bold text-<?= $isFailed ? 'danger' : 'success' ?> fs-5"><?= $result ?></span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block">Exam State</span>
                    <span class="fw-bold text-dark fs-5"><?= htmlspecialchars($exam['result_status']) ?></span>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-5 pt-4">
            <div>
                <span class="text-muted small d-block">Generated on: <?= date('d M Y') ?></span>
            </div>
            <div class="text-center" style="border-top: 1px solid #000; width: 220px; padding-top: 5px;">
                <span class="fw-semibold">Principal Signature</span>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
