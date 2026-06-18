<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Log Sports Achievements | Admin Control";
$active_menu = "achievements";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $name     = trim($_POST['name']);
    $event    = trim($_POST['event']);
    $position = trim($_POST['position']);
    $date     = $_POST['date'];

    if(empty($name) || empty($event) || empty($position) || empty($date)){
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO sports_achievements (student_name, event_name, position, event_date)
            VALUES (?,?,?,?)
        ");
        $stmt->execute([$name, $event, $position, $date]);
        $message = "Sports achievement logged successfully!";
    }
}

$data = $pdo->query("SELECT * FROM sports_achievements ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🏅 Sports Achievements Log</h2>
            <p class="text-muted mb-0">Record student performances and medal ranks in sports tournaments.</p>
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
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-award me-2 text-primary"></i>Add Sports Record</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Student Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Amit Kumar" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Event / Sport Name</label>
                        <input type="text" name="event" class="form-control" placeholder="e.g. Under-16 100m Athletics" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Position Won</label>
                        <select name="position" class="form-select" required>
                            <option>1st (Gold)</option>
                            <option>2nd (Silver)</option>
                            <option>3rd (Bronze)</option>
                            <option>Participant</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Event Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="col-md-12 mt-4 text-end">
                        <button type="submit" name="save" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa fa-save me-1"></i> Log Sports Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-secondary"></i>Sports Records Log</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Student Name</th>
                                    <th>Event</th>
                                    <th>Rank</th>
                                    <th class="pe-4">Date</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if(count($data) > 0): ?>
                                    <?php foreach($data as $d): ?>
                                        <?php
                                        $badge = 'secondary';
                                        if (strpos($d['position'], '1st') !== false) $badge = 'warning text-dark';
                                        elseif (strpos($d['position'], '2nd') !== false) $badge = 'secondary';
                                        elseif (strpos($d['position'], '3rd') !== false) $badge = 'danger-subtle text-danger';
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($d['student_name']) ?></td>
                                            <td><?= htmlspecialchars($d['event_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $badge ?> px-3 py-1 fw-bold">
                                                    <?= htmlspecialchars($d['position']) ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 text-muted small"><?= date('d M Y', strtotime($d['event_date'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No sports records logged yet.</td>
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
