<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ADD VISITOR
========================== */
if(isset($_POST['save_visitor'])){
    $visitor_name = trim($_POST['visitor_name']);
    $relation     = trim($_POST['relation']);
    $student_id   = (int)$_POST['student_id'];
    $mobile       = trim($_POST['mobile_no']);
    $purpose      = trim($_POST['purpose']);

    if(empty($visitor_name) || empty($student_id)){
        $error = "Visitor Name and Student to visit are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostel_visitors(visitor_name, relation_with_student, student_id, mobile_no, purpose, entry_time)
            VALUES(?,?,?,?,?, NOW())
        ");
        $stmt->execute([
            $visitor_name,
            $relation,
            $student_id,
            $mobile,
            $purpose
        ]);
        $message = "Visitor Entry Recorded Successfully!";
    }
}

/* ==========================
MARK EXIT
========================== */
if(isset($_GET['exit'])){
    $id = (int)$_GET['exit'];
    
    $stmt = $pdo->prepare("
        UPDATE hostel_visitors
        SET exit_time = NOW()
        WHERE id = ? AND exit_time IS NULL
    ");
    $stmt->execute([$id]);

    header("Location: visitors.php");
    exit;
}

// Fetch all students for dropdown
$students = $pdo->query("SELECT id, admission_no, first_name, last_name FROM students ORDER BY first_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch visitors
$visitors = $pdo->query("
    SELECT hv.*, s.admission_no, s.first_name, s.last_name
    FROM hostel_visitors hv
    LEFT JOIN students s ON hv.student_id=s.id
    ORDER BY hv.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Hostel Visitors Register | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and track hostel visitors check-in logs</h5>
    <div class="d-flex gap-2">
        <a href="room-types.php" class="btn btn-outline-primary">
            <i class="fa fa-sliders-h me-1"></i> Room Types
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="allocate-room.php" class="btn btn-outline-primary">
            <i class="fa fa-user-tag me-1"></i> Allocations
        </a>
        <a href="hostel-fees.php" class="btn btn-outline-warning">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="visitors.php" class="btn btn-primary">
            <i class="fa fa-users me-1"></i> Visitors
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
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

<div class="row">
    <!-- ADD ENTRY visitor -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Visitor Check-In Entry</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Visitor Name <span class="text-danger">*</span></label>
                        <input type="text" name="visitor_name" class="form-control" placeholder="e.g. Ramesh Singh" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Relation with Student</label>
                        <input type="text" name="relation" class="form-control" placeholder="e.g. Father, Mother, Guardian">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student to Meet <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Choose Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number</label>
                        <input type="text" name="mobile_no" class="form-control" placeholder="e.g. 9876543210">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Purpose of Visit</label>
                        <input type="text" name="purpose" class="form-control" placeholder="e.g. Meeting, Fees payment">
                    </div>

                    <button type="submit" name="save_visitor" class="btn btn-success w-100">
                        <i class="fa fa-sign-in-alt me-1"></i> Log Check-In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST VISITORS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Visitor Log Sheet</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Student Met</th>
                                <th>Mobile</th>
                                <th>Check-In</th>
                                <th>Check-Out</th>
                                <th class="text-center" width="140">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($visitors) > 0): ?>
                                <?php foreach($visitors as $v): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($v['visitor_name']) ?></div>
                                            <span class="text-muted small">Relation: <?= htmlspecialchars($v['relation_with_student'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary mb-0"><?= htmlspecialchars($v['first_name'] . ' ' . $v['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($v['admission_no']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($v['mobile_no'] ?: '-') ?></td>
                                        <td><span class="small text-muted"><i class="fa fa-clock text-success me-1"></i> <?= date('d M Y h:i A', strtotime($v['entry_time'])) ?></span></td>
                                        <td>
                                            <?php if($v['exit_time']): ?>
                                                <span class="small text-muted"><i class="fa fa-clock text-danger me-1"></i> <?= date('d M Y h:i A', strtotime($v['exit_time'])) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Still Inside</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if(empty($v['exit_time'])): ?>
                                                <a href="?exit=<?= $v['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirm visitor check-out exit time?')">
                                                    <i class="fa fa-sign-out-alt me-1"></i> Log Exit
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Exited</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
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
