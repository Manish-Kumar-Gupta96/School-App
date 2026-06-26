<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');
requireRole('super_admin');

$data = $pdo->query("
    SELECT ll.*, u.email 
    FROM login_logs ll 
    LEFT JOIN users u ON u.id = ll.user_id 
    ORDER BY ll.id DESC 
    LIMIT 500
");

$root_path = "../";
$page_title = "Login Logs | VIC ERP";
$page_header = "Session Histories";
$active_menu = "superadmin_logs";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>IP Address</th>
                        <th>Device Info</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $data->fetch()): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?= htmlspecialchars($row['email'] ?? 'Unknown User') ?></td>
                        <td><span class="badge bg-success"><i class="fa fa-sign-in-alt me-1"></i> <?= htmlspecialchars($row['login_time']) ?></span></td>
                        <td>
                            <?php if ($row['logout_time']): ?>
                                <span class="badge bg-secondary"><i class="fa fa-sign-out-alt me-1"></i> <?= htmlspecialchars($row['logout_time']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark"><i class="fa fa-circle text-success me-1" style="font-size:0.6em;"></i> Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($row['ip_address']) ?></td>
                        <td class="small text-muted" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($row['device_info']) ?>">
                            <?= htmlspecialchars($row['device_info']) ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
