<?php
require_once('../config/database.php');
$active_menu = "syllabus";
$page_title = "Syllabus Management | Teacher Portal";
require_once('includes/header.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $class       = trim($_POST['class']);
    $subject     = trim($_POST['subject']);
    $topic       = trim($_POST['topic']);
    $description = trim($_POST['description']);
    $percent     = (int)$_POST['percent'];

    if(empty($class) || empty($subject) || empty($topic)){
        $error = "Class, Subject, and Topic are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO syllabus (class, subject, topic, description, completion_percent, updated_by)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([
            $class,
            $subject,
            $topic,
            $description,
            $percent,
            $_SESSION['teacher_id']
        ]);
        $message = "Syllabus updated successfully!";
    }
}

$data = $pdo->query("SELECT * FROM syllabus ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📘 Syllabus Management</h2>
        <p class="text-muted mb-0">Record and track curriculum progress, topics, and completion status.</p>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-4 text-start">
    <!-- Form -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
            <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-book-open me-2 text-success"></i>Update Syllabus Status</h5>
            
            <form method="POST" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Class</label>
                    <input type="text" name="class" class="form-control" placeholder="e.g. 10-A" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subject</label>
                    <input type="text" name="subject" class="form-control" placeholder="e.g. Science" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Topic / Chapter Title</label>
                    <input type="text" name="topic" class="form-control" placeholder="e.g. Chapter 4: Carbon Compounds" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Brief Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Enter key topics covered..."></textarea>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Completion Percentage (0-100%)</label>
                    <input type="number" name="percent" class="form-control" placeholder="e.g. 75" min="0" max="100" required>
                </div>

                <div class="col-md-12 mt-4 text-end">
                    <button type="submit" name="save" class="btn btn-success w-100 py-2 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save Syllabus Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- History Logs -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-history me-2 text-primary"></i>Syllabus Updates Logs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Class & Subject</th>
                                <th>Topic</th>
                                <th>Completion Bar</th>
                                <th class="pe-4">Updated At</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($data) > 0): ?>
                                <?php foreach($data as $s): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <span class="badge bg-secondary-subtle text-secondary me-2">Class <?= htmlspecialchars($s['class']) ?></span>
                                            <strong class="text-dark"><?= htmlspecialchars($s['subject']) ?></strong>
                                        </td>
                                        <td class="text-muted small">
                                            <strong><?= htmlspecialchars($s['topic']) ?></strong>
                                            <div class="text-muted text-truncate" style="max-width: 150px;"><?= htmlspecialchars($s['description']) ?></div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 10px; border-radius: 5px;">
                                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= (int)$s['completion_percent'] ?>%"></div>
                                                </div>
                                                <small class="fw-bold text-success"><?= (int)$s['completion_percent'] ?>%</small>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-muted small"><?= date('d M Y', strtotime($s['updated_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No syllabus data entered yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
