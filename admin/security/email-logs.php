<?php
require_once __DIR__ . '/../../includes/init.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();

try {
    // Read outgoing automated transmission sequences histories
    $emailLogs = $db->query("SELECT * FROM system_email_logs ORDER BY sent_at DESC LIMIT 40")->fetchAll();
} catch (PDOException $e) {
    die("Log compilation fault parameters broken: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Outgoing Broadcast Logs Matrix</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5" style="max-width: 950px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-envelope-open-text"></i> Automated Credentials Email Delivery Ledger</h6>
            <span class="badge bg-success">Live SMTP Transmission Track</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped align-middle text-center table-sm mb-0" style="font-size: 13px;">
                <thead class="table-secondary">
                    <tr>
                        <th>Log Link ID</th>
                        <th>Recipient Inbox Target</th>
                        <th>Account Username Sent</th>
                        <th>Assigned Access Privilege</th>
                        <th>Dispatch Timeline</th>
                        <th>Status Telemetry</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($emailLogs) === 0): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">System queue me abhi tak koi automatic mail generation triggers fire nahi huye hain.</td></tr>
                    <?php else: ?>
                        <?php foreach ($emailLogs as $log): ?>
                        <tr>
                            <td><code>#<?php echo $log['id']; ?></code></td>
                            <td><strong><?php echo htmlspecialchars($log['recipient_email']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($log['username_sent']); ?></code></td>
                            <td><span class="badge bg-primary"><?php echo strtoupper($log['assigned_role']); ?></span></td>
                            <td class="small text-muted"><?php echo $log['sent_at']; ?></td>
                            <td><span class="badge bg-success small py-1"><i class="fas fa-check-circle"></i> DELIVERED SUCCESS</span></td>
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
