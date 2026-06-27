<?php
require_once __DIR__ . '/includes/BaseController.php';
require_once __DIR__ . '/includes/Csrf.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/AuditLogger.php';

new BaseController();
$db = getDBConnection();
$message = '';
$status = true;
$myId = $_SESSION['user_id'];

// ACTION 1: Password Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $newPass = $_POST['new_password'] ?? '';
    
    if (strlen($newPass) >= 6) {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
        $stmt->execute([':pass' => $hashed, ':id' => $myId]);
        AuditLogger::log('PASSWORD_CHANGED', "User ID #{$myId} updated their login credentials.");
        $message = "Password updated successfully!";
    } else { $message = "Password kam se kam 6 characters ka hona chahiye."; $status = false; }
}

// ACTION 2: Access Delegation Token Token Generator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delegate_access'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Failure.");
    $delegateUsername = filter_input(INPUT_POST, 'delegate_user', FILTER_SANITIZE_SPECIAL_CHARS);
    $days = filter_input(INPUT_POST, 'days_limit', FILTER_VALIDATE_INT);

    if ($days > 10 || $days <= 0) {
        $message = "Error: Aap 10 days se zyada ya 0 days ke liye access nahi de sakte.";
        $status = false;
    } else {
        // Fetch target delegate account details
        $chk = $db->prepare("SELECT id FROM users WHERE username = :uname LIMIT 1");
        $chk->execute([':uname' => $delegateUsername]);
        $targetUser = $chk->fetch();

        if ($targetUser) {
            $otp = sprintf("%06d", mt_rand(1, 999999));
            $endDate = date('Y-m-d H:i:s', strtotime("+$days days"));
            
            $stmt = $db->prepare("INSERT INTO access_delegation (owner_id, delegate_id, start_date, end_date, otp_code, is_verified) 
                                  VALUES (:owner, :delegate, NOW(), :end, :otp, 0)");
            $stmt->execute([
                ':owner' => $myId,
                ':delegate' => $targetUser['id'],
                ':end' => $endDate,
                ':otp' => $otp
            ]);
            $message = "OTP Generated! Share this OTP code with target user: **$otp** (Valid for $days days)";
        } else { $message = "Target Username system me nahi mila."; $status = false; }
    }
}

// User Profile Data Details Row Pull
$myProfile = $db->prepare("SELECT username, email, role FROM users WHERE id = :id");
$myProfile->execute([':id' => $myId]);
$profile = $myProfile->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>My Profile Security Desk</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 900px;">
    <div class="card p-4 border-0 shadow-sm mb-4">
        <h5 class="fw-bold text-dark border-bottom pb-2">My Profile & Connection Matrix</h5>
        <?php if (!empty($message)): ?><div class="alert alert-info small py-2"><?php echo $message; ?></div><?php endif; ?>
        
        <div class="row g-3 small">
            <div class="col-md-4"><strong>Login ID/Username:</strong> <code class="fs-6"><?php echo $profile['username']; ?></code></div>
            <div class="col-md-4"><strong>Registered Email:</strong> <?php echo $profile['email']; ?></div>
            <div class="col-md-4"><strong>Current Scope Role:</strong> <span class="badge bg-primary"><?php echo strtoupper($profile['role']); ?></span></div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card p-3 border-0 shadow-sm">
                <h6 class="fw-bold mb-3 text-secondary">Modify Account Password</h6>
                <form action="" method="POST">
                    <?php Csrf::injectInput(); ?>
                    <input type="password" name="new_password" class="form-control form-control-sm mb-3" placeholder="Enter new strong password" required>
                    <button type="submit" name="change_password" class="btn btn-sm btn-dark w-100 fw-bold">Update Key Locked</button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3 border-0 shadow-sm">
                <h6 class="fw-bold mb-1 text-secondary">Timed Access Delegation Engine</h6>
                <small class="text-muted d-block mb-3">Aap apna login access kisi authorized staff ko maximum 10 din ke liye de sakte hain.</small>
                <form action="" method="POST">
                    <?php Csrf::injectInput(); ?>
                    <input type="text" name="delegate_user" class="form-control form-control-sm mb-2" placeholder="Target Username jise access dena hai" required>
                    <input type="number" name="days_limit" class="form-control form-control-sm mb-3" min="1" max="10" placeholder="Duration Days Limit (Max 10)" required>
                    <button type="submit" name="delegate_access" class="btn btn-sm btn-danger w-100 fw-bold">Authorize and Emit OTP Code</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
