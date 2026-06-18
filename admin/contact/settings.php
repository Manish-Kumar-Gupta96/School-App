<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "School Contact Configuration | Admin Control";
$active_menu = "contacts";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $phone   = trim($_POST['phone']);
    $email   = trim($_POST['email']);
    $address = trim($_POST['address']);
    $map     = trim($_POST['map']);
    $fb      = trim($_POST['fb']);
    $insta   = trim($_POST['insta']);
    $yt      = trim($_POST['yt']);

    try {
        $pdo->beginTransaction();
        
        $pdo->query("DELETE FROM contact_settings");

        $stmt = $pdo->prepare("
            INSERT INTO contact_settings (phone, email, address, google_map, facebook, instagram, youtube)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([$phone, $email, $address, $map, $fb, $insta, $yt]);

        $pdo->commit();
        $message = "Contact information settings updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to update settings: " . $e->getMessage();
    }
}

$settings = $pdo->query("SELECT * FROM contact_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">⚙️ School Contact Settings</h2>
            <p class="text-muted mb-0">Configure dynamic details (phone, email, map embed, social links) displayed on the front website page.</p>
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

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px; max-width: 750px;">
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-gears me-2 text-primary"></i>Configure Contact Details</h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone'] ?? '') ?>" class="form-control" required placeholder="e.g. +91 XXXXX XXXXX">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Contact Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>" class="form-control" required placeholder="e.g. info@school.com">
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Campus Address</label>
                <textarea name="address" class="form-control" rows="3" required placeholder="Enter school address details..."><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Google Map Embed Link / URL</label>
                <textarea name="map" class="form-control" rows="3" placeholder="Enter full iframe tag or source URL..."><?= htmlspecialchars($settings['google_map'] ?? '') ?></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Facebook URL</label>
                <input type="text" name="fb" value="<?= htmlspecialchars($settings['facebook'] ?? '') ?>" class="form-control" placeholder="https://facebook.com/...">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Instagram URL</label>
                <input type="text" name="insta" value="<?= htmlspecialchars($settings['instagram'] ?? '') ?>" class="form-control" placeholder="https://instagram.com/...">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">YouTube URL</label>
                <input type="text" name="yt" value="<?= htmlspecialchars($settings['youtube'] ?? '') ?>" class="form-control" placeholder="https://youtube.com/...">
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="save" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-save me-1"></i> Save Configuration Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
