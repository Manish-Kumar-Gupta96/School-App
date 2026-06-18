<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Publish Announcements | Admin Control";
$active_menu = "notices";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['send'])){
    $msg_content = trim($_POST['message'] ?? '');
    $role        = $_POST['role'];
    $priority    = $_POST['priority'];

    if(empty($msg_content)){
        $error = "Announcement message cannot be empty.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO announcements (message, target_role, priority)
            VALUES (?,?,?)
        ");
        $stmt->execute([$msg_content, $role, $priority]);
        $message = "Announcement published successfully!";
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📢 Publish Announcements</h2>
            <p class="text-muted mb-0">Broadcast alerts or general notices targeting specific roles or groups in the ERP system.</p>
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

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px; max-width: 700px;">
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-bullhorn me-2 text-primary"></i>Broadcast Alert Message</h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">Announcement Message</label>
                <textarea name="message" class="form-control" rows="5" placeholder="Enter broadcast text detail..." required></textarea>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-semibold">Target Audience Role</label>
                <select name="role" class="form-select" required>
                    <option>All</option>
                    <option>Student</option>
                    <option>Teacher</option>
                    <option>Parent</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Priority Level</label>
                <select name="priority" class="form-select" required>
                    <option>LOW</option>
                    <option>MEDIUM</option>
                    <option>HIGH</option>
                </select>
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="send" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-paper-plane me-1"></i> Send Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
