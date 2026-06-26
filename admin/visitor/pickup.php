<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Pickup Authorization
if (isset($_POST['save_auth'])) {
    $student_id        = (int)$_POST['student_id'];
    $authorized_person = trim($_POST['authorized_person']);
    $mobile            = trim($_POST['mobile']);
    $relation_name     = trim($_POST['relation_name']);

    if (empty($student_id) || empty($authorized_person) || empty($mobile)) {
        $error = "Student, Authorized Person, and Mobile are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO pickup_authorizations (school_id, student_id, authorized_person, mobile, relation_name)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $authorized_person,
            $mobile,
            $relation_name
        ]);
        $message = "Pickup authorization added successfully!";
    }
}

// Remove Authorization
if (isset($_GET['remove_id'])) {
    $auth_id = (int)$_GET['remove_id'];
    $stmt_rm = $pdo->prepare("DELETE FROM pickup_authorizations WHERE id = ? AND school_id = ?");
    if($stmt_rm->execute([$auth_id, CURRENT_SCHOOL_ID])) {
        $_SESSION['success_msg'] = "Authorization removed successfully.";
        header("Location: pickup.php");
        exit;
    }
}

if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Fetch Students for Dropdown
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Authorizations
$stmt_auths = $pdo->prepare("
    SELECT a.*, s.first_name, s.last_name, s.admission_no 
    FROM pickup_authorizations a
    JOIN students s ON a.student_id = s.id
    WHERE a.school_id = ?
    ORDER BY a.id DESC
");
$stmt_auths->execute([CURRENT_SCHOOL_ID]);
$authsList = $stmt_auths->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Parent Pickup Authorizations | VIC ERP";
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
        <a href="exit-passes.php" class="btn btn-outline-primary">
            <i class="fa fa-person-walking-arrow-right me-1"></i> Exit Passes
        </a>
        <a href="pickup.php" class="btn btn-success">
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
    <!-- Add Auth Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add Pickup Authorization</h5>
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
                        <label class="form-label fw-semibold">Authorized Person Name <span class="text-danger">*</span></label>
                        <input type="text" name="authorized_person" class="form-control" placeholder="e.g. Michael Smith" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" name="mobile" class="form-control" placeholder="e.g. 9876543210" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Relation to Student</label>
                        <input type="text" name="relation_name" class="form-control" placeholder="e.g. Uncle, Grandfather, Driver">
                    </div>

                    <button type="submit" name="save_auth" class="btn btn-success w-100">
                        <i class="fa fa-user-plus me-1"></i> Add Authorization
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Auth List Card -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Active Pickup Authorizations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Authorized Person</th>
                                <th>Contact & Relation</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($authsList) > 0): ?>
                                <?php foreach($authsList as $al): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($al['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($al['authorized_person']) ?>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-phone text-muted me-1"></i> <?= htmlspecialchars($al['mobile']) ?></div>
                                            <div class="small"><i class="fa fa-users text-muted me-1"></i> <?= htmlspecialchars($al['relation_name'] ?: 'Not Specified') ?></div>
                                        </td>
                                        <td class="text-center">
                                            <a href="?remove_id=<?= $al['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Revoke this authorization?')">
                                                <i class="fa fa-trash-alt me-1"></i> Revoke
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-car-side fs-2 mb-2 d-block"></i>
                                        No active pickup authorizations found.
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
