<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add / Edit Role
if (isset($_POST['save_role'])) {
    $role_name = trim($_POST['role_name']);
    $description = trim($_POST['description']);

    if (empty($role_name)) {
        $error = "Role Name is required.";
    } else {
        // Simple insert (In a full app, you might want an update branch if ID is passed)
        $stmt = $pdo->prepare("INSERT INTO roles (school_id, role_name, description) VALUES (?, ?, ?)");
        if ($stmt->execute([CURRENT_SCHOOL_ID, $role_name, $description])) {
            $message = "Role saved successfully.";
        } else {
            $error = "Failed to save role. It may already exist.";
        }
    }
}

// Fetch Roles
$stmt_roles = $pdo->prepare("SELECT * FROM roles WHERE school_id = ? ORDER BY id ASC");
$stmt_roles->execute([CURRENT_SCHOOL_ID]);
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Manage Roles | VIC ERP";
$page_header = "Role Based Access Control";
$active_menu = "roles";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage Roles and System Access</h5>
    <div class="d-flex gap-2">
        <a href="roles.php" class="btn btn-primary">
            <i class="fa fa-user-tag me-1"></i> System Roles
        </a>
        <a href="permissions.php" class="btn btn-outline-primary">
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
    <!-- Add Role Card -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add New Role</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="role_name" class="form-control" placeholder="e.g. Content Editor" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this role's responsibilities"></textarea>
                    </div>
                    <button type="submit" name="save_role" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Role
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Roles List -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Existing Roles</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-start">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($roles) > 0): ?>
                                <?php foreach($roles as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted">#<?= $r['id'] ?></td>
                                        <td class="fw-bold text-primary">
                                            <?= htmlspecialchars($r['role_name']) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars($r['description'] ?? 'No description provided') ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="permissions.php?role_id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill px-3">
                                                <i class="fa fa-cog me-1"></i> Permissions
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No roles found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
