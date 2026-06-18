<?php
require_once('../config/database.php');
$active_menu = "results";
$page_title = "My Results Report Card | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student details
$stmt = $pdo->prepare("SELECT *, CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get unique exams taken by student
$exams = $pdo->prepare("SELECT DISTINCT exam_name FROM student_marks WHERE student_id=?");
$exams->execute([$student_id]);
$examList = $exams->fetchAll(PDO::FETCH_ASSOC);

$selected_exam = $_GET['exam'] ?? '';
$results = [];
$total_obtained = 0;
$total_marks = 0;

if($selected_exam){
    $stmt = $pdo->prepare("
        SELECT * FROM student_marks
        WHERE student_id=? AND exam_name=?
    ");
    $stmt->execute([$student_id, $selected_exam]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($results as $r){
        $total_obtained += (float)$r['marks_obtained'];
        $total_marks += (float)$r['total_marks'];
    }
}

$percentage = ($total_marks > 0) ? round(($total_obtained / $total_marks) * 100, 2) : 0;

if($percentage >= 90) $final_grade = "A+";
elseif($percentage >= 75) $final_grade = "A";
elseif($percentage >= 60) $final_grade = "B";
elseif($percentage >= 40) $final_grade = "C";
else $final_grade = "F";
?>

<style>
@media print{
    .sidebar, .navbar, .no-print, header, footer, .top-bar {
        display: none !important;
    }
    .main {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .report-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
.report-card {
    max-width: 800px;
    background: white;
    border-radius: 12px;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 text-start no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">📊 Student Result Card</h2>
        <p class="text-muted mb-0">Select an exam to view and print your official academic performance report.</p>
    </div>
</div>

<!-- FILTER CARD (NO PRINT) -->
<div class="card shadow-sm border-0 p-4 mb-4 text-start no-print" style="border-radius: 12px; max-width: 800px;">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Select Examination</label>
            <select name="exam" class="form-select" required>
                <option value="">Select Exam</option>
                <?php foreach($examList as $e): ?>
                    <option value="<?= htmlspecialchars($e['exam_name']) ?>" <?= ($selected_exam == $e['exam_name']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($e['exam_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-eye me-1"></i> View Result Card
            </button>
        </div>
    </form>
</div>

<!-- REPORT CARD -->
<?php if($selected_exam && count($results) > 0): ?>
    <div class="card shadow-sm border p-5 report-card text-start mx-auto mb-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary mb-1">🏫 <?= htmlspecialchars(CURRENT_SCHOOL_NAME) ?></h3>
            <h5 class="text-secondary fw-semibold">Official Academic Transcript</h5>
            <div class="mt-4 row text-start">
                <div class="col-md-6">
                    <p class="mb-1 text-muted">Student Name: <strong class="text-dark"><?= htmlspecialchars($student['student_name']) ?></strong></p>
                    <p class="mb-1 text-muted">Roll Number: <strong class="text-dark"><?= htmlspecialchars($student['roll_no'] ?: '-') ?></strong></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1 text-muted">Class Group: <strong class="text-dark">Class <?= htmlspecialchars($student['class']) ?></strong></p>
                    <p class="mb-1 text-muted">Exam Session: <strong class="text-dark"><?= htmlspecialchars($selected_exam) ?></strong></p>
                </div>
            </div>
        </div>
        
        <hr class="mb-4">

        <table class="table table-bordered table-striped align-middle text-center mb-4">
            <thead class="table-dark">
                <tr>
                    <th class="text-start ps-3">Subject</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($results as $r): ?>
                    <?php
                    $subj_percent = ($r['total_marks'] > 0) ? round(($r['marks_obtained'] / $r['total_marks']) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td class="text-start ps-3 fw-bold"><?= htmlspecialchars($r['subject']) ?></td>
                        <td><?= number_format($r['marks_obtained'], 1) ?></td>
                        <td><?= number_format($r['total_marks'], 0) ?></td>
                        <td class="fw-semibold text-primary"><?= $subj_percent ?>%</td>
                        <td>
                            <span class="badge <?= $r['grade'] == 'F' ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?> px-2 py-1">
                                <?= htmlspecialchars($r['grade']) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="row g-3 text-center border-top pt-4">
            <div class="col-md-4">
                <h6 class="text-muted text-uppercase small">Total Score</h6>
                <h4 class="fw-bold text-dark"><?= $total_obtained ?> / <?= $total_marks ?></h4>
            </div>
            
            <div class="col-md-4">
                <h6 class="text-muted text-uppercase small">Aggregate Percentage</h6>
                <h4 class="fw-bold text-primary"><?= $percentage ?>%</h4>
            </div>

            <div class="col-md-4">
                <h6 class="text-muted text-uppercase small">Final Grade</h6>
                <h4 class="fw-bold <?= $final_grade == 'F' ? 'text-danger' : 'text-success' ?>"><?= $final_grade ?></h4>
            </div>
        </div>

        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-success px-5 py-2 fw-semibold">
                <i class="fa fa-print me-1"></i> Print Report Card
            </button>
        </div>
    </div>
<?php elseif($selected_exam): ?>
    <div class="alert alert-warning text-center shadow-sm py-4 border-0 no-print" style="max-width: 800px; margin: auto;">
        <i class="fa fa-info-circle me-2"></i> No marks recorded for <strong><?= htmlspecialchars($selected_exam) ?></strong>.
    </div>
<?php endif; ?>

<?php require_once('includes/footer.php'); ?>
