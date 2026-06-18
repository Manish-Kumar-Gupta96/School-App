<?php
require_once('../config/database.php');
$active_menu = "marks";
$page_title = "Child Academic Performance | Parent Portal";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];

// Get child details
$stmt = $pdo->prepare("
    SELECT s.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name 
    FROM students s
    JOIN parent_student_map psm ON s.id = psm.student_id
    WHERE psm.parent_id = ? AND s.school_id = ?
");
$stmt->execute([$parent_id, CURRENT_SCHOOL_ID]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    $child = $pdo->query("SELECT *, CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE school_id = " . CURRENT_SCHOOL_ID . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

// Get unique exams taken by student
$exams = [];
if($child){
    $stmt_ex = $pdo->prepare("SELECT DISTINCT exam_name FROM student_marks WHERE student_id=?");
    $stmt_ex->execute([$child['id']]);
    $exams = $stmt_ex->fetchAll(PDO::FETCH_ASSOC);
}

$selected_exam = $_GET['exam'] ?? '';
$results = [];
$total_obtained = 0;
$total_marks = 0;

if($child && $selected_exam){
    $stmt = $pdo->prepare("
        SELECT * FROM student_marks
        WHERE student_id=? AND exam_name=?
    ");
    $stmt->execute([$child['id'], $selected_exam]);
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

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📊 Child Report Cards</h2>
        <p class="text-muted mb-0">Student: <strong><?= htmlspecialchars($child['student_name'] ?? 'N/A') ?></strong> | Class: <strong><?= htmlspecialchars($child['class'] ?? 'N/A') ?></strong></p>
    </div>
</div>

<!-- FILTER -->
<div class="card shadow-sm border-0 p-4 mb-4 text-start" style="border-radius: 12px; max-width: 800px;">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Select Examination Session</label>
            <select name="exam" class="form-select" required>
                <option value="">Select Exam</option>
                <?php foreach($exams as $e): ?>
                    <option value="<?= htmlspecialchars($e['exam_name']) ?>" <?= ($selected_exam == $e['exam_name']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($e['exam_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-eye me-1"></i> Open Performance Log
            </button>
        </div>
    </form>
</div>

<!-- RESULTS CARD -->
<?php if($selected_exam && count($results) > 0): ?>
    <div class="card shadow-sm border p-4 text-start mx-auto mb-5" style="border-radius: 15px; max-width: 800px;">
        <h5 class="fw-bold text-dark mb-4 text-center"><i class="fa fa-scroll me-2 text-primary"></i>Transcript: <?= htmlspecialchars($selected_exam) ?></h5>
        
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
    </div>
<?php elseif($selected_exam): ?>
    <div class="alert alert-warning text-center shadow-sm py-4 border-0" style="max-width: 800px;">
        <i class="fa fa-info-circle me-2"></i> No records found for this exam.
    </div>
<?php endif; ?>

<?php require_once('includes/footer.php'); ?>
