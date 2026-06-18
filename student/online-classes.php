<?php
$root_path = "../";
require_once($root_path . 'config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$student_class = 'Class 10-A'; // Default fallback

// Try to fetch student target class
if (isset($_SESSION['user_id'])) {
    try {
        $stmt_s = $pdo->prepare("SELECT class FROM students WHERE email = (SELECT email FROM users WHERE id = ?)");
        $stmt_s->execute([$_SESSION['user_id']]);
        $res = $stmt_s->fetch(PDO::FETCH_ASSOC);
        if ($res && !empty($res['class'])) {
            $student_class = $res['class'];
        }
    } catch (PDOException $e) {
        // Ignore
    }
}

// Fetch classes matching student class
$classes = [];
try {
    $stmt_c = $pdo->prepare("
        SELECT oc.*, t.name AS teacher_name 
        FROM online_classes oc
        LEFT JOIN teachers t ON oc.teacher_id = t.id
        WHERE oc.class_name = ? AND oc.status != 'COMPLETED'
        ORDER BY oc.start_time ASC
    ");
    $stmt_c->execute([$student_class]);
    $classes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error loading online classes: ' . $e->getMessage();
}

// Fetch Recordings
$recordings = [];
try {
    $stmt_r = $pdo->prepare("
        SELECT cr.*, oc.subject, oc.title AS class_title
        FROM class_recordings cr
        LEFT JOIN online_classes oc ON cr.class_id = oc.id
        WHERE oc.class_name = ?
        ORDER BY cr.id DESC
    ");
    $stmt_r->execute([$student_class]);
    $recordings = $stmt_r->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore
}

$page_title = "Online Classes | Student Portal";
$page_header = "Virtual Classrooms & Lectures";
$active_menu = "online-classes";

require_once('includes/header.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Virtual Online Classes</h2>
        <span class="badge bg-primary fs-6 py-2 px-3">Target: <?= htmlspecialchars($student_class) ?></span>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger text-start py-2 small" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Live Classes List -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-circle-play text-danger me-2 animate-pulse"></i>Live & Upcoming Classes</h5>
                <hr class="text-muted mt-0 mb-4">

                <?php if (count($classes) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($classes as $c): ?>
                            <div class="p-3 border rounded text-start" style="background-color: #fafbfd; border-radius: 10px;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($c['title']) ?></h6>
                                        <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($c['subject'] ?: 'Subject') ?></span>
                                    </div>
                                    <div>
                                        <?php if ($c['status'] === 'LIVE'): ?>
                                            <span class="badge bg-danger animate-pulse">LIVE NOW</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">UPCOMING</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="text-muted small mb-2"><i class="fa fa-chalkboard-user me-2"></i>Instructor: <?= htmlspecialchars($c['teacher_name'] ?: 'Faculty') ?></p>
                                <p class="text-muted small mb-3"><i class="fa fa-calendar-alt me-2"></i>Time: <?= date('d M Y, h:i A', strtotime($c['start_time'])) ?> to <?= date('h:i A', strtotime($c['end_time'])) ?></p>

                                <?php if (!empty($c['meeting_link'])): ?>
                                    <a href="<?= htmlspecialchars($c['meeting_link']) ?>" target="_blank" class="btn btn-primary w-100 py-2 fw-semibold" style="border-radius: 8px;">
                                        <i class="fa fa-arrow-up-right-from-square me-2"></i> Join Live Class
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-video-slash fs-2 mb-2 d-block"></i>
                        No live classes scheduled for your class target.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recorded Playbacks -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-clapperboard text-success me-2"></i>Recorded Lecture Archives</h5>
                <hr class="text-muted mt-0 mb-4">

                <?php if (count($recordings) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recordings as $rec): ?>
                            <div class="p-3 border rounded text-start" style="background-color: #fafbfd; border-radius: 10px;">
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($rec['title']) ?></h6>
                                <span class="badge bg-success-subtle text-success small mb-2"><?= htmlspecialchars($rec['subject'] ?: 'Subject') ?></span>
                                <p class="text-muted small mb-3">Topic: <?= htmlspecialchars($rec['class_title'] ?: '-') ?></p>
                                <a href="<?= htmlspecialchars($rec['recording_link']) ?>" target="_blank" class="btn btn-outline-success w-100 py-2" style="border-radius: 8px;">
                                    <i class="fa fa-play me-2"></i> Watch Lecture Recording
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                        No recorded playbacks uploaded yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
