<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Role & Permission Settings | Admin Control";
$active_menu = "settings";
require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

// Check if update form submitted
if (isset($_POST['update_permissions'])) {
    try {
        $pdo->beginTransaction();
        
        $role_id = (int)$_POST['role_id'];
        
        // Modules list
        $modules = ['attendance', 'marks', 'accounts'];
        
        foreach ($modules as $module) {
            $view   = isset($_POST["perm"][$module]["view"]) ? 1 : 0;
            $add    = isset($_POST["perm"][$module]["add"]) ? 1 : 0;
            $edit   = isset($_POST["perm"][$module]["edit"]) ? 1 : 0;
            $delete = isset($_POST["perm"][$module]["delete"]) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO role_module_permissions (role_id, module, can_view, can_add, can_edit, can_delete)
                VALUES (?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE 
                    can_view = VALUES(can_view),
                    can_add = VALUES(can_add),
                    can_edit = VALUES(can_edit),
                    can_delete = VALUES(can_delete)
            ");
            $stmt->execute([$role_id, $module, $view, $add, $edit, $delete]);
        }
        
        $pdo->commit();
        $message = "Permissions updated successfully for selected role!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update permissions: " . $e->getMessage();
    }
}

// Get all roles
$roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Selected role
$selected_role_id = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 1;

// Get permissions for selected role
$stmt_p = $pdo->prepare("SELECT * FROM role_module_permissions WHERE role_id=?");
$stmt_p->execute([$selected_role_id]);
$permissions_list = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

$perms = [];
foreach ($permissions_list as $p) {
    $perms[$p['module']] = [
        'view' => $p['can_view'],
        'add' => $p['can_add'],
        'edit' => $p['can_edit'],
        'delete' => $p['can_delete']
    ];
}

// Fallback default structure
$modules = ['attendance', 'marks', 'accounts'];
foreach ($modules as $m) {
    if (!isset($perms[$m])) {
        $perms[$m] = ['view' => 0, 'add' => 0, 'edit' => 0, 'delete' => 0];
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔐 Role & Permission Manager</h2>
            <p class="text-muted mb-0">Review roles and configure dynamic module access controls across the school ERP.</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Roles List Sidebar Card -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-users me-2 text-primary"></i>ERP User Roles</h5>
                <div class="list-group list-group-flush">
                    <?php foreach($roles as $r): ?>
                        <a href="?role_id=<?= $r['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 border-0 px-2 <?= ($selected_role_id == $r['id']) ? 'active rounded bg-primary-subtle text-primary fw-bold' : 'text-secondary' ?>" style="font-size: 0.95rem;">
                            <span><i class="fa fa-circle-user me-2"></i> <?= htmlspecialchars($r['role_name']) ?></span>
                            <i class="fa fa-chevron-right small"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Permissions Editor Grid -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <?php
                    // Find selected role name
                    $sel_role_name = 'Role';
                    foreach($roles as $r) {
                        if ($r['id'] == $selected_role_id) $sel_role_name = $r['role_name'];
                    }
                    ?>
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-key me-2 text-warning"></i>Access Matrix - <?= htmlspecialchars($sel_role_name) ?></h5>
                </div>
                <div class="card-body p-0">
                    <form method="POST">
                        <input type="hidden" name="role_id" value="<?= $selected_role_id ?>">
                        
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center">
                                <thead class="table-light text-start">
                                    <tr>
                                        <th class="ps-4" style="width: 30%;">Module</th>
                                        <th class="text-center">View</th>
                                        <th class="text-center">Add</th>
                                        <th class="text-center">Edit</th>
                                        <th class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody class="text-start">
                                    <?php foreach($modules as $m): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark text-capitalize"><?= htmlspecialchars($m) ?></td>
                                            
                                            <td class="text-center">
                                                <input class="form-check-input border-secondary" type="checkbox" name="perm[<?= $m ?>][view]" value="1" <?= $perms[$m]['view'] ? 'checked' : '' ?>>
                                            </td>
                                            
                                            <td class="text-center">
                                                <input class="form-check-input border-secondary" type="checkbox" name="perm[<?= $m ?>][add]" value="1" <?= $perms[$m]['add'] ? 'checked' : '' ?>>
                                            </td>
                                            
                                            <td class="text-center">
                                                <input class="form-check-input border-secondary" type="checkbox" name="perm[<?= $m ?>][edit]" value="1" <?= $perms[$m]['edit'] ? 'checked' : '' ?>>
                                            </td>
                                            
                                            <td class="text-center">
                                                <input class="form-check-input border-secondary" type="checkbox" name="perm[<?= $m ?>][delete]" value="1" <?= $perms[$m]['delete'] ? 'checked' : '' ?>>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 bg-light text-end">
                            <button type="submit" name="update_permissions" class="btn btn-primary px-5 py-2 fw-semibold">
                                <i class="fa fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
