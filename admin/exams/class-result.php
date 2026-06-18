<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$exam_id = $_GET['exam_id'] ?? '';
$class   = $_GET['class'] ?? '';

$results = [];
$exam_info = null;

if($exam_id && $class){
    // Fetch exam info
    $stmt_e = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
    $stmt_e->execute([$exam_id]);
    $exam_info = $stmt_e->fetch(PDO::FETCH_ASSOC);

    // Fetch class performance summary
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.admission_no,
            s.first_name,
            s.last_name,
            SUM(m.obtained_marks) as obtained,
            SUM(m.total_marks) as total,
            -- Check if they failed in any subject (obtained_marks < 33)
            SUM(CASE WHEN m.obtained_marks < 33 THEN 1 ELSE 0 END) as failed_subjects
        FROM students s
        INNER JOIN marks m ON s.id = m.student_id
        WHERE m.exam_id = ? AND s.class = ?
        GROUP BY s.id
        ORDER BY obtained DESC
    ");
    $stmt->execute([$exam_id, $class]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all exams for filter dropdown
$exams = $pdo->query("SELECT id, exam_name, class_name FROM exams ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch classes
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Layout setup
$root_path = "../../";
$page_title = "Class Merit Results | VIC ERP";
$page_header = "Class Merit Ledger";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Review class performance, topper metrics, and rank listings</h5>
    <a href="create-exam.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Exams
    </a>
</div>

<!-- FILTER CARD -->
<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Exam</label>
                <select name="exam_id" class="form-select" required>
                    <option value="">Select Exam</option>
                    <?php foreach($exams as $ex): ?>
                        <option value="<?= $ex['id'] ?>" <?= ($exam_id == $ex['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ex['exam_name']) ?> (<?= htmlspecialchars($ex['class_name']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-5">
                <label class="form-label fw-semibold">Class</label>
                <select name="class" class="form-select" required>
                    <option value="">Select Class</option>
                    <?php if (count($classes) > 0): ?>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($class == $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="Class <?= $i ?>" <?= ($class == "Class $i") ? 'selected' : '' ?>>
                                Class <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">
                    <i class="fa fa-sync me-1"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<?php if($exam_id && $class && count($results) > 0): ?>
    <!-- TOPPER HIGHLIGHT BANNER -->
    <?php 
    $topper = $results[0];
    $topper_pct = $topper['total'] ? round(($topper['obtained'] / $topper['total']) * 100, 2) : 0;
    ?>
    <div class="alert alert-success border-0 shadow-sm p-4 mb-4 d-flex align-items-center justify-content-between" style="border-radius: 12px; background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
        <div>
            <h4 class="alert-heading fw-bold text-success mb-1"><i class="fa fa-trophy me-2 text-warning fs-3 animate__animated animate__bounce"></i> Class Topper!</h4>
            <p class="mb-0 text-dark-50">Congratulations to <strong><?= htmlspecialchars($topper['first_name'] . ' ' . $topper['last_name']) ?></strong> for securing Rank 1 with an outstanding score of <strong><?= $topper['obtained'] ?> / <?= $topper['total'] ?></strong> (<?= $topper_pct ?>%)!</p>
        </div>
        <div class="d-none d-md-block text-end">
            <span class="badge bg-success text-white px-3 py-2 fs-6">Score: <?= $topper_pct ?>%</span>
        </div>
    </div>

    <!-- MERIT TABLE -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">Merit Standings for <?= htmlspecialchars($exam_info['exam_name'] ?? '') ?></h5>
            <button onclick="window.print()" class="btn btn-sm btn-outline-primary no-print"><i class="fa fa-print me-1"></i> Print Sheet</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4" width="80">Rank</th>
                            <th>Admission No</th>
                            <th>Student Name</th>
                            <th>Total Marks</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th class="text-center" width="220">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php 
                        $rank = 1;
                        foreach($results as $row): 
                            $pct = $row['total'] ? round(($row['obtained'] / $row['total']) * 100, 2) : 0;
                            $failed = (int)$row['failed_subjects'] > 0;
                            $status_text = $failed ? 'FAIL' : 'PASS';
                            $status_class = $failed ? 'danger' : 'success';
                        ?>
                            <tr>
                                <td class="ps-4 fw-bold">
                                    <?php if ($rank == 1): ?>
                                        <span class="badge bg-warning text-dark"><i class="fa fa-medal me-1"></i> 1st</span>
                                    <?php elseif ($rank == 2): ?>
                                        <span class="badge bg-secondary text-white">2nd</span>
                                    <?php elseif ($rank == 3): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">3rd</span>
                                    <?php else: ?>
                                        <span class="text-muted ms-2"><?= $rank ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['admission_no']) ?></span></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= $row['total'] ?></td>
                                <td class="fw-bold text-primary"><?= $row['obtained'] ?></td>
                                <td class="fw-bold text-success"><?= $pct ?>%</td>
                                <td>
                                    <span class="badge bg-<?= $status_class ?>-subtle text-<?= $status_class ?> border border-<?= $status_class ?>-subtle px-3 py-2">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="report-card.php?student_id=<?= $row['id'] ?>&exam_id=<?= $exam_id ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa fa-file-invoice me-1"></i> Report Card
                                    </a>
                                </td>
                            </tr>
                        <?php 
                            $rank++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif($exam_id && $class): ?>
    <div class="alert alert-warning">
        <i class="fa fa-info-circle me-2"></i> No marks recorded for the selected class and exam. Go to <a href="marks-entry.php?exam_id=<?= htmlspecialchars($exam_id) ?>&class=<?= urlencode($class) ?>" class="alert-link">Marks Entry</a> to record grades.
    </div>
<?php endif; ?>

<?php
require_once('../includes/footer.php');
?>
