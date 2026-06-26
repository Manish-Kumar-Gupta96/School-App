<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');
requireRole('super_admin');

$data = $pdo->query("SELECT * FROM permissions ORDER BY module_name, permission_name");

$root_path = "../";
$page_title = "Manage Permissions | VIC ERP";
$page_header = "System Permissions";
$active_menu = "superadmin_permissions";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Module</th>
                        <th>Permission Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $data->fetch()): ?>
                    <tr>
                        <td class="ps-4 text-muted"><?= htmlspecialchars($row['id']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars(strtoupper($row['module_name'])) ?></span></td>
                        <td class="fw-semibold"><?= htmlspecialchars($row['permission_name']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
