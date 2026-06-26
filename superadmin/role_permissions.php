<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');
requireRole('super_admin');

$roleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;

// Fetch Role Details
$stmt = $pdo->prepare("SELECT * FROM roles WHERE id = ?");
$stmt->execute([$roleId]);
$role = $stmt->fetch();

if (!$role) {
    header("Location: roles.php");
    exit();
}

// Save logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?")->execute([$roleId]);

    if (isset($_POST['permissions']) && is_array($_POST['permissions'])) {
        $stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($_POST['permissions'] as $permissionId) {
            $stmt->execute([$roleId, $permissionId]);
        }
    }
    
    $_SESSION['success'] = "Permissions updated successfully.";
    header("Location: role_permissions.php?role_id=$roleId");
    exit();
}

// Fetch all permissions grouped by module
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY module_name, permission_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch currently mapped permissions for this role
$currentPerms = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$currentPerms->execute([$roleId]);
$mappedPerms = $currentPerms->fetchAll(PDO::FETCH_COLUMN);

// Group permissions by module
$groupedPerms = [];
foreach ($permissions as $p) {
    $groupedPerms[$p['module_name']][] = $p;
}

$root_path = "../";
$page_title = "Role Permissions | VIC ERP";
$page_header = "Manage Role Permissions";
$active_menu = "superadmin_roles";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Role: <span class="fw-bold text-primary"><?= htmlspecialchars($role['role_name']) ?></span></h5>
    <a href="roles.php" class="btn btn-outline-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Roles
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<form method="post">
    <div class="row">
        <?php foreach ($groupedPerms as $module => $perms): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light fw-bold text-uppercase">
                        <?= htmlspecialchars($module) ?>
                    </div>
                    <div class="card-body">
                        <?php foreach ($perms as $p): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>" <?= in_array($p['id'], $mappedPerms) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="perm_<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['permission_name']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-end mt-3 mb-5">
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Save Permissions</button>
    </div>
</form>

<?php require_once('../includes/footer.php'); ?>
