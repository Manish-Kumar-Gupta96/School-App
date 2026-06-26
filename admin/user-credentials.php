<?php
require_once('../config/database.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow Super Admin (Role ID 1)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    echo "Access Denied! Only Super Administrators can view this page.";
    exit;
}

$root_path = "../";
$page_title = "User Credentials Management | VIC ERP";
$page_header = "User Credentials & Auth";
$active_menu = "settings";

require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $id = (int)$_POST['user_id'];
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $status = $_POST['status'];
        $new_pass = $_POST['new_password'] ?? '';
        $force_change = isset($_POST['force_password_change']) ? 1 : 0;
        
        try {
            if (!empty($new_pass)) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, status=?, password=?, force_password_change=? WHERE id=?");
                $stmt->execute([$name, $email, $status, $hash, $force_change, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, status=?, force_password_change=? WHERE id=?");
                $stmt->execute([$name, $email, $status, $force_change, $id]);
            }
            $message = "User details updated successfully.";
        } catch (PDOException $e) {
            $error = "Error updating user: " . $e->getMessage();
        }
    } 
    elseif (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        
        if ($id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                $stmt->execute([$id]);
                $message = "User account deleted successfully.";
            } catch (PDOException $e) {
                $error = "Error deleting user: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['generate_temp'])) {
        $id = (int)$_POST['user_id'];
        $temp_pass = bin2hex(random_bytes(4)); // 8 chars
        $hash = password_hash($temp_pass, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET password=?, force_password_change=1 WHERE id=?");
            $stmt->execute([$hash, $id]);
            $message = "Temporary password generated for User ID $id: <strong>$temp_pass</strong> (User must change it on next login)";
        } catch (PDOException $e) {
            $error = "Error generating password: " . $e->getMessage();
        }
    }
}

// Fetch all users with their roles
$stmt = $pdo->query("
    SELECT u.*, r.role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY u.role_id ASC, u.name ASC
");
$usersList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-bottom py-3">
                <h6 class="mb-0 fw-bold text-primary">Manage User Credentials</h6>
                <p class="text-muted small mb-0">View all logins and reset passwords.</p>
            </div>
            <div class="card-body p-0">
                <?php if($message): ?>
                    <div class="alert alert-success m-3"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="alert alert-danger m-3"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Name</th>
                                <th>Login ID (Email)</th>
                                <th>Role</th>
                                <th>Password</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usersList as $usr): ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?= $usr['id'] ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($usr['name']) ?></td>
                                    <td>
                                        <span class="text-primary font-monospace"><?= htmlspecialchars($usr['email']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <?= htmlspecialchars($usr['role_name'] ?: 'Unknown') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border"><i class="fa fa-lock"></i> Secured</span>
                                        <?php if(isset($usr['force_password_change']) && $usr['force_password_change']): ?>
                                            <span class="badge bg-warning-subtle text-warning mt-1 d-block"><i class="fa fa-exclamation-circle"></i> Must Change Pass</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($usr['status'] == 'ACTIVE'): ?>
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger"><?= htmlspecialchars($usr['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <!-- Temp Password -->
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Generate a temporary password and force change on next login?');">
                                            <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                            <button type="submit" name="generate_temp" class="btn btn-sm btn-outline-warning btn-icon" title="Generate Temp Password">
                                                <i class="fa fa-key"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit User" data-bs-toggle="modal" data-bs-target="#editModal<?= $usr['id'] ?>">
                                            <i class="fa fa-edit"></i>
                                        </button>

                                        <!-- Delete Form -->
                                        <?php if($usr['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user\'s login access?');">
                                            <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                            <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger btn-icon" title="Delete Access">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $usr['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit User Credentials</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($usr['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Login ID (Email)</label>
                                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usr['email']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="ACTIVE" <?= $usr['status'] == 'ACTIVE' ? 'selected' : '' ?>>Active</option>
                                                        <option value="INACTIVE" <?= $usr['status'] != 'ACTIVE' ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="text" name="new_password" class="form-control" placeholder="Leave blank to keep unchanged">
                                                    <small class="text-muted">Set a new password here to update their login credentials.</small>
                                                </div>
                                                <div class="mb-3 form-check">
                                                    <input type="checkbox" name="force_password_change" class="form-check-input" id="force_change_<?= $usr['id'] ?>" <?= (isset($usr['force_password_change']) && $usr['force_password_change']) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="force_change_<?= $usr['id'] ?>">Force password change on next login</label>
                                                </div>
                                                <small class="text-muted d-block mt-2"><i class="fa fa-info-circle"></i> Updating these details will only affect their login credentials. It will not update their entity record.</small>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_user" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    </div>
</div>

<style>
.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    line-height: 32px;
    text-align: center;
    border-radius: 8px;
}
</style>

<?php require_once('includes/footer.php'); ?>
