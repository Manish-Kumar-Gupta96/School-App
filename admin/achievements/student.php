<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Log Student Achievements | Admin Control";
$active_menu = "achievements";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $student_id = (int)$_POST['student_id'];
    $title      = trim($_POST['title']);
    $desc       = trim($_POST['description']);
    $date       = $_POST['date'];

    if(empty($title) || empty($date) || !$student_id){
        $error = "Student ID, Achievement Title, and Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO student_achievements (student_id, title, description, achievement_date)
            VALUES (?,?,?,?)
        ");
        $stmt->execute([$student_id, $title, $desc, $date]);
        $message = "Student achievement logged successfully!";
    }
}

$data = $pdo->query("
    SELECT sa.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.class 
    FROM student_achievements sa
    JOIN students s ON sa.student_id = s.id
    ORDER BY sa.achievement_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🎓 Student Achievements Log</h2>
            <p class="text-muted mb-0">Record and track academic and extra-curricular student awards or accolades.</p>
        </div>
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

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-award me-2 text-primary"></i>Add Student Record</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Student ID</label>
                        <input type="number" name="student_id" class="form-control" placeholder="e.g. 1" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Achievement Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Science Olympiad 1st Rank" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Enter key details..."></textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Date of Achievement</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-12 mt-4 text-end">
                        <button type="submit" name="save" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa fa-save me-1"></i> Log Achievement
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-secondary"></i>Logged Achievements</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Student Name</th>
                                    <th>Achievement</th>
                                    <th class="pe-4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if(count($data) > 0): ?>
                                    <?php foreach($data as $d): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <strong class="text-dark"><?= htmlspecialchars($d['student_name']) ?></strong>
                                                <div class="text-muted small">Class: <?= htmlspecialchars($d['class']) ?> (ID: <?= (int)$d['student_id'] ?>)</div>
                                            </td>
                                            <td>
                                                <strong class="text-primary"><?= htmlspecialchars($d['title']) ?></strong>
                                                <div class="text-muted small text-truncate" style="max-width: 150px;"><?= htmlspecialchars($d['description']) ?></div>
                                            </td>
                                            <td class="pe-4 text-muted small"><?= date('d M Y', strtotime($d['achievement_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">No student achievements logged yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
