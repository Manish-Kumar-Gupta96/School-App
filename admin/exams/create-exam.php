<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_exam'])){
    $exam_type_id = $_POST['exam_type_id'];
    $exam_name    = trim($_POST['exam_name']);
    $class_name   = $_POST['class_name'];
    $session_year = $_POST['session_year'];
    $exam_date    = $_POST['exam_date'];
    $total_marks  = (int)$_POST['total_marks'];
    $passing_marks= (int)$_POST['passing_marks'];
    $status       = $_POST['status'];

    if(empty($exam_type_id) || empty($exam_name) || empty($class_name) || empty($exam_date)){
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO exams(
                exam_type_id,
                exam_name,
                class_name,
                session_year,
                exam_date,
                total_marks,
                passing_marks,
                status,
                result_status
            )
            VALUES(?,?,?,?,?,?,?,?,'Draft')
        ");
        $stmt->execute([
            $exam_type_id,
            $exam_name,
            $class_name,
            $session_year,
            $exam_date,
            $total_marks,
            $passing_marks,
            $status
        ]);
        $message = "Exam Created Successfully";
    }
}

// Fetch active exam types
$examTypes = $pdo->query("
    SELECT *
    FROM exam_types
    WHERE status='Active'
    ORDER BY exam_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct classes from database for dropdown fallback, else standard
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$db_classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Fetch created exams list
$exams = $pdo->query("
    SELECT e.*, et.exam_name as type_name
    FROM exams e
    LEFT JOIN exam_types et ON e.exam_type_id = et.id
    ORDER BY e.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Exams Dashboard | VIC ERP";
$page_header = "Exams Management";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Create and monitor classroom examinations</h5>
    <a href="exam-types.php" class="btn btn-outline-primary">
        <i class="fa fa-cogs me-1"></i> Exam Types
    </a>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- CREATE EXAM CARD -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Create New Exam</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Type <span class="text-danger">*</span></label>
                        <select name="exam_type_id" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach($examTypes as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['exam_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Title Name <span class="text-danger">*</span></label>
                        <input type="text" name="exam_name" class="form-control" placeholder="e.g. Half Yearly Examination 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Class <span class="text-danger">*</span></label>
                        <select name="class_name" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php if(count($db_classes) > 0): ?>
                                <?php foreach($db_classes as $cls): ?>
                                    <option value="<?= htmlspecialchars($cls) ?>"><?= htmlspecialchars($cls) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="Class <?= $i ?>">Class <?= $i ?></option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Academic Session</label>
                        <input type="text" name="session_year" value="2026-2027" class="form-control" placeholder="e.g. 2026-2027" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Exam Date <span class="text-danger">*</span></label>
                        <input type="date" name="exam_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Total Marks <span class="text-danger">*</span></label>
                            <input type="number" name="total_marks" value="100" class="form-control" required min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Passing Marks <span class="text-danger">*</span></label>
                            <input type="number" name="passing_marks" value="33" class="form-control" required min="1">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Upcoming">Upcoming</option>
                            <option value="Running">Running</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>

                    <button type="submit" name="save_exam" class="btn btn-success w-100">
                        <i class="fa fa-plus-circle me-1"></i> Create Exam
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST EXAMS CARD -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Exam Details</th>
                                <th>Class</th>
                                <th>Session</th>
                                <th>Date</th>
                                <th>Marks</th>
                                <th>Result</th>
                                <th class="text-center" width="280">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($exams) > 0): ?>
                                <?php foreach($exams as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['exam_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($row['type_name']) ?></span>
                                        </td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['class_name']) ?></td>
                                        <td><?= htmlspecialchars($row['session_year']) ?></td>
                                        <td><?= date('d M Y', strtotime($row['exam_date'])) ?></td>
                                        <td>
                                            <span class="text-muted small d-block">Total: <?= $row['total_marks'] ?></span>
                                            <span class="text-muted small d-block">Pass: <?= $row['passing_marks'] ?></span>
                                        </td>
                                        <td>
                                            <?php if($row['result_status'] == 'Published'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Published</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex gap-2 justify-content-center">
                                                <a href="marks-entry.php?exam_id=<?= $row['id'] ?>&class=<?= urlencode($row['class_name']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-edit"></i> Marks
                                                </a>
                                                <a href="class-result.php?exam_id=<?= $row['id'] ?>&class=<?= urlencode($row['class_name']) ?>" class="btn btn-sm btn-outline-info">
                                                    <i class="fa fa-chart-bar"></i> Results
                                                </a>
                                                <a href="publish-result.php?exam_id=<?= $row['id'] ?>" class="btn btn-sm <?= ($row['result_status'] == 'Published') ? 'btn-outline-secondary' : 'btn-success' ?>">
                                                    <i class="fa <?= ($row['result_status'] == 'Published') ? 'fa-eye-slash' : 'fa-eye' ?>"></i> 
                                                    <?= ($row['result_status'] == 'Published') ? 'Unpublish' : 'Publish' ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-file-signature fs-2 mb-2 d-block"></i>
                                        No exams scheduled.
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
require_once('../includes/footer.php');
?>
