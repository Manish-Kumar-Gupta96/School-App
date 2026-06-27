<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
// Secure Lock: Sirf Superadmin hi security tokens ko force-kill kar sakta hai
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();
$message = '';

// ACTION HANDLER: Force Terminate Shared Token Pipeline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_token'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Exception: CSRF verification failed.");
    }

    $delegationId = filter_input(INPUT_POST, 'delegation_id', FILTER_VALIDATE_INT);

    if ($delegationId) {
        try {
            $db->beginTransaction();

            // 1. Database table me token ko expire (end_date ko purana) kar dein
            $stmt = $db->prepare("UPDATE access_delegation SET end_date = NOW(), is_verified = 0 WHERE id = :id");
            $stmt->execute([':id' => $delegationId]);

            // 2. Clear mapping track links from system logs variables
            AuditLogger::log('DELEGATION_FORCE_KILLED', "Superadmin force-terminated Shared Access ID #{$delegationId} instantly.");
            
            $db->commit();
            $message = "Security Command Executed: Shared connection access pipeline instantly killed!";
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Operation Failed: " . $e->getMessage();
        }
    }
}

// Fetch all active/pending delegation rows intersecting usernames mapped parameters keys
$query = "SELECT d.id, d.start_date, d.end_date, d.is_verified, 
                 u1.name as owner_name, u2.name as delegate_name 
          FROM access_delegation d
          JOIN users u1 ON d.owner_id = u1.id
          JOIN users u2 ON d.delegate_id = u2.id
          WHERE d.end_date > NOW() 
          ORDER BY d.is_verified DESC, d.created_at DESC";
          
$activeDelegations = $db->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Security Token Overrider</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5" style="max-width: 1000px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white font-weight-bold d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-user-shield"></i> Live Shared Access Token Registry Control Center</h6>
            <span class="badge bg-white text-danger">Active Token Gating Mode</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($message)): ?>
                <div class="alert alert-warning py-2 rounded-0 mb-0 small"><?php echo $message; ?></div>
            <?php endif; ?>

            <table class="table table-striped align-middle text-center mb-0 table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>Track ID</th>
                        <th>Original Account Owner</th>
                        <th>Target Delegate User</th>
                        <th>Authorized Window Range</th>
                        <th>Current Loop Status</th>
                        <th>Interception Control</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($activeDelegations) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">School system me filhal koi active account delegation sharing nahi chal rahi hai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activeDelegations as $row): ?>
                        <tr>
                            <td><code>#<?php echo $row['id']; ?></code></td>
                            <td><span class="text-dark fw-bold"><?php echo htmlspecialchars($row['owner_name']); ?></span></td>
                            <td><span class="text-primary fw-bold"><?php echo htmlspecialchars($row['delegate_name']); ?></span></td>
                            <td class="small text-muted"><?php echo $row['start_date']; ?> <strong class="text-danger">to</strong> <?php echo $row['end_date']; ?></td>
                            <td>
                                <?php if ($row['is_verified'] == 1): ?>
                                    <span class="badge bg-success small">LIVE CONNECTED</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark small">PENDING OTP</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="" method="POST" onsubmit="return confirm('Kya aap sach me is live session key link ko force-kill karna chateh hain?');">
                                    <?php Csrf::injectInput(); ?>
                                    <input type="hidden" name="delegation_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="revoke_token" class="btn btn-danger btn-sm py-0 fw-bold">Revoke Access</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
