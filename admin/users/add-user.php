<?php
require_once __DIR__ . '/../../includes/init.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin', 'teacher', 'accountant']);

$db = getDBConnection();
$message = '';
$status = true;

$currentRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
$currentUserId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Breach: CSRF Verification Failed.");
    }

    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = $_POST['password'] ?? '';
    $targetRole = $_POST['role'] ?? '';

    try {
        if ($targetRole !== 'student' && !in_array($currentRole, ['superadmin', 'admin'])) {
            throw new Exception("Access Denied: Aapke paas staff members (Teacher/Staff) ko add karne ka adhikar nahi hai. Aap sirf Students add kar sakte hain.");
        }

        if ($targetRole === 'superadmin' && $currentRole !== 'superadmin') {
            throw new Exception("Security Alert: Super Admin account sirf ek existing Super Admin hi create kar sakta hai.");
        }

        if (strlen($password) < 6) {
            throw new Exception("Password kam se kam 6 characters ka hona anivary hai.");
        }

        $chk = $db->prepare("SELECT id FROM users WHERE name = :uname OR email = :email LIMIT 1");
        $chk->execute([':uname' => $username, ':email' => $email]);
        if ($chk->fetch()) {
            throw new Exception("Error: Yeh Username ya Email system me pehle se registered hai.");
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $roleMap = [
            'superadmin' => 6,
            'admin' => 7,
            'teacher' => 8,
            'student' => 9,
            'accountant' => 5
        ];
        
        $roleId = $roleMap[$targetRole] ?? null;
        if (!$roleId) {
            $rStmt = $db->prepare("SELECT id FROM roles WHERE role_name = :role LIMIT 1");
            $rStmt->execute([':role' => $targetRole]);
            $roleId = $rStmt->fetchColumn();
            if (!$roleId) {
                throw new Exception("Error: Invalid role selected.");
            }
        }

        $stmt = $db->prepare("INSERT INTO users (name, email, password, role_id) VALUES (:uname, :email, :pass, :role)");
        $stmt->execute([
            ':uname' => $username,
            ':email' => $email,
            ':pass'  => $hashedPassword,
            ':role'  => $roleId
        ]);

        require_once __DIR__ . '/../../includes/EmailProvider.php';
        EmailProvider::sendWelcomeCredentials($email, $username, $password, $targetRole);

        AuditLogger::log('USER_ONBOARDED', "New user '{$username}' registered and credential dispatch packet fired to email.");
        $message = "Success! Account create ho gaya hai aur Login ID & Password student/staff ke email par automatically bhej diya gaya hai!";
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $status = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School ERP - Secure User Onboarding Control</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-user-plus"></i> Secure Institutional User Onboarding</h6>
            <span class="badge bg-warning text-dark">Logged as: <?php echo strtoupper($currentRole); ?></span>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small py-2"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="off">
                <?php Csrf::injectInput(); ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Account Username / Login ID</label>
                    <input type="text" name="username" class="form-control form-control-sm" required placeholder="e.g., rahul_teacher or amit_student">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Official Email Address</label>
                    <input type="email" name="email" class="form-control form-control-sm" required placeholder="name@school.com">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Temporary Account Password</label>
                    <input type="password" name="password" class="form-control form-control-sm" minlength="6" required placeholder="Min 6 characters">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Assign System Role Panel</label>
                    <select name="role" class="form-select form-select-sm" required>
                        <option value="">-- Choose Access Scope --</option>
                        <option value="student">Student (Classroom & Progress View)</option>
                        
                        <?php if (in_array($currentRole, ['superadmin', 'admin'])): ?>
                            <option value="teacher">Teacher (Academic Console)</option>
                            <option value="accountant">Accountant (Finance & Fee Desk)</option>
                            <option value="admin">Admin (Institutional Manager)</option>
                        <?php endif; ?>
                        
                        <?php if ($currentRole === 'superadmin'): ?>
                            <option value="superadmin">Super Admin (Absolute Root Access)</option>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted d-block mt-1" style="font-size: 11px; color: #dc3545 !important;">
                        * Note: Teachers aur Accountants ko yahan sirf 'Student' add karne ka option visible hoga.
                    </small>
                </div>

                <button type="submit" name="create_user" class="btn btn-dark btn-sm w-100 fw-bold">Authorize & Onboard Member</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
