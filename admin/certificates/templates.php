<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_template') {
        $id = (int)$_POST['id'];
        $document_name = trim($_POST['document_name']);
        $template_html = $_POST['template_html']; // Can contain raw HTML/CSS
        
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE document_templates SET document_name=?, template_html=? WHERE id=? AND school_id=?");
            $stmt->execute([$document_name, $template_html, $id, $school_id]);
            $_SESSION['success'] = "Template updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO document_templates (school_id, document_name, template_html) VALUES (?, ?, ?)");
            $stmt->execute([$school_id, $document_name, $template_html]);
            $_SESSION['success'] = "Template created successfully.";
        }
        header("Location: templates.php");
        exit;
    } elseif ($_POST['action'] === 'delete_template') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM document_templates WHERE id=? AND school_id=?")->execute([$id, $school_id]);
        $_SESSION['success'] = "Template deleted.";
        header("Location: templates.php");
        exit;
    }
}

// Fetch Templates
$stmt = $pdo->prepare("SELECT * FROM document_templates WHERE school_id = ? ORDER BY document_name ASC");
$stmt->execute([$school_id]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit_template = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM document_templates WHERE id = ? AND school_id = ?");
    $stmt->execute([(int)$_GET['edit'], $school_id]);
    $edit_template = $stmt->fetch(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Document Templates | VIC School ERP";
$page_header = "Certificate Automation Builder";
$active_menu = "certificates";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<!-- TinyMCE for WYSIWYG editing -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#template_html',
    height: 500,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code table wordcount',
    toolbar_mode: 'floating',
    content_style: "body { font-family: 'Times New Roman', serif; padding: 20px; }",
  });
</script>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Editor -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-file-signature me-2"></i><?= $edit_template ? 'Edit Template' : 'Create New Document Template' ?></h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="save_template">
                        <input type="hidden" name="id" value="<?= $edit_template['id'] ?? 0 ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Document Name <span class="text-danger">*</span></label>
                            <input type="text" name="document_name" class="form-control" value="<?= htmlspecialchars($edit_template['document_name'] ?? '') ?>" required placeholder="e.g. Bonafide Certificate">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Template HTML & Variables</label>
                            <textarea name="template_html" id="template_html" class="form-control"><?= htmlspecialchars($edit_template['template_html'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_template): ?>
                                <a href="templates.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success fw-bold"><i class="fa fa-save me-2"></i> <?= $edit_template ? 'Update Template' : 'Save Template' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Dynamic Variables Help -->
            <div class="card shadow-sm border-0 mb-4 bg-light" style="border-radius: 15px;">
                <div class="card-header py-3 bg-light border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-code me-2 text-info"></i>Dynamic Variables</h6>
                </div>
                <div class="card-body pt-0">
                    <p class="small text-muted mb-3">Copy and paste these tags into the editor. They will be auto-replaced when generating the PDF.</p>
                    <ul class="list-group list-group-flush small font-monospace">
                        <li class="list-group-item bg-transparent"><code>{{student_name}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{father_name}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{class_name}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{admission_no}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{dob}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{issue_date}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{document_no}}</code></li>
                        <li class="list-group-item bg-transparent"><code>{{qr_code_url}}</code></li>
                    </ul>
                </div>
            </div>

            <!-- Existing Templates -->
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fa fa-copy me-2 text-primary"></i>Saved Templates</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (count($templates) > 0): ?>
                            <?php foreach ($templates as $t): ?>
                                <li class="list-group-item p-3">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['document_name']) ?></div>
                                    <div class="text-muted small mb-2">ID: <?= $t['id'] ?> | Status: <?= ucfirst($t['status']) ?></div>
                                    <div class="d-flex gap-2">
                                        <a href="?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary w-50"><i class="fa fa-edit me-1"></i>Edit</a>
                                        <form method="POST" class="w-50 d-inline" onsubmit="return confirm('Delete this template?');">
                                            <input type="hidden" name="action" value="delete_template">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fa fa-trash me-1"></i>Delete</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center py-4 text-muted">No templates configured yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
