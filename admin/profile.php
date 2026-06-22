<?php
$root_path = "../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure user is admin
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

$admin_id = $_SESSION['admin_id'] ?? 1;
$admin = null;

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Admin Profile | VIC ERP";
$page_header = "My Profile";
$active_menu = "profile";

require_once('includes/header.php');
require_once('includes/topbar.php');
?>

<div class="main-dashboard p-4 text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">👤 Account Profile</h2>
            <p class="text-muted mb-0">View your administrator profile details.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm text-center p-4" style="border-radius: 15px;">
                <img src="<?= $root_path ?>assets/images/default-user.png" alt="Admin Photo" class="rounded-circle border border-primary border-4 mx-auto mb-3" style="width: 140px; height: 140px; object-fit: cover;">
                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($admin['name'] ?? 'Super Admin') ?></h4>
                <p class="badge bg-primary px-3 py-2 rounded-pill mb-3">Super Administrator</p>
                <div class="text-muted small"><i class="fa fa-envelope me-2"></i><?= htmlspecialchars($admin['email'] ?? 'admin@vicschool.edu.in') ?></div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-circle-info text-primary me-2"></i>Profile Information</h5>
                <hr class="text-muted mt-0 mb-4">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Full Name</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($admin['name'] ?? 'Super Admin') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($admin['email'] ?? 'admin@vicschool.edu.in') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Role Name</label>
                        <input type="text" class="form-control" value="Super Administrator" readonly style="background-color: #f8f9fa;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-semibold">Joined Date</label>
                        <input type="text" class="form-control" value="<?= isset($admin['created_at']) ? date('d M Y, h:i A', strtotime($admin['created_at'])) : date('d M Y') ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
