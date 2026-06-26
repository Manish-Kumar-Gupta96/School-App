<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Generate Exit Pass
if (isset($_POST['save_exit_pass'])) {
    $student_id = (int)$_POST['student_id'];
    $reason     = trim($_POST['reason']);
    $exit_time  = trim($_POST['exit_time']);

    if (empty($student_id) || empty($reason) || empty($exit_time)) {
        $error = "Student, Reason, and Exit Time are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO student_exit_passes (school_id, student_id, approved_by, reason, exit_time)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $_SESSION['user_id'], // Assuming approved by logged-in admin
            $reason,
            $exit_time
        ]);
        $message = "Exit pass generated successfully!";
    }
}

// Mark Return Time
if (isset($_GET['return_id'])) {
    $pass_id = (int)$_GET['return_id'];
    $stmt_rt = $pdo->prepare("UPDATE student_exit_passes SET return_time = NOW() WHERE id = ? AND school_id = ?");
    if($stmt_rt->execute([$pass_id, CURRENT_SCHOOL_ID])) {
        $_SESSION['success_msg'] = "Student return time logged successfully.";
        header("Location: exit-passes.php");
        exit;
    }
}

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Fetch Students for Dropdown
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name, class FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Passes (Not returned yet)
$stmt_active = $pdo->prepare("
    SELECT p.*, s.first_name, s.last_name, s.admission_no 
    FROM student_exit_passes p
    JOIN students s ON p.student_id = s.id
    WHERE p.school_id = ? AND p.return_time IS NULL
    ORDER BY p.exit_time DESC
");
$stmt_active->execute([CURRENT_SCHOOL_ID]);
$activePasses = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

// Fetch Past Passes
$stmt_past = $pdo->prepare("
    SELECT p.*, s.first_name, s.last_name, s.admission_no 
    FROM student_exit_passes p
    JOIN students s ON p.student_id = s.id
    WHERE p.school_id = ? AND p.return_time IS NOT NULL
    ORDER BY p.return_time DESC LIMIT 20
");
$stmt_past->execute([CURRENT_SCHOOL_ID]);
$pastPasses = $stmt_past->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Student Exit Passes | VIC ERP";
$page_header = "Gate Pass & Visitor Management";
$active_menu = "visitor";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage security logs, passes, and visitor tracking</h5>
    <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-outline-primary">
            <i class="fa fa-users me-1"></i> Visitor Register
        </a>
        <a href="exit-passes.php" class="btn btn-primary">
            <i class="fa fa-person-walking-arrow-right me-1"></i> Exit Passes
        </a>
        <a href="pickup.php" class="btn btn-outline-success">
            <i class="fa fa-car-side me-1"></i> Parent Pickup
        </a>
        <a href="security-dashboard.php" class="btn btn-outline-info">
            <i class="fa fa-shield-halved me-1"></i> Security Dashboard
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
    <!-- Generate Exit Pass Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Issue Exit Pass</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Search Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Expected Exit Time <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="exit_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Reason for Leaving <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Medical emergency, Family function" required></textarea>
                    </div>

                    <button type="submit" name="save_exit_pass" class="btn btn-primary w-100">
                        <i class="fa fa-print me-1"></i> Generate & Approve Pass
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Passes List Card -->
    <div class="col-lg-8">
        <!-- Active Passes -->
        <div class="card shadow border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Students Currently Out</h5>
                <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><?= count($activePasses) ?> Students Out</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student Details</th>
                                <th>Reason</th>
                                <th>Exit Time</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activePasses) > 0): ?>
                                <?php foreach($activePasses as $ap): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($ap['first_name'] . ' ' . $ap['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($ap['admission_no']) ?></span>
                                        </td>
                                        <td>
                                            <div class="small text-muted text-wrap" style="max-width: 200px;">
                                                <?= htmlspecialchars($ap['reason']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-danger"><i class="fa fa-arrow-right-from-bracket me-1"></i> <?= date('h:i A', strtotime($ap['exit_time'])) ?></div>
                                            <div class="small text-muted"><?= date('d M Y', strtotime($ap['exit_time'])) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <a href="?return_id=<?= $ap['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="return confirm('Mark student as returned?')">
                                                <i class="fa fa-check me-1"></i> Mark Returned
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fa fa-building-circle-check fs-3 mb-2 d-block text-success"></i>
                                        No students are currently out on passes.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-muted">Pass History (Returned)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Reason</th>
                                <th>Duration Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pastPasses) > 0): ?>
                                <?php foreach($pastPasses as $pp): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($pp['first_name'] . ' ' . $pp['last_name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-muted text-wrap" style="max-width: 200px;">
                                                <?= htmlspecialchars($pp['reason']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small text-danger mb-1"><i class="fa fa-arrow-up me-1"></i> Out: <?= date('d M, h:i A', strtotime($pp['exit_time'])) ?></div>
                                            <div class="small text-success"><i class="fa fa-arrow-down me-1"></i> In: <?= date('d M, h:i A', strtotime($pp['return_time'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No history found.
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
