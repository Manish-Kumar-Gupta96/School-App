<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Academic Calendar Setup | Admin Control";
$active_menu = "events";
require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $title = trim($_POST['title']);
    $date  = $_POST['date'];
    $type  = $_POST['type'];

    if(empty($title) || empty($date)){
        $error = "Title and Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO academic_calendar (title, event_date, type)
            VALUES (?,?,?)
        ");
        $stmt->execute([$title, $date, $type]);
        $message = "Calendar event saved successfully!";
    }
}

$data = $pdo->query("SELECT * FROM academic_calendar ORDER BY event_date ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📅 Academic Calendar Setup</h2>
            <p class="text-muted mb-0">Record and review public school holidays, exams schedules, and special events.</p>
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
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-plus me-2 text-primary"></i>Add Calendar Event</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Event Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Summer Vacation, Term-1 Exams" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Event Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Event Type</label>
                        <select name="type" class="form-select" required>
                            <option>HOLIDAY</option>
                            <option>EXAM</option>
                            <option>EVENT</option>
                        </select>
                    </div>

                    <div class="col-md-12 mt-4 text-end">
                        <button type="submit" name="save" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa fa-save me-1"></i> Save Event
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-secondary"></i>Calendar Events Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Event Title</th>
                                    <th>Date</th>
                                    <th class="pe-4 text-center">Type</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if(count($data) > 0): ?>
                                    <?php foreach($data as $c): ?>
                                        <?php
                                        $badge = 'secondary';
                                        if ($c['type'] == 'HOLIDAY') $badge = 'danger';
                                        elseif ($c['type'] == 'EXAM') $badge = 'info';
                                        elseif ($c['type'] == 'EVENT') $badge = 'success';
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($c['title']) ?></td>
                                            <td class="text-muted small"><?= date('d M Y (l)', strtotime($c['event_date'])) ?></td>
                                            <td class="pe-4 text-center">
                                                <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-1 fw-bold">
                                                    <?= htmlspecialchars($c['type']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">No calendar events logged yet.</td>
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
require_once('includes/footer.php');
?>
