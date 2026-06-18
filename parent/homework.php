<?php
require_once('../config/database.php');
$active_menu = "homework";
$page_title = "Child Homework Tasks | Parent Portal";
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

$assignments = [];
$submissions = [];

if($child){
    // Get assignments
    $stmt = $pdo->prepare("
        SELECT * FROM assignments
        WHERE class=?
        ORDER BY id DESC
    ");
    $stmt->execute([$child['class']]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get submissions
    $stmt_sub = $pdo->prepare("SELECT assignment_id, status FROM assignment_submissions WHERE student_id=?");
    $stmt_sub->execute([$child['id']]);
    $submissions = $stmt_sub->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📝 Child Homework Tracker</h2>
        <p class="text-muted mb-0">Student: <strong><?= htmlspecialchars($child['student_name'] ?? 'N/A') ?></strong> | Class: <strong><?= htmlspecialchars($child['class'] ?? 'N/A') ?></strong></p>
    </div>
</div>

<div class="row g-4 text-start">
    <?php if(count($assignments) > 0): ?>
        <?php foreach($assignments as $a): ?>
            <?php
            $subStatus = $submissions[$a['id']] ?? 'PENDING';
            $badgeColor = 'danger';
            if ($subStatus == 'SUBMITTED') $badgeColor = 'warning';
            elseif ($subStatus == 'CHECKED') $badgeColor = 'success';
            ?>
            <div class="col-md-12">
                <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <span class="badge bg-secondary-subtle text-secondary mb-2"><?= htmlspecialchars($a['subject']) ?></span>
                            <h4 class="fw-bold text-dark mb-1" style="font-size: 1.2rem;"><?= htmlspecialchars($a['title']) ?></h4>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;"><i class="fa fa-calendar-alt me-1"></i> Due Date: <strong><?= date('d M Y', strtotime($a['due_date'])) ?></strong></p>
                        </div>
                        <div>
                            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> border border-<?= $badgeColor ?>-subtle px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
                                <?= $subStatus ?>
                            </span>
                        </div>
                    </div>

                    <p class="text-secondary small mb-3"><?= nl2br(htmlspecialchars($a['description'])) ?></p>

                    <?php if($a['file_path']): ?>
                        <div class="pt-2">
                            <a href="../<?= htmlspecialchars($a['file_path']) ?>" class="btn btn-outline-primary btn-sm" download>
                                <i class="fa fa-download me-1"></i> Download Homework Files
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center shadow-sm py-5 border-0">
                <i class="fa fa-info-circle me-2 fs-3 mb-2 d-block"></i> No assignments found for your child's class.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>
