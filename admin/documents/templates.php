<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Template
if (isset($_POST['save_template'])) {
    $document_name = trim($_POST['document_name']);
    $template_html = trim($_POST['template_html']);
    $status        = trim($_POST['status']);

    if (empty($document_name) || empty($template_html)) {
        $error = "Document Name and Template HTML are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO document_templates (school_id, document_name, template_html, status)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $document_name,
            $template_html,
            $status
        ]);
        $message = "Template saved successfully!";
    }
}

// Fetch Templates
$stmt_templates = $pdo->prepare("
    SELECT * 
    FROM document_templates
    WHERE school_id = ?
    ORDER BY created_at DESC
");
$stmt_templates->execute([CURRENT_SCHOOL_ID]);
$templatesList = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Document Templates | VIC ERP";
$page_header = "Certificate & Document Automation";
$active_menu = "documents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage automatic document generation and history</h5>
    <div class="d-flex gap-2">
        <a href="templates.php" class="btn btn-primary">
            <i class="fa fa-file-code me-1"></i> Templates
        </a>
        <a href="generate.php" class="btn btn-outline-primary">
            <i class="fa fa-cogs me-1"></i> Generate Document
        </a>
        <a href="issued.php" class="btn btn-outline-success">
            <i class="fa fa-history me-1"></i> Issued Documents
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Add Template Card -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Create New Template</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="alert alert-info py-2 small mb-3">
                    <strong>Supported Variables:</strong><br>
                    <code>{{student_name}}</code>, <code>{{admission_no}}</code>, <code>{{class_name}}</code>, <code>{{dob}}</code>, <code>{{father_name}}</code>, <code>{{issue_date}}</code>, <code>{{current_date}}</code>
                </div>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Document Name <span class="text-danger">*</span></label>
                        <input type="text" name="document_name" class="form-control" placeholder="e.g. Transfer Certificate" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">HTML Template Content <span class="text-danger">*</span></label>
                        <textarea name="template_html" class="form-control" rows="12" placeholder="<h1>Transfer Certificate</h1>
<p>This is to certify that {{student_name}}...</p>" required></textarea>
                    </div>

                    <button type="submit" name="save_template" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Template
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Templates List Card -->
    <div class="col-lg-7">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Available Templates</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Document Name</th>
                                <th>Status</th>
                                <th>Created On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($templatesList) > 0): ?>
                                <?php foreach($templatesList as $tpl): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?= htmlspecialchars($tpl['document_name']) ?>
                                        </td>
                                        <td>
                                            <?php if ($tpl['status'] == 'active'): ?>
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small text-muted"><i class="fa fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($tpl['created_at'])) ?></div>
                                        </td>
                                        <td>
                                            <!-- Simple view button to preview raw HTML -->
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#previewModal<?= $tpl['id'] ?>">
                                                <i class="fa fa-eye"></i> Preview
                                            </button>
                                            
                                            <!-- Preview Modal -->
                                            <div class="modal fade" id="previewModal<?= $tpl['id'] ?>" tabindex="-1" aria-hidden="true">
                                              <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                  <div class="modal-header">
                                                    <h5 class="modal-title">Preview: <?= htmlspecialchars($tpl['document_name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                  </div>
                                                  <div class="modal-body p-4 border" style="background: #f9f9f9;">
                                                    <div class="bg-white p-4 shadow-sm">
                                                        <?= $tpl['template_html'] // Display raw HTML ?>
                                                    </div>
                                                  </div>
                                                </div>
                                              </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-file-code fs-2 mb-2 d-block"></i>
                                        No document templates created yet.
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

<?php require_once('../includes/footer.php'); ?>
