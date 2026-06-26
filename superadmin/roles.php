<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');

// We should require super_admin role for this page
requireRole('super_admin');

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name");

$root_path = "../";
$page_title = "Manage Roles | VIC ERP";
$page_header = "Role Management";
$active_menu = "superadmin_roles";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">System Roles</h5>
    <a href="add_role.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Add Role
    </a>
</div>

<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $roles->fetch()): ?>
                    <tr>
                        <td class="ps-4"><?= htmlspecialchars($row['id']) ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($row['role_name']) ?></td>
                        <td class="text-muted"><?= htmlspecialchars($row['description']) ?></td>
                        <td class="text-center pe-4">
                            <a href="role_permissions.php?role_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-info" title="Manage Permissions">
                                <i class="fa fa-shield-alt"></i> Permissions
                            </a>
                            <a href="edit_role.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Role">
                                <i class="fa fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
