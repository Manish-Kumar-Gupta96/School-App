<?php
require_once('../config/database.php');
$active_menu = "syllabus";
$page_title = "My Syllabus Status | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student class
$stmt = $pdo->prepare("SELECT class FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$class = $student['class'] ?? '';

// Get syllabus
$stmt = $pdo->prepare("
    SELECT * FROM syllabus
    WHERE class=?
    ORDER BY subject ASC, id DESC
");
$stmt->execute([$class]);
$syllabus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📘 Syllabus Status</h2>
        <p class="text-muted mb-0">Class: <strong>Class <?= htmlspecialchars($class) ?></strong> | Monitor subject curriculum coverage and learning goals.</p>
    </div>
</div>

<div class="row g-4 text-start">
    <?php if(count($syllabus) > 0): ?>
        <?php foreach($syllabus as $s): ?>
            <?php
            $percent = (int)$s['completion_percent'];
            $color = 'danger';
            if ($percent >= 80) $color = 'success';
            elseif ($percent >= 50) $color = 'info';
            elseif ($percent >= 30) $color = 'warning';
            ?>
            <div class="col-md-6">
                <div class="card shadow-sm border-0 p-4 h-100" style="border-radius: 12px; border-left: 4px solid var(--bs-<?= $color ?>) !important;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary mb-2"><?= htmlspecialchars($s['subject']) ?></span>
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1.1rem;"><?= htmlspecialchars($s['topic']) ?></h5>
                        </div>
                        <h4 class="fw-bold text-<?= $color ?> mb-0"><?= $percent ?>%</h4>
                    </div>
                    
                    <p class="text-muted small mb-4"><?= htmlspecialchars($s['description'] ?: 'No details provided.') ?></p>
                    
                    <div class="mt-auto">
                        <div class="progress" style="height: 10px; border-radius: 5px;">
                            <div class="progress-bar bg-<?= $color ?>" role="progressbar" style="width: <?= $percent ?>%"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small mt-2" style="font-size: 0.75rem;">
                            <span>Updated: <?= date('d M Y', strtotime($s['updated_at'])) ?></span>
                            <span>Target Completion</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center shadow-sm py-5 border-0">
                <i class="fa fa-info-circle me-2 fs-3 mb-2 d-block"></i> No syllabus progress logs entered for Class <?= htmlspecialchars($class) ?>.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>
