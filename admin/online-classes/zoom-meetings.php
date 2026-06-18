<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle save credentials
if (isset($_POST['save_zoom'])) {
    $client_id = trim($_POST['client_id']);
    $client_secret = trim($_POST['client_secret']);
    $account_id = trim($_POST['account_id']);

    try {
        $stmt_check = $pdo->query("SELECT COUNT(*) FROM zoom_settings");
        $count = $stmt_check->fetchColumn();

        if ($count > 0) {
            $stmt = $pdo->prepare("UPDATE zoom_settings SET client_id = ?, client_secret = ?, account_id = ? WHERE id = 1");
            $stmt->execute([$client_id, $client_secret, $account_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO zoom_settings (id, client_id, client_secret, account_id) VALUES (1, ?, ?, ?)");
            $stmt->execute([$client_id, $client_secret, $account_id]);
        }
        $success = 'Zoom credentials updated successfully.';
    } catch (PDOException $e) {
        $error = 'Error saving settings: ' . $e->getMessage();
    }
}

// Fetch Zoom settings
$zoom = null;
try {
    $zoom = $pdo->query("SELECT * FROM zoom_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silent
}

$client_id = $zoom['client_id'] ?? '';
$client_secret = $zoom['client_secret'] ?? '';
$account_id = $zoom['account_id'] ?? '';

$page_title = "Zoom Settings | VIC ERP";
$page_header = "Zoom API Setup";
$active_menu = "online-classes";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">Zoom Meetings API Integration</h2>
    </div>

    <!-- Sub links panel -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-2 d-flex gap-2">
            <a href="class-list.php" class="btn btn-sm btn-light">Classes Register</a>
            <a href="create-class.php" class="btn btn-sm btn-light">Schedule Class</a>
            <a href="zoom-meetings.php" class="btn btn-sm btn-primary">Zoom Settings</a>
            <a href="google-meet.php" class="btn btn-sm btn-light">Google Meet Settings</a>
            <a href="recordings.php" class="btn btn-sm btn-light">Class Recordings</a>
            <a href="reports.php" class="btn btn-sm btn-light">Attendance Reports</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark mb-3"><i class="fa fa-key me-2 text-primary"></i>Zoom OAuth Server-to-Server Credentials</h5>
        <hr class="text-muted mt-0 mb-4">
        
        <form method="POST" action="" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold small">Zoom Client ID</label>
                <input type="text" name="client_id" class="form-control" placeholder="Enter Client ID" value="<?= htmlspecialchars($client_id) ?>" required style="border-radius: 8px;">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold small">Zoom Client Secret</label>
                <input type="password" name="client_secret" class="form-control" placeholder="••••••••" value="<?= htmlspecialchars($client_secret) ?>" required style="border-radius: 8px;">
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold small">Zoom Account ID</label>
                <input type="text" name="account_id" class="form-control" placeholder="Enter Account ID" value="<?= htmlspecialchars($account_id) ?>" required style="border-radius: 8px;">
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" name="save_zoom" class="btn btn-primary px-4 fw-semibold" style="border-radius: 8px;">
                    <i class="fa fa-save me-2"></i> Save Zoom API Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
