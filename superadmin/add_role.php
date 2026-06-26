<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');
requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['role_name'];
    $desc = $_POST['description'];

    $stmt = $pdo->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
    $stmt->execute([$name, $desc]);

    header("Location: roles.php");
    exit();
}

$root_path = "../";
$page_title = "Add Role | VIC ERP";
$page_header = "Add New Role";
$active_menu = "superadmin_roles";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="card shadow border-0" style="border-radius: 15px; max-width: 600px; margin: auto;">
    <div class="card-body p-4">
        <h5 class="fw-bold text-dark mb-4">Create New Role</h5>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Role Name</label>
                <input type="text" name="role_name" class="form-control" placeholder="e.g., librarian" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this role"></textarea>
            </div>
            <div class="d-flex justify-content-between">
                <a href="roles.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Role</button>
            </div>
        </form>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
