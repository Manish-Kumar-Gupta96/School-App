<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Career Session
if (isset($_POST['save_session'])) {
    $title        = trim($_POST['title']);
    $session_date = trim($_POST['session_date']);
    $speaker_name = trim($_POST['speaker_name']);

    if (empty($title) || empty($session_date)) {
        $error = "Title and Session Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO career_sessions (school_id, title, session_date, speaker_name)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $title,
            $session_date,
            $speaker_name
        ]);
        $message = "Career session scheduled successfully!";
    }
}

// Fetch Career Sessions
$stmt_sessions = $pdo->prepare("
    SELECT * 
    FROM career_sessions
    WHERE school_id = ?
    ORDER BY session_date DESC
");
$stmt_sessions->execute([CURRENT_SCHOOL_ID]);
$sessionsList = $stmt_sessions->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Career Sessions | VIC ERP";
$page_header = "Placement & Career Guidance";
$active_menu = "placement";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage placement drives and career records</h5>
    <div class="d-flex gap-2">
        <a href="companies.php" class="btn btn-outline-primary">
            <i class="fa fa-building me-1"></i> Companies
        </a>
        <a href="sessions.php" class="btn btn-primary">
            <i class="fa fa-person-chalkboard me-1"></i> Career Sessions
        </a>
        <a href="placements.php" class="btn btn-outline-success">
            <i class="fa fa-briefcase me-1"></i> Placements
        </a>
        <a href="exams.php" class="btn btn-outline-info">
            <i class="fa fa-file-signature me-1"></i> Entrance Exams
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add Session Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Schedule Session</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. How to crack FAANG" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Session Date <span class="text-danger">*</span></label>
                        <input type="date" name="session_date" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Speaker / Guide Name</label>
                        <input type="text" name="speaker_name" class="form-control" placeholder="e.g. John Doe">
                    </div>

                    <button type="submit" name="save_session" class="btn btn-success w-100">
                        <i class="fa fa-calendar-plus me-1"></i> Schedule Session
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sessions List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Upcoming & Past Sessions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Session Title</th>
                                <th>Speaker Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($sessionsList) > 0): ?>
                                <?php foreach($sessionsList as $sess): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-primary"><?= date('d M Y', strtotime($sess['session_date'])) ?></div>
                                            <span class="small text-muted"><?= date('l', strtotime($sess['session_date'])) ?></span>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?= htmlspecialchars($sess['title']) ?>
                                        </td>
                                        <td>
                                            <?php if ($sess['speaker_name']): ?>
                                                <i class="fa fa-user-tie text-secondary me-1"></i> <?= htmlspecialchars($sess['speaker_name']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fa fa-person-chalkboard fs-2 mb-2 d-block"></i>
                                        No sessions scheduled yet.
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

<?php require_once('../includes/footer.php'); ?>
