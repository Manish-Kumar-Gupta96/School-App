<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Check In Visitor
if (isset($_POST['checkin_visitor'])) {
    $visitor_name   = trim($_POST['visitor_name']);
    $mobile         = trim($_POST['mobile']);
    $purpose        = trim($_POST['purpose']);
    $person_to_meet = trim($_POST['person_to_meet']);

    if (empty($visitor_name) || empty($mobile)) {
        $error = "Visitor Name and Mobile are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO visitors (school_id, visitor_name, mobile, purpose, person_to_meet, checkin_time)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $visitor_name,
            $mobile,
            $purpose,
            $person_to_meet
        ]);
        $visitor_id = $pdo->lastInsertId();

        // Also generate a pass for them automatically
        $qr_data = "VISITOR-" . $visitor_id . "-" . time();
        $valid_until = date('Y-m-d H:i:s', strtotime('+8 hours')); // Valid for 8 hours

        $stmt_pass = $pdo->prepare("
            INSERT INTO visitor_passes (school_id, visitor_id, qr_code, valid_until)
            VALUES (?, ?, ?, ?)
        ");
        $stmt_pass->execute([
            CURRENT_SCHOOL_ID,
            $visitor_id,
            $qr_data,
            $valid_until
        ]);

        $message = "Visitor checked in successfully and pass generated!";
    }
}

// Check Out Visitor
if (isset($_GET['checkout'])) {
    $visitor_id = (int)$_GET['checkout'];
    $stmt_co = $pdo->prepare("UPDATE visitors SET checkout_time = NOW() WHERE id = ? AND school_id = ?");
    if($stmt_co->execute([$visitor_id, CURRENT_SCHOOL_ID])) {
        $_SESSION['success_msg'] = "Visitor checked out successfully.";
        header("Location: register.php");
        exit;
    }
}

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Fetch Active Visitors (Not checked out yet)
$stmt_active = $pdo->prepare("
    SELECT * 
    FROM visitors
    WHERE school_id = ? AND checkout_time IS NULL
    ORDER BY checkin_time DESC
");
$stmt_active->execute([CURRENT_SCHOOL_ID]);
$activeVisitors = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

// Fetch Past Visitors
$stmt_past = $pdo->prepare("
    SELECT * 
    FROM visitors
    WHERE school_id = ? AND checkout_time IS NOT NULL
    ORDER BY checkout_time DESC LIMIT 20
");
$stmt_past->execute([CURRENT_SCHOOL_ID]);
$pastVisitors = $stmt_past->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Visitor Register | VIC ERP";
$page_header = "Gate Pass & Visitor Management";
$active_menu = "visitor";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage security logs, passes, and visitor tracking</h5>
    <div class="d-flex gap-2">
        <a href="register.php" class="btn btn-primary">
            <i class="fa fa-users me-1"></i> Visitor Register
        </a>
        <a href="exit-passes.php" class="btn btn-outline-primary">
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
    <!-- Add Visitor Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Check-in New Visitor</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Visitor Name <span class="text-danger">*</span></label>
                        <input type="text" name="visitor_name" class="form-control" placeholder="e.g. John Doe" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" placeholder="e.g. 9876543210" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Person to Meet</label>
                        <input type="text" name="person_to_meet" class="form-control" placeholder="e.g. Principal, Mr. Smith">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Purpose of Visit</label>
                        <textarea name="purpose" class="form-control" rows="2" placeholder="e.g. Admission inquiry, Vendor meeting"></textarea>
                    </div>

                    <button type="submit" name="checkin_visitor" class="btn btn-success w-100">
                        <i class="fa fa-sign-in-alt me-1"></i> Check-in Visitor
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Visitors List Card -->
    <div class="col-lg-8">
        <!-- Active Visitors -->
        <div class="card shadow border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Active Visitors In Campus</h5>
                <span class="badge bg-success rounded-pill px-3 py-2"><?= count($activeVisitors) ?> Active</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Visitor Info</th>
                                <th>Meeting Details</th>
                                <th>Check-in Time</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activeVisitors) > 0): ?>
                                <?php foreach($activeVisitors as $av): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($av['visitor_name']) ?></div>
                                            <div class="small text-muted"><i class="fa fa-phone me-1"></i> <?= htmlspecialchars($av['mobile']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary"><?= htmlspecialchars($av['person_to_meet'] ?: 'General') ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($av['purpose'] ?: 'Not specified') ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><i class="fa fa-clock me-1 text-primary"></i> <?= date('h:i A', strtotime($av['checkin_time'])) ?></span>
                                            <div class="small text-muted mt-1"><?= date('d M Y', strtotime($av['checkin_time'])) ?></div>
                                        </td>
                                        <td class="text-center">
                                            <a href="?checkout=<?= $av['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Check out this visitor?')">
                                                <i class="fa fa-sign-out-alt me-1"></i> Check-out
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fa fa-check-double fs-3 mb-2 d-block text-success"></i>
                                        No active visitors on campus right now.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Past Visitors -->
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-muted">Recent Check-outs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pastVisitors) > 0): ?>
                                <?php foreach($pastVisitors as $pv): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($pv['visitor_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($pv['person_to_meet'] ?: 'General') ?></div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-clock text-success me-1"></i> <?= date('d M h:i A', strtotime($pv['checkin_time'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-clock text-danger me-1"></i> <?= date('d M h:i A', strtotime($pv['checkout_time'])) ?></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        No past visitor logs found.
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
