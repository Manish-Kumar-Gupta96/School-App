<?php
require_once('../config/database.php');
$active_menu = "assignments";
$page_title = "My Assignments | Student Portal";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Get student class
$stmt = $pdo->prepare("SELECT class FROM students WHERE id=?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
$class = $student['class'] ?? '';

// Get assignments
$stmt = $pdo->prepare("
    SELECT * FROM assignments
    WHERE class=?
    ORDER BY id DESC
");
$stmt->execute([$class]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my submissions status
$stmt_sub = $pdo->prepare("SELECT assignment_id, status FROM assignment_submissions WHERE student_id=?");
$stmt_sub->execute([$student_id]);
$submissions = $stmt_sub->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📝 My Assignments</h2>
        <p class="text-muted mb-0">Class: <strong>Class <?= htmlspecialchars($class) ?></strong> | Track homework tasks, download attachments, and submit answers.</p>
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
                            <h4 class="fw-bold text-dark mb-1" style="font-size: 1.25rem;"><?= htmlspecialchars($a['title']) ?></h4>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;"><i class="fa fa-calendar-alt me-1"></i> Due Date: <strong><?= date('d M Y', strtotime($a['due_date'])) ?></strong></p>
                        </div>
                        <div>
                            <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> border border-<?= $badgeColor ?>-subtle px-3 py-2 fw-semibold" style="font-size: 0.8rem;">
                                <?= $subStatus ?>
                            </span>
                        </div>
                    </div>

                    <p class="text-secondary small mb-4" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($a['description'])) ?></p>

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                        <?php if($a['file_path']): ?>
                            <a href="../<?= htmlspecialchars($a['file_path']) ?>" class="btn btn-outline-primary btn-sm" download>
                                <i class="fa fa-download me-1"></i> Download Homework Files
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- SUBMIT FORM -->
                    <?php if($subStatus == 'PENDING'): ?>
                        <div class="border-top pt-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="fa fa-paper-plane me-2 text-primary"></i>Submit Your Answer</h6>
                            <form method="POST" action="submit-assignment.php" enctype="multipart/form-data" class="row g-3">
                                <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold text-muted small">Write Answer text / Comments</label>
                                    <textarea name="text" class="form-control" rows="3" placeholder="Enter text answers, links, or notes..." required></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-muted small">Attach Submission File (Optional)</label>
                                    <input type="file" name="file" class="form-control">
                                </div>

                                <div class="col-md-6 text-end align-self-end">
                                    <button type="submit" class="btn btn-success px-5 py-2 fw-semibold w-100 w-md-auto">
                                        <i class="fa fa-save me-1"></i> Submit Assignment
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="border-top pt-3 text-muted small">
                            <i class="fa fa-check-circle me-1 text-success"></i> Submission recorded. You cannot edit submitted assignments.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center shadow-sm py-5 border-0">
                <i class="fa fa-info-circle me-2 fs-3 mb-2 d-block"></i> No assignments found for Class <?= htmlspecialchars($class) ?>.
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once('includes/footer.php'); ?>
