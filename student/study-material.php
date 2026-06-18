<?php
require_once('../config/database.php');
$active_menu = "material";
$page_title = "Study Materials | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student class
$stmt = $pdo->prepare("SELECT class FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$class = $student['class'] ?? '';

// Get materials
$stmt = $pdo->prepare("
    SELECT sm.*, CONCAT(t.name, ' (', t.subject, ')') AS teacher_name 
    FROM study_materials sm
    LEFT JOIN teachers t ON sm.uploaded_by = t.id
    WHERE sm.class=?
    ORDER BY sm.id DESC
");
$stmt->execute([$class]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📚 Study Materials</h2>
        <p class="text-muted mb-0">Class: <strong>Class <?= htmlspecialchars($class) ?></strong> | View and download documents shared by your teachers.</p>
    </div>
</div>

<div class="row g-4 text-start">
    <?php if(count($materials) > 0): ?>
        <?php foreach($materials as $m): ?>
            <?php
            $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
            $icon = 'fa-file-lines';
            $badgeColor = 'secondary';
            if ($ext == 'pdf') { $icon = 'fa-file-pdf'; $badgeColor = 'danger'; }
            elseif (in_array($ext, ['doc', 'docx'])) { $icon = 'fa-file-word'; $badgeColor = 'primary'; }
            elseif (in_array($ext, ['jpg', 'png', 'jpeg'])) { $icon = 'fa-file-image'; $badgeColor = 'success'; }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100 card-hover p-4" style="border-radius: 12px;">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="p-3 bg-light rounded-circle text-<?= $badgeColor ?>">
                            <i class="fa <?= $icon ?> fs-2"></i>
                        </div>
                        <div class="flex-grow-1">
                            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> mb-2"><?= strtoupper($ext) ?> Document</span>
                            <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;"><?= htmlspecialchars($m['title']) ?></h5>
                            <p class="text-muted small mb-0">Subject: <strong><?= htmlspecialchars($m['subject']) ?></strong></p>
                        </div>
                    </div>
                    
                    <div class="border-top pt-3 text-muted small d-flex justify-content-between align-items-center mt-auto">
                        <div>
                            <i class="fa fa-user me-1"></i> <?= htmlspecialchars($m['teacher_name'] ?: 'Faculty Staff') ?>
                        </div>
                        <a href="../<?= htmlspecialchars($m['file_path']) ?>" class="btn btn-outline-primary btn-sm px-3" download>
                            <i class="fa fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center shadow-sm py-5 border-0">
                <i class="fa fa-info-circle me-2 fs-3 mb-2 d-block"></i> No study materials uploaded for Class <?= htmlspecialchars($class) ?> yet.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>
