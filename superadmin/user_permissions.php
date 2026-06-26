<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once('../config/database.php');
require_once('../includes/access.php');
requireRole('super_admin');

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Fetch User
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Fetch all permissions
$permissions = $pdo->query("SELECT * FROM permissions ORDER BY module_name, permission_name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch current user overrides
$currentOverrides = $pdo->prepare("SELECT permission_id, allow_deny FROM user_permissions WHERE user_id = ?");
$currentOverrides->execute([$userId]);
$userPerms = [];
while ($row = $currentOverrides->fetch()) {
    $userPerms[$row['permission_id']] = $row['allow_deny'];
}

// Group permissions
$groupedPerms = [];
foreach ($permissions as $p) {
    $groupedPerms[$p['module_name']][] = $p;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Clear existing
    $pdo->prepare("DELETE FROM user_permissions WHERE user_id=?")->execute([$userId]);

    $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, allow_deny) VALUES (?, ?, ?)");

    // Save allows
    if (isset($_POST['allow']) && is_array($_POST['allow'])) {
        foreach ($_POST['allow'] as $pid) {
            $stmt->execute([$userId, $pid, 'allow']);
        }
    }
    
    // Save denys
    if (isset($_POST['deny']) && is_array($_POST['deny'])) {
        foreach ($_POST['deny'] as $pid) {
            $stmt->execute([$userId, $pid, 'deny']);
        }
    }

    $_SESSION['success'] = "User specific overrides saved.";
    header("Location: user_permissions.php?user_id=$userId");
    exit();
}

$root_path = "../";
$page_title = "User Permission Overrides | VIC ERP";
$page_header = "User Overrides";
$active_menu = "superadmin_users";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Overrides for User: <span class="fw-bold text-primary"><?= htmlspecialchars($user['email']) ?></span></h5>
    <a href="../admin/user-credentials.php" class="btn btn-outline-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Users
    </a>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<form method="post">
    <div class="row">
        <?php foreach ($groupedPerms as $module => $perms): ?>
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-light fw-bold text-uppercase d-flex justify-content-between">
                        <span><?= htmlspecialchars($module) ?></span>
                        <span class="badge bg-secondary">System Default Applies if None Checked</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Permission</th>
                                    <th class="text-center text-success">Explicit Allow</th>
                                    <th class="text-center text-danger">Explicit Deny</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($perms as $p): ?>
                                    <tr>
                                        <td class="ps-3"><?= htmlspecialchars($p['permission_name']) ?></td>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" name="allow[]" value="<?= $p['id'] ?>" <?= (isset($userPerms[$p['id']]) && $userPerms[$p['id']] == 'allow') ? 'checked' : '' ?>>
                                        </td>
                                        <td class="text-center">
                                            <input class="form-check-input" type="checkbox" name="deny[]" value="<?= $p['id'] ?>" <?= (isset($userPerms[$p['id']]) && $userPerms[$p['id']] == 'deny') ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="text-end mt-3 mb-5">
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Save Overrides</button>
    </div>
</form>

<?php require_once('../includes/footer.php'); ?>
