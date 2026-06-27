<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
// Secure Lock: Sirf Admin aur Super Admin hi is portal ko open kar sakte hain
BaseController::enforceRole(['admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;
$currentRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_handover'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Check Failed: Invalid CSRF Token.");
    }

    $oldStaffId = filter_input(INPUT_POST, 'old_staff_id', FILTER_VALIDATE_INT);
    $newStaffId = filter_input(INPUT_POST, 'new_staff_id', FILTER_VALIDATE_INT);

    if ($oldStaffId === $newStaffId) {
        $message = "Error: Purane aur naye member ka account same nahi ho sakta.";
        $status = false;
    } else {
        try {
            $db->beginTransaction();

            // 1. SECURITY CHECK: Purane staff ka role check karein
            $stmt = $db->prepare("SELECT r.role_name as role, u.name as username FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
            $stmt->execute([':id' => $oldStaffId]);
            $oldStaffData = $stmt->fetch();

            if (!$oldStaffData) {
                throw new Exception("Purana staff member system me nahi mila.");
            }

            // RULE: Agar purana member 'admin' hai, toh sirf 'superadmin' hi use change kar sakta hai
            if ($oldStaffData['role'] === 'admin' && $currentRole !== 'superadmin') {
                throw new Exception("Suraqsha Chetawani: Admin account ko badalne ka access sirf Super Admin ke paas hai.");
            }

            // 2. TRANSFER REVENUE/ACADEMIC MAPPINGS: Saari classes aur subjects naye teacher ko transfer karein
            $updateMapping = $db->prepare("UPDATE unified_academic_mapping SET teacher_id = :new_id WHERE teacher_id = :old_id");
            $updateMapping->execute([':new_id' => $newStaffId, ':old_id' => $oldStaffId]);
            $mappedRowsTransferred = $updateMapping->rowCount();

            // 3. DEACTIVATE OLD ACCOUNT: Chhodne wale member ka status deactivate karein taaki wo login na kar sake
            // Note: Agar aapke users table me status column nahi hai, toh hum password blank/scramble kar dete hain taaki login block ho jaye
            $deactivateUser = $db->prepare("UPDATE users SET password = 'JOB_LEFT_ACCOUNT_LOCKED_BY_ADMIN' WHERE id = :old_id");
            $deactivateUser->execute([':old_id' => $oldStaffId]);

            // Log details in audit registry
            AuditLogger::log('ACCOUNT_HANDOVER_EXECUTED', "Handover done from {$oldStaffData['username']} (ID: #{$oldStaffId}) to New Staff ID #{$newStaffId}. Mappings moved: {$mappedRowsTransferred}");

            $db->commit();
            $message = "Success! Account handover complete ho gaya hai. Purane member ka access block kar diya gaya hai aur saari classes naye member ko transfer ho gayi hain.";
        } catch (Exception $e) {
            $db->rollBack();
            $message = "Handover Failed: " . $e->getMessage();
            $status = false;
        }
    }
}

// Fetch list of active users to show in dropdowns
// Admin dropdowns me dikhane ke liye roles pull karein
$allStaff = $db->query("SELECT u.id, u.name as username, r.role_name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name IN ('admin', 'teacher', 'accountant') ORDER BY r.role_name ASC, u.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Offboarding & Account Handover Console</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 650px;">
    <div class="card shadow border-0">
        <div class="card-header bg-dark text-white font-weight-bold d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-exchange-alt"></i> Staff Offboarding & Data Handover Engine</h6>
            <span class="badge bg-danger"><?php echo strtoupper($currentRole); ?> Mode</span>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small py-2"><?php echo $message; ?></div>
            <?php endif; ?>

            <p class="text-muted small">Jab koi employee school chhodta hai, toh aap is utility ke throw uski saari responsibilities aur dynamic access portal naye staff ko safe transfer kar sakte hain.</p>
            
            <form action="" method="POST" onsubmit="return confirm('Kya aap sach me is handover process ko execute karna chateh hain? Purane member ka login block ho jayega!');">
                <?php Csrf::injectInput(); ?>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-danger">1. Chhodne Wala Member (Old Staff Account)</label>
                    <select name="old_staff_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose Staff Leaving --</option>
                        <?php foreach ($allStaff as $staff): ?>
                            <?php if ($staff['role'] === 'admin' && $currentRole !== 'superadmin') continue; ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['username']); ?> (<?php echo strtoupper($staff['role']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">* Note: Admin profiles ko badalne ka access sirf Super Admin ke paas show hoga.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-success">2. Naya Member (New Successor Account)</label>
                    <select name="new_staff_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose New Staff Successor --</option>
                        <?php foreach ($allStaff as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['username']); ?> (<?php echo strtoupper($staff['role']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="execute_handover" class="btn btn-danger btn-sm w-100 fw-bold">Execute Secure Account Handover</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
