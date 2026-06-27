<?php
$root_path = "../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');
require_once($root_path . 'includes/Csrf.php');
require_once($root_path . 'includes/AuditLogger.php');

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

$myId = $_SESSION['user_id'];
$admin_id = $_SESSION['admin_id'] ?? 1;
$admin = null;
$message = '';
$status = true;

// ACTION 1: Password Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $newPass = $_POST['new_password'] ?? '';
    
    if (strlen($newPass) >= 6) {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([':pass' => $hashed, ':id' => $myId]);
        AuditLogger::log('PASSWORD_CHANGED', "User ID #{$myId} updated their login credentials from admin profile.");
        $message = "Password updated successfully!";
        $status = true;
    } else { 
        $message = "Password kam se kam 6 characters ka hona chahiye."; 
        $status = false; 
    }
}

// ACTION 2: Access Delegation Token Generator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delegate_access'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $delegateUsername = filter_input(INPUT_POST, 'delegate_user', FILTER_SANITIZE_SPECIAL_CHARS);
    $days = filter_input(INPUT_POST, 'days_limit', FILTER_VALIDATE_INT);

    if ($days > 10 || $days <= 0) {
        $message = "Error: Aap 10 days se zyada ya 0 days ke liye access nahi de sakte.";
        $status = false;
    } else {
        // Fetch target delegate account details
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = :uname LIMIT 1");
        $chk->execute([':uname' => $delegateUsername]);
        $targetUser = $chk->fetch(PDO::FETCH_ASSOC);

        if ($targetUser) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $endDate = date('Y-m-d H:i:s', strtotime("+$days days"));
            
            $stmt = $pdo->prepare("INSERT INTO access_delegation (owner_id, delegate_id, start_date, end_date, otp_code, is_verified) 
                                  VALUES (:owner, :delegate, NOW(), :end, :otp, 0)");
            $stmt->execute([
                ':owner' => $myId,
                ':delegate' => $targetUser['id'],
                ':end' => $endDate,
                ':otp' => $otp
            ]);
            $message = "OTP Generated! Share this OTP code with target user: <strong>$otp</strong> (Valid for $days days)";
            $status = true;
        } else { 
            $message = "Target Username system me nahi mila."; 
            $status = false; 
        }
    }
}

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Admin Profile | VIC ERP";
$page_header = "My Profile";
$active_menu = "profile";

require_once('includes/header.php');
require_once('includes/topbar.php');
?>

<div class="main-dashboard p-4 text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">👤 Account Profile</h2>
            <p class="text-muted mb-0">View your administrator profile details and manage security settings.</p>
        </div>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= $status ? 'success' : 'danger' ?> alert-dismissible fade show mb-4" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm text-center p-4 mb-4" style="border-radius: 15px;">
                <img src="<?= $root_path ?>assets/images/default-user.png" alt="Admin Photo" class="rounded-circle border border-primary border-4 mx-auto mb-3" style="width: 140px; height: 140px; object-fit: cover;">
                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($admin['name'] ?? 'Super Admin') ?></h4>
                <p class="badge bg-primary px-3 py-2 rounded-pill mb-3">Administrator</p>
                <div class="text-muted small"><i class="fa fa-envelope me-2"></i><?= htmlspecialchars($admin['email'] ?? 'admin@vicschool.edu.in') ?></div>
            </div>
            
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h6 class="fw-bold mb-3 text-secondary"><i class="fa fa-key text-primary me-2"></i> Modify Account Password</h6>
                <form action="" method="POST">
                    <?php Csrf::injectInput(); ?>
                    <input type="password" name="new_password" class="form-control mb-3" placeholder="Enter new strong password" required>
                    <button type="submit" name="change_password" class="btn btn-dark w-100 fw-bold">Update Key Locked</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-circle-info text-primary me-2"></i>Profile Information</h5>
                <hr class="text-muted mt-0 mb-4">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($admin['name'] ?? 'Super Admin') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? 'admin@vicschool.edu.in') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Role Name</label>
                        <input type="text" class="form-control" value="Administrator" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Joined Date</label>
                        <input type="text" class="form-control" value="<?= isset($admin['created_at']) ? date('d M Y, h:i A', strtotime($admin['created_at'])) : date('d M Y') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-1"><i class="fa fa-shield-alt text-danger me-2"></i>Timed Access Delegation Engine</h5>
                <p class="text-muted small mb-4">Aap apna login access kisi authorized staff ko maximum 10 din ke liye de sakte hain.</p>
                <form action="" method="POST">
                    <?php Csrf::injectInput(); ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Target Username</label>
                            <input type="text" name="delegate_user" class="form-control" placeholder="Target username jise access dena hai" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Duration (Days)</label>
                            <input type="number" name="days_limit" class="form-control" min="1" max="10" placeholder="Max 10 days" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" name="delegate_access" class="btn btn-danger w-100 fw-bold">Authorize and Emit OTP Code</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
