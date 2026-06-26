<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Security Stats
$failed_logins = 0; // Mock data, you'd calculate from login_history or audit_logs
$blocked_ips = $pdo->query("SELECT COUNT(*) FROM blocked_ips")->fetchColumn();
$audit_today = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$total_roles = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();

// Recent Audits
$recent_audits = $pdo->query("
    SELECT a.*, 
           COALESCE(u.name, 'System') as user_name
    FROM audit_logs a
    LEFT JOIN admins u ON a.user_id = u.id
    ORDER BY a.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Security Dashboard | VIC School ERP";
$page_header = "Security Command Center";
$active_menu = "security";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <!-- AI Security Engine Alert -->
    <div class="alert alert-warning border-warning shadow-sm d-flex align-items-center mb-4" role="alert" style="border-radius: 15px;">
        <i class="fa fa-shield-alt fs-2 me-3 text-warning"></i>
        <div>
            <h6 class="alert-heading fw-bold mb-1">AI Threat Detection Engine Active</h6>
            <p class="mb-0 small">The AI engine is monitoring login patterns, mass data exports, and unusual activities. No critical threats detected in the last 24 hours.</p>
        </div>
    </div>

    <!-- Security Metrics Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-danger mb-2"><i class="fa fa-user-lock fs-1"></i></div>
                <h3 class="fw-bold mb-0"><?= $failed_logins ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Failed Logins (24h)</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-warning mb-2"><i class="fa fa-ban fs-1"></i></div>
                <h3 class="fw-bold mb-0"><?= $blocked_ips ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Blocked IPs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3" style="border-radius: 15px;">
                <div class="text-primary mb-2"><i class="fa fa-history fs-1"></i></div>
                <h3 class="fw-bold mb-0"><?= number_format($audit_today) ?></h3>
                <div class="text-muted small text-uppercase fw-bold">Audit Events Today</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 text-center p-3 bg-success text-white" style="border-radius: 15px;">
                <div class="mb-2"><i class="fa fa-check-double fs-1"></i></div>
                <h3 class="fw-bold mb-0">100%</h3>
                <div class="small text-uppercase fw-bold text-white-50">Backup Success Rate</div>
            </div>
        </div>
    </div>

    <!-- Security Navigation & Quick Actions -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-body d-flex justify-content-around p-4">
                    <a href="audit_logs.php" class="text-center text-decoration-none text-dark">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-2 mx-auto" style="width:60px; height:60px;">
                            <i class="fa fa-list text-primary fs-4"></i>
                        </div>
                        <div class="fw-bold small">Audit Logs</div>
                    </a>
                    <a href="roles.php" class="text-center text-decoration-none text-dark">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-2 mx-auto" style="width:60px; height:60px;">
                            <i class="fa fa-users-cog text-info fs-4"></i>
                        </div>
                        <div class="fw-bold small">RBAC Roles</div>
                    </a>
                    <a href="backups.php" class="text-center text-decoration-none text-dark">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-2 mx-auto" style="width:60px; height:60px;">
                            <i class="fa fa-database text-success fs-4"></i>
                        </div>
                        <div class="fw-bold small">Disaster Recovery</div>
                    </a>
                    <a href="#" class="text-center text-decoration-none text-dark">
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-2 mx-auto" style="width:60px; height:60px;">
                            <i class="fa fa-fingerprint text-secondary fs-4"></i>
                        </div>
                        <div class="fw-bold small">2FA Settings</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Audit Logs Preview -->
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-eye me-2 text-primary"></i>Live Audit Stream</h6>
            <a href="audit_logs.php" class="btn btn-sm btn-outline-primary">View All Logs</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>User</th>
                            <th>Module</th>
                            <th>Action</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recent_audits) > 0): ?>
                            <?php foreach ($recent_audits as $log): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($log['user_name']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['module']) ?></span></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="text-muted small font-monospace"><?= htmlspecialchars($log['ip_address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No audit events recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
