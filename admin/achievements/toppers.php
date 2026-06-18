<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Academic Toppers Report | Admin Control";
$active_menu = "achievements";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

/* GET TOPPERS */
$toppers = $pdo->query("
    SELECT s.id, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.class, s.roll_no,
    SUM(sm.marks_obtained) as total_obtained, SUM(sm.total_marks) as total_max,
    AVG(sm.percentage) as avg_percent
    FROM student_marks sm
    JOIN students s ON sm.student_id = s.id
    WHERE s.school_id = " . CURRENT_SCHOOL_ID . "
    GROUP BY sm.student_id
    ORDER BY avg_percent DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📊 Academic Toppers Leaderboard</h2>
            <p class="text-muted mb-0">Review the top performing students calculated automatically from exam records.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-trophy me-2 text-warning"></i>Toppers List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Rank</th>
                            <th>Student</th>
                            <th>Class Group</th>
                            <th>Total Obtained Score</th>
                            <th class="pe-4">Overall Performance %</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php if(count($toppers) > 0): ?>
                            <?php $rank = 1; foreach($toppers as $t): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <?php if($rank == 1): ?>
                                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fa fa-medal me-1"></i> 1st</span>
                                        <?php elseif($rank == 2): ?>
                                            <span class="badge bg-secondary px-3 py-2">2nd</span>
                                        <?php elseif($rank == 3): ?>
                                            <span class="badge bg-danger-subtle text-danger px-3 py-2">3rd</span>
                                        <?php else: ?>
                                            <span class="text-muted">#<?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?= htmlspecialchars($t['student_name']) ?></strong>
                                        <div class="text-muted small">Roll #<?= htmlspecialchars($t['roll_no'] ?: '-') ?> (ID: <?= (int)$t['id'] ?>)</div>
                                    </td>
                                    <td>Class <?= htmlspecialchars($t['class']) ?></td>
                                    <td class="fw-semibold text-secondary"><?= number_format($t['total_obtained'], 1) ?> / <?= number_format($t['total_max'], 0) ?></td>
                                    <td class="pe-4 fw-bold text-success" style="font-size: 1.1rem;"><?= round($t['avg_percent'], 2) ?>%</td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No academic records found to calculate toppers.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
