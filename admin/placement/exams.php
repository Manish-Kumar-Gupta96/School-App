<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Exam Record
if (isset($_POST['save_exam'])) {
    $student_id = (int)$_POST['student_id'];
    $exam_name  = trim($_POST['exam_name']);
    $score      = trim($_POST['score']);
    $rank_no    = trim($_POST['rank_no']);

    if (empty($student_id) || empty($exam_name)) {
        $error = "Student and Exam Name are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO entrance_exams (school_id, student_id, exam_name, score, rank_no)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $exam_name,
            $score,
            $rank_no
        ]);
        $message = "Entrance exam record added successfully!";
    }
}

// Fetch Students for Dropdown
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Exam Records
$stmt_exams = $pdo->prepare("
    SELECT e.*, s.first_name, s.last_name, s.admission_no 
    FROM entrance_exams e
    JOIN students s ON e.student_id = s.id
    WHERE e.school_id = ?
    ORDER BY e.id DESC
");
$stmt_exams->execute([CURRENT_SCHOOL_ID]);
$examsList = $stmt_exams->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Entrance Exams | VIC ERP";
$page_header = "Placement & Career Guidance";
$active_menu = "placement";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage placement drives and career records</h5>
    <div class="d-flex gap-2">
        <a href="companies.php" class="btn btn-outline-primary">
            <i class="fa fa-building me-1"></i> Companies
        </a>
        <a href="sessions.php" class="btn btn-outline-primary">
            <i class="fa fa-person-chalkboard me-1"></i> Career Sessions
        </a>
        <a href="placements.php" class="btn btn-outline-success">
            <i class="fa fa-briefcase me-1"></i> Placements
        </a>
        <a href="exams.php" class="btn btn-info text-white">
            <i class="fa fa-file-signature me-1"></i> Entrance Exams
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add Exam Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add Exam Record</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. JEE Main, NEET, CAT" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Score / Percentile</label>
                        <input type="text" name="score" class="form-control" placeholder="e.g. 98.5">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">All India Rank (AIR)</label>
                        <input type="text" name="rank_no" class="form-control" placeholder="e.g. 1542">
                    </div>

                    <button type="submit" name="save_exam" class="btn btn-info text-white w-100">
                        <i class="fa fa-save me-1"></i> Save Exam Record
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Exams List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Entrance Exams & Results</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student Name</th>
                                <th>Exam Name</th>
                                <th>Score / Percentile</th>
                                <th>Rank</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($examsList) > 0): ?>
                                <?php foreach($examsList as $ex): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($ex['first_name'] . ' ' . $ex['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($ex['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($ex['exam_name']) ?>
                                        </td>
                                        <td>
                                            <?php if ($ex['score']): ?>
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($ex['score']) ?></div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ex['rank_no']): ?>
                                                <span class="badge bg-info text-dark px-2 py-1">AIR <?= htmlspecialchars($ex['rank_no']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-file-signature fs-2 mb-2 d-block"></i>
                                        No exam records found.
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

<?php require_once('../includes/footer.php'); ?>
