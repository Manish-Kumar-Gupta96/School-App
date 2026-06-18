<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Notification Templates | Admin Control";
$active_menu = "notifications";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

// Handle Template Save (Insert/Update)
if (isset($_POST['save_template'])) {
    $id = isset($_POST['id']) ? (int)$POST['id'] : 0;
    $template_name = trim($_POST['template_name']);
    $title = trim($_POST['title']);
    $message_content = trim($_POST['message']);

    if (empty($template_name) || empty($title) || empty($message_content)) {
        $error = "All fields are required.";
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE notification_templates
                    SET template_name = ?, title = ?, message = ?
                    WHERE id = ?
                ");
                $stmt->execute([$template_name, $title, $message_content, $id]);
                $message = "Template updated successfully!";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO notification_templates (template_name, title, message)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$template_name, $title, $message_content]);
                $message = "Template created successfully!";
            }

            // Log audit
            require_once('../includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'],
                'Notification Template Saved: ' . $template_name,
                'Notifications'
            );
        } catch (Exception $e) {
            $error = "Failed to save template: " . $e->getMessage();
        }
    }
}

// Handle Template Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM notification_templates WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Template deleted successfully!";
    } catch (Exception $e) {
        $error = "Failed to delete template: " . $e->getMessage();
    }
}

// Fetch all templates
$templates = $pdo->query("SELECT * FROM notification_templates ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch edit template details if requested
$edit_template = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM notification_templates WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_template = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📋 Notification Templates</h2>
            <p class="text-muted mb-0">Manage reusable message layouts for notifications like fee alerts, parent-teacher calls, etc.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="send.php" class="btn btn-outline-primary"><i class="fa fa-paper-plane me-1"></i> Send Alert</a>
            <a href="settings.php" class="btn btn-outline-primary"><i class="fa fa-cogs me-1"></i> Settings</a>
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

    <div class="row g-4">
        <!-- Editor Column -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-3 border-bottom pb-2 text-primary">
                    <i class="fa <?= $edit_template ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i><?= $edit_template ? 'Edit Template' : 'Add New Template' ?>
                </h5>
                <form method="POST">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_template['id'] ?? '0') ?>">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Template Identifier Name</label>
                        <input type="text" name="template_name" class="form-control" placeholder="e.g. Fee Reminder" required value="<?= htmlspecialchars($edit_template['template_name'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Title / Subject</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Fee Payment Outstanding Dues" required value="<?= htmlspecialchars($edit_template['title'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Message Body Template</label>
                        <textarea name="message" class="form-control" rows="8" placeholder="Compose alert body layout..." required><?= htmlspecialchars($edit_template['message'] ?? '') ?></textarea>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="save_template" class="btn btn-primary fw-semibold">
                            <i class="fa fa-save me-1"></i> <?= $edit_template ? 'Update Template' : 'Save Template' ?>
                        </button>
                        <?php if ($edit_template): ?>
                            <a href="templates.php" class="btn btn-secondary btn-sm">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Templates List -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-warning"></i>Notification Templates Matrix</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Name</th>
                                    <th>Subject / Title</th>
                                    <th class="pe-4 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($templates) > 0): ?>
                                    <?php foreach($templates as $tpl): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($tpl['template_name']) ?></td>
                                            <td class="text-secondary"><?= htmlspecialchars($tpl['title']) ?></td>
                                            <td class="pe-4 text-center">
                                                <a href="?edit_id=<?= $tpl['id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="?delete_id=<?= $tpl['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this template?');">
                                                    <i class="fa fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">
                                            No templates configured yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
