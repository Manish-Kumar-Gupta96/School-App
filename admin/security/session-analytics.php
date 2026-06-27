<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../config/database.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();

try {
    // Read operational streams intersecting tracking sessions
    $query = "SELECT s.id, u.username, u.role, s.ip_address, s.last_activity_at, s.user_agent 
              FROM active_user_sessions s 
              JOIN users u ON s.user_id = u.id 
              ORDER BY s.last_activity_at DESC LIMIT 30";
              
    $activeSessions = $db->query($query)->fetchAll();
} catch (PDOException $e) {
    die("Security Grid Compilation Exception: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Active Session Monitoring Node</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i> Live Session Security Diagnostics Center</h5>
            <span class="badge bg-danger">Real-Time Auditing Mode</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped align-middle table-sm mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th>Session Link ID</th>
                        <th>User Principal</th>
                        <th>Role Matrix</th>
                        <th>IP Origin Address</th>
                        <th>Last Socket Hit</th>
                        <th>Client Browser Agent Spec</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($activeSessions) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No active data session nodes currently instantiated.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activeSessions as $session): ?>
                        <tr>
                            <td><code>#<?php echo $session['id']; ?></code></td>
                            <td><strong><?php echo htmlspecialchars($session['username']); ?></strong></td>
                            <td><span class="badge bg-primary"><?php echo strtoupper($session['role']); ?></span></td>
                            <td><code><?php echo $session['ip_address']; ?></code></td>
                            <td class="small text-muted"><?php echo $session['last_activity_at']; ?></td>
                            <td class="small text-truncate text-muted" style="max-width: 300px;">
                                <?php echo htmlspecialchars($session['user_agent']); ?>
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
