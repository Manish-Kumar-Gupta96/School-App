<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';

new BaseController();
$db = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Violation.");
    $otpInput = filter_input(INPUT_POST, 'otp_code', FILTER_SANITIZE_SPECIAL_CHARS);
    
    try {
        // Validate matching OTP code lines which hasn't crossed dynamic timeline boundary locks
        $stmt = $db->prepare("SELECT * FROM access_delegation WHERE delegate_id = :my_id AND otp_code = :otp AND end_date > NOW() AND is_verified = 0 LIMIT 1");
        $stmt->execute([':my_id' => $_SESSION['user_id'], ':otp' => $otpInput]);
        $validToken = $stmt->fetch();

        if ($validToken) {
            // Confirm identity token connection matching active parameters constraints 
            $update = $db->prepare("UPDATE access_delegation SET is_verified = 1 WHERE id = :id");
            $update->execute([':id' => $validToken['id']]);

            // Save owner context and link session switch
            $_SESSION['admin_backed_id'] = $_SESSION['user_id'];
            $_SESSION['user_id'] = $validToken['owner_id']; // Direct login target hijack execution loop
            
            header('Location: /school-app/dashboard.php');
            exit;
        } else { $message = "Invalid Token Entry Code or Access Timeline Expired past boundary limitations."; }
    } catch (PDOException $e) { $message = "Execution error: " . $e->getMessage(); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Claim Delegation Authority</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 450px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white font-weight-bold small text-center">Verify Delegated Shared System Access Token</div>
        <div class="card-body">
            <?php if(!empty($message)): ?><div class="alert alert-warning small py-1"><?php echo $message; ?></div><?php endif; ?>
            <form action="" method="POST">
                <?php Csrf::injectInput(); ?>
                <label class="form-label small fw-bold">Enter 6-Digit OTP Security Code Received</label>
                <input type="text" name="otp_code" maxlength="6" class="form-control text-center mb-3 font-monospace tracking-wide" placeholder="000000" required>
                <button type="submit" name="verify_otp" class="btn btn-sm btn-dark w-100 fw-bold">Confirm Integration Access</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
