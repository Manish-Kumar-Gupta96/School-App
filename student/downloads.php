<?php
require_once('../config/database.php');
$active_menu = "downloads";
$page_title = "Download Center | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student class
$stmt = $pdo->prepare("SELECT class FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$class = $student['class'] ?? '';

// Get files from downloads table
$stmt = $pdo->prepare("
    SELECT * FROM downloads
    WHERE class=? OR class IS NULL OR class=''
    ORDER BY id DESC
");
$stmt->execute([$class]);
$downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📥 Download Center</h2>
        <p class="text-muted mb-0">Class: <strong>Class <?= htmlspecialchars($class) ?></strong> | Download academic syllabus guides, holidays schedules, or files circulars.</p>
    </div>
</div>

<div class="row g-4 text-start">
    <?php if(count($downloads) > 0): ?>
        <?php foreach($downloads as $d): ?>
            <?php
            $ext = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
            $icon = 'fa-file-arrow-down';
            $badgeColor = 'success';
            if ($d['category'] == 'HOLIDAY') { $icon = 'fa-calendar-alt'; $badgeColor = 'warning'; }
            elseif ($d['category'] == 'SYLLABUS') { $icon = 'fa-book-open'; $badgeColor = 'info'; }
            elseif ($d['category'] == 'FORM') { $icon = 'fa-file-invoice'; $badgeColor = 'primary'; }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 card-hover p-4" style="border-radius: 12px;">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="p-3 bg-light rounded-circle text-<?= $badgeColor ?>">
                            <i class="fa <?= $icon ?> fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> mb-2"><?= htmlspecialchars($d['category']) ?></span>
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;"><?= htmlspecialchars($d['title']) ?></h5>
                            <p class="text-muted small mb-0">Sub Category: <strong><?= htmlspecialchars($d['sub_category'] ?: '-') ?></strong></p>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 text-muted small d-flex justify-content-between align-items-center mt-auto">
                        <div>
                            <i class="fa fa-clock me-1"></i> <?= date('d M Y', strtotime($d['created_at'])) ?>
                        </div>
                        <a href="../<?= htmlspecialchars($d['file_path']) ?>" class="btn btn-outline-success btn-sm px-3" download>
                            <i class="fa fa-download"></i> Download File
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center shadow-sm py-5 border-0">
                <i class="fa fa-info-circle me-2 fs-3 mb-2 d-block"></i> No downloadable resources available at this time.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>
