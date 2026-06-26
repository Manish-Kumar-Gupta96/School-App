<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_role') {
        $role_id = (int)$_POST['role_id'];
        $role_name = trim($_POST['role_name']);
        $perms = $_POST['permissions'] ?? [];
        
        if (!empty($role_name)) {
            if ($role_id > 0) {
                // Update Role
                $stmt = $pdo->prepare("UPDATE roles SET role_name=? WHERE id=? AND school_id=?");
                $stmt->execute([$role_name, $role_id, $school_id]);
            } else {
                // Insert Role
                $stmt = $pdo->prepare("INSERT INTO roles (school_id, role_name) VALUES (?, ?)");
                $stmt->execute([$school_id, $role_name]);
                $role_id = $pdo->lastInsertId();
            }
            
            // Sync Permissions
            $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?")->execute([$role_id]);
            if (!empty($perms)) {
                $insert_stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach ($perms as $p_id) {
                    $insert_stmt->execute([$role_id, $p_id]);
                }
            }
            $_SESSION['success'] = "Role saved successfully.";
        } else {
            $_SESSION['error'] = "Role name is required.";
        }
        header("Location: roles.php");
        exit;
    } elseif ($_POST['action'] === 'delete_role') {
        $role_id = (int)$_POST['role_id'];
        // Ensure not deleting base roles (e.g., ID < 5 could be considered base, or just allow it if no users tied)
        $pdo->prepare("DELETE FROM role_permissions WHERE role_id=?")->execute([$role_id]);
        $pdo->prepare("DELETE FROM roles WHERE id=? AND school_id=?")->execute([$role_id, $school_id]);
        $_SESSION['success'] = "Role deleted.";
        header("Location: roles.php");
        exit;
    }
}

// Fetch all permissions
$all_permissions = $pdo->query("SELECT * FROM permissions ORDER BY permission_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Roles & their assigned perms
$roles = $pdo->prepare("SELECT * FROM roles WHERE school_id = ? ORDER BY id ASC");
$roles->execute([$school_id]);
$roles = $roles->fetchAll(PDO::FETCH_ASSOC);

$role_perms = [];
foreach ($roles as $r) {
    $stmt = $pdo->prepare("SELECT p.permission_name FROM role_permissions rp JOIN permissions p ON rp.permission_id = p.id WHERE rp.role_id = ?");
    $stmt->execute([$r['id']]);
    $role_perms[$r['id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$root_path = "../../";
$page_title = "RBAC Roles | VIC School ERP";
$page_header = "Role-Based Access Control";
$active_menu = "security";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Manage Role Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-user-shield me-2 text-primary"></i>Create / Edit Role</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_role">
                        <input type="hidden" name="role_id" id="edit_role_id" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Role Name <span class="text-danger">*</span></label>
                            <input type="text" name="role_name" id="edit_role_name" class="form-control" required placeholder="E.g. Assistant Librarian">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Assign Permissions</label>
                            <div class="border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach($all_permissions as $p): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input perm-checkbox" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_<?= $p['id'] ?>">
                                        <label class="form-check-label font-monospace small" for="perm_<?= $p['id'] ?>">
                                            <?= htmlspecialchars($p['permission_name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa fa-save me-2"></i> Save Role</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Roles List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-users-cog me-2 text-info"></i>Configured Roles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Role Name</th>
                                    <th>Permissions Granted</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($r['role_name']) ?></td>
                                        <td>
                                            <?php if(empty($role_perms[$r['id']])): ?>
                                                <span class="text-muted small">No permissions assigned.</span>
                                            <?php else: ?>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <?php foreach($role_perms[$r['id']] as $pname): ?>
                                                        <span class="badge bg-secondary font-monospace" style="font-size: 0.7rem;"><?= htmlspecialchars($pname) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <!-- Edit is handled via JS to populate form -->
                                            <button class="btn btn-sm btn-outline-primary" onclick="editRole(<?= $r['id'] ?>, '<?= addslashes($r['role_name']) ?>')"><i class="fa fa-edit"></i></button>
                                            
                                            <?php if($r['id'] > 5): // Don't delete system base roles ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this role permanently?');">
                                                    <input type="hidden" name="action" value="delete_role">
                                                    <input type="hidden" name="role_id" value="<?= $r['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editRole(id, name) {
    document.getElementById('edit_role_id').value = id;
    document.getElementById('edit_role_name').value = name;
    // In a real app, you'd fetch the role's assigned permissions via AJAX and check the boxes.
    // For this UI mockup, we clear checkboxes to let user re-assign.
    document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = false);
    window.scrollTo(0, 0);
}
</script>

<?php require_once('../includes/footer.php'); ?>
