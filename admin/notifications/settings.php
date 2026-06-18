<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Notification Settings | Admin Control";
$active_menu = "notifications";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

$keys = [
    'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
    'twilio_sid', 'twilio_token', 'twilio_number'
];

if (isset($_POST['save_settings'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        foreach ($keys as $key) {
            $val = trim($_POST[$key] ?? '');
            $stmt->execute([$key, $val]);
        }
        $pdo->commit();
        $message = "Settings updated successfully!";

        // Log audit log
        require_once('../includes/audit-helper.php');
        addAuditLog(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['name'] ?? 'Admin',
            $_SESSION['role'],
            'Notification Gateway Settings Updated',
            'Notifications'
        );
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to save settings: " . $e->getMessage();
    }
}

// Fetch current settings
$settings = [];
foreach ($keys as $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $settings[$key] = $stmt->fetchColumn() ?: '';
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">⚙️ Gateway Settings</h2>
            <p class="text-muted mb-0">Configure SMTP email server settings and Twilio SMS/WhatsApp integration credentials.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="send.php" class="btn btn-outline-primary"><i class="fa fa-paper-plane me-1"></i> Send Alert</a>
            <a href="templates.php" class="btn btn-outline-primary"><i class="fa fa-file-invoice me-1"></i> Templates</a>
            <a href="history.php" class="btn btn-outline-primary"><i class="fa fa-history me-1"></i> History</a>
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

    <form method="POST">
        <div class="row g-4">
            <!-- SMTP Settings -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; height: 100%;">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-2 text-primary"><i class="fa fa-envelope me-2"></i>SMTP Mail Settings</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">SMTP Host</label>
                        <input type="text" name="smtp_host" class="form-control" placeholder="e.g. smtp.mailtrap.io" value="<?= htmlspecialchars($settings['smtp_host']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">SMTP Port</label>
                        <input type="text" name="smtp_port" class="form-control" placeholder="e.g. 587" value="<?= htmlspecialchars($settings['smtp_port']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">SMTP Username</label>
                        <input type="text" name="smtp_user" class="form-control" placeholder="username/email" value="<?= htmlspecialchars($settings['smtp_user']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control" placeholder="password" value="<?= htmlspecialchars($settings['smtp_pass']) ?>">
                    </div>
                </div>
            </div>

            <!-- Twilio Settings -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; height: 100%;">
                    <h5 class="fw-bold text-dark mb-4 border-bottom pb-2 text-primary"><i class="fa fa-sms me-2"></i>Twilio Gateway Settings</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Account SID</label>
                        <input type="text" name="twilio_sid" class="form-control" placeholder="ACXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX" value="<?= htmlspecialchars($settings['twilio_sid']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Auth Token</label>
                        <input type="password" name="twilio_token" class="form-control" placeholder="auth_token_here" value="<?= htmlspecialchars($settings['twilio_token']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Twilio Phone Number</label>
                        <input type="text" name="twilio_number" class="form-control" placeholder="+1234567890" value="<?= htmlspecialchars($settings['twilio_number']) ?>">
                    </div>
                    
                    <div class="alert alert-info py-2 mb-0 mt-3" style="font-size: 0.85rem;">
                        <i class="fa fa-info-circle me-1"></i> These credentials will be loaded by SMS and WhatsApp broadcast APIs.
                    </div>
                </div>
            </div>

            <div class="col-12 text-end">
                <button type="submit" name="save_settings" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-save me-1"></i> Save Configuration
                </button>
            </div>
        </div>
    </form>
</div>

<?php
require_once('../includes/footer.php');
?>
