<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add New Permission definition
if (isset($_POST['add_permission'])) {
    $permission_name = trim($_POST['permission_name']);
    $module_name     = trim($_POST['module_name']);
    
    if (empty($permission_name) || empty($module_name)) {
        $error = "Both Permission Name and Module Name are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO permissions (permission_name, module_name) VALUES (?, ?)");
        try {
            $stmt->execute([$permission_name, $module_name]);
            $message = "Permission added successfully.";
        } catch (PDOException $e) {
            $error = "Permission might already exist or DB error.";
        }
    }
}

// Assign Permissions to Role
if (isset($_POST['assign_permissions'])) {
    $role_id = (int)$_POST['role_id'];
    $assigned_perms = $_POST['permissions'] ?? [];
    
    if ($role_id > 0) {
        $pdo->beginTransaction();
        try {
            // Remove existing permissions for this role
            $stmt_del = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt_del->execute([$role_id]);
            
            // Insert new ones
            $stmt_ins = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($assigned_perms as $perm_id) {
                $stmt_ins->execute([$role_id, (int)$perm_id]);
            }
            
            $pdo->commit();
            $message = "Role permissions updated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update permissions.";
        }
    } else {
        $error = "Please select a valid role.";
    }
}

// Fetch all roles
$stmt_roles = $pdo->prepare("SELECT * FROM roles WHERE school_id = ? ORDER BY id ASC");
$stmt_roles->execute([CURRENT_SCHOOL_ID]);
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Fetch all available permissions
$stmt_perms = $pdo->prepare("SELECT * FROM permissions ORDER BY module_name ASC, permission_name ASC");
$stmt_perms->execute();
$permissions = $stmt_perms->fetchAll(PDO::FETCH_ASSOC);

// If a role is selected via GET, fetch its current permissions
$selected_role_id = $_GET['role_id'] ?? ($_POST['role_id'] ?? 0);
$current_role_perms = [];
if ($selected_role_id > 0) {
    $stmt_crp = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
    $stmt_crp->execute([$selected_role_id]);
    $current_role_perms = $stmt_crp->fetchAll(PDO::FETCH_COLUMN);
}

// Group permissions by module
$grouped_perms = [];
foreach ($permissions as $p) {
    $grouped_perms[$p['module_name']][] = $p;
}

// Layout variables
$root_path = "../../";
$page_title = "Assign Permissions | VIC ERP";
$page_header = "Role Based Access Control";
$active_menu = "roles";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage Roles and System Access</h5>
    <div class="d-flex gap-2">
        <a href="roles.php" class="btn btn-outline-primary">
            <i class="fa fa-user-tag me-1"></i> System Roles
        </a>
        <a href="permissions.php" class="btn btn-primary">
            <i class="fa fa-key me-1"></i> Assign Permissions
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Assign Permissions Card -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Role Permission Matrix</h5>
            </div>
            <div class="card-body p-4 bg-light">
                <form method="GET" action="permissions.php" class="mb-4">
                    <label class="form-label fw-bold">Select Role to Manage</label>
                    <div class="input-group">
                        <select name="role_id" class="form-select" onchange="this.form.submit()">
                            <option value="0">-- Select Role --</option>
                            <?php foreach($roles as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($r['id'] == $selected_role_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>

                <?php if($selected_role_id > 0): ?>
                    <form method="POST">
                        <input type="hidden" name="role_id" value="<?= $selected_role_id ?>">
                        
                        <div class="accordion shadow-sm" id="permissionsAccordion">
                            <?php $i=0; foreach($grouped_perms as $module => $perms): $i++; ?>
                                <div class="accordion-item border-0 mb-2 rounded overflow-hidden">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $i>1 ? 'collapsed' : '' ?> fw-bold text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>">
                                            <i class="fa fa-folder-open me-2 text-muted"></i> Module: <?= ucfirst(htmlspecialchars($module)) ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $i ?>" class="accordion-collapse collapse <?= $i==1 ? 'show' : '' ?>" data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body bg-white row">
                                            <?php foreach($perms as $p): ?>
                                                <div class="col-md-6 mb-2">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm<?= $p['id'] ?>" <?= in_array($p['id'], $current_role_perms) ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="perm<?= $p['id'] ?>">
                                                            <?= htmlspecialchars($p['permission_name']) ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="assign_permissions" class="btn btn-success px-5 rounded-pill shadow-sm">
                                <i class="fa fa-save me-1"></i> Update Permissions
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa fa-user-lock fs-1 mb-3"></i>
                        <h5>Select a role from the dropdown above to view and assign permissions.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create New Permission Card -->
    <div class="col-lg-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Define New Permission</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Permission Name <span class="text-danger">*</span></label>
                        <input type="text" name="permission_name" class="form-control" placeholder="e.g. student_view" required>
                        <div class="form-text">Use lowercase_underscore format.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Module Name <span class="text-danger">*</span></label>
                        <input type="text" name="module_name" class="form-control" placeholder="e.g. students" required>
                    </div>
                    <button type="submit" name="add_permission" class="btn btn-dark w-100">
                        <i class="fa fa-plus me-1"></i> Add to Registry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
