<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Visitor Log
if (isset($_POST['save_visitor'])) {
    $visitor_name  = trim($_POST['visitor_name']);
    $relation_name = trim($_POST['relation_name']);
    $student_id    = (int)$_POST['student_id'];
    $mobile        = trim($_POST['mobile']);
    $visit_time    = $_POST['visit_time'];

    if (empty($visitor_name) || empty($student_id) || empty($visit_time)) {
        $error = "Visitor Name, Student, and Visit Time are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostel_visitors (school_id, student_id, visitor_name, relation_name, mobile, visit_time)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $visitor_name,
            $relation_name,
            $mobile,
            $visit_time
        ]);
        $message = "Visitor check-in logged successfully!";
    }
}

// Fetch Boarders for dropdown
$stmt_stud = $pdo->prepare("
    SELECT DISTINCT s.id, s.first_name, s.last_name, s.admission_no 
    FROM students s
    JOIN hostel_beds hb ON s.id = hb.student_id
    WHERE s.school_id = ?
    ORDER BY s.first_name ASC
");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Visitor Logs
$stmt_vis = $pdo->prepare("
    SELECT hv.*, s.first_name, s.last_name, s.admission_no 
    FROM hostel_visitors hv
    JOIN students s ON hv.student_id = s.id
    WHERE hv.school_id = ?
    ORDER BY hv.visit_time DESC
");
$stmt_vis->execute([CURRENT_SCHOOL_ID]);
$visitors = $stmt_vis->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Hostel Visitors | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and track hostel visitors check-in logs</h5>
    <div class="d-flex gap-2">
        <a href="hostels.php" class="btn btn-outline-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="beds.php" class="btn btn-outline-primary">
            <i class="fa fa-bed me-1"></i> Beds
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="visitors.php" class="btn btn-primary">
            <i class="fa fa-users me-1"></i> Visitors
        </a>
        <a href="mess-menu.php" class="btn btn-outline-success">
            <i class="fa fa-utensils me-1"></i> Mess Menu
        </a>
        <a href="fees.php" class="btn btn-outline-warning">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
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
    <!-- Log Entry Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Log Check-In</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Visitor Name <span class="text-danger">*</span></label>
                        <input type="text" name="visitor_name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Relation with Boarder</label>
                        <input type="text" name="relation_name" class="form-control" placeholder="e.g. Father, Uncle">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student to Meet <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Boarder...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contact Mobile</label>
                        <input type="text" name="mobile" class="form-control" placeholder="e.g. 9876543210">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Visit Date & Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="visit_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <button type="submit" name="save_visitor" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Entry
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Visitors Log Sheet -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Visitors Log Sheet</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Student Met</th>
                                <th>Mobile</th>
                                <th>Check-In Time</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($visitors) > 0): ?>
                                <?php foreach($visitors as $v): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($v['visitor_name']) ?></div>
                                            <span class="text-muted small">Relation: <?= htmlspecialchars($v['relation_name'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary mb-0"><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($v['admission_no']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($v['mobile'] ?: '-') ?></td>
                                        <td>
                                            <span class="small text-muted"><i class="fa fa-clock text-success me-1"></i> <?= date('d M Y h:i A', strtotime($v['visit_time'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-history fs-2 mb-2 d-block"></i>
                                        No visitor logs registered.
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

<?php
require_once('../includes/footer.php');
?>
