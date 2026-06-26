<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';
$preview_html = '';

// Generate Document
if (isset($_POST['generate_document'])) {
    $template_id = (int)$_POST['template_id'];
    $user_type   = trim($_POST['user_type']);
    $user_id     = (int)$_POST['user_id'];
    $issue_date  = date('Y-m-d');

    if (empty($template_id) || empty($user_id)) {
        $error = "Please select a Template and a User.";
    } else {
        // Get Template
        $stmt_tpl = $pdo->prepare("SELECT * FROM document_templates WHERE id = ? AND school_id = ?");
        $stmt_tpl->execute([$template_id, CURRENT_SCHOOL_ID]);
        $template = $stmt_tpl->fetch(PDO::FETCH_ASSOC);

        if (!$template) {
            $error = "Invalid template selected.";
        } else {
            $html = $template['template_html'];
            $user_data = [];

            // Fetch User Data based on type
            if ($user_type == 'student') {
                $stmt_usr = $pdo->prepare("SELECT * FROM students WHERE id = ? AND school_id = ?");
                $stmt_usr->execute([$user_id, CURRENT_SCHOOL_ID]);
                $user_data = $stmt_usr->fetch(PDO::FETCH_ASSOC);

                if ($user_data) {
                    $html = str_replace('{{student_name}}', htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']), $html);
                    $html = str_replace('{{admission_no}}', htmlspecialchars($user_data['admission_no']), $html);
                    $html = str_replace('{{dob}}', htmlspecialchars($user_data['dob']), $html);
                    // Handle class if needed
                    $html = str_replace('{{class_name}}', 'Class ' . htmlspecialchars($user_data['class']), $html);
                    $html = str_replace('{{father_name}}', 'N/A', $html); // Could join parents table if needed
                }
            } else {
                $stmt_usr = $pdo->prepare("SELECT * FROM teachers WHERE id = ? AND school_id = ?");
                $stmt_usr->execute([$user_id, CURRENT_SCHOOL_ID]);
                $user_data = $stmt_usr->fetch(PDO::FETCH_ASSOC);

                if ($user_data) {
                    $html = str_replace('{{student_name}}', htmlspecialchars($user_data['name']), $html);
                    $html = str_replace('{{admission_no}}', 'EMP-'.htmlspecialchars($user_data['id']), $html);
                    $html = str_replace('{{dob}}', 'N/A', $html);
                    $html = str_replace('{{class_name}}', 'Staff', $html);
                    $html = str_replace('{{father_name}}', 'N/A', $html);
                }
            }

            if (!$user_data) {
                $error = "User not found.";
            } else {
                // Common variables
                $html = str_replace('{{issue_date}}', date('d M Y', strtotime($issue_date)), $html);
                $html = str_replace('{{current_date}}', date('d M Y'), $html);

                // For simplicity, we just save the generated HTML text instead of generating a real PDF here.
                // In a production env, you would use DomPDF or mPDF to save a .pdf file and put the path here.
                // Here we'll simulate it by creating a dummy file path and saving to DB.
                $dummy_file_path = "uploads/documents/doc_" . time() . ".pdf";
                $qr_code = "DOC-" . CURRENT_SCHOOL_ID . "-" . $user_id . "-" . time();

                $stmt_issue = $pdo->prepare("
                    INSERT INTO issued_documents (school_id, document_id, user_type, user_id, issue_date, qr_code, file_path, issued_by)
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, ?)
                ");
                $stmt_issue->execute([
                    CURRENT_SCHOOL_ID,
                    $template_id,
                    $user_type,
                    $user_id,
                    $qr_code,
                    $dummy_file_path,
                    $_SESSION['user_id']
                ]);

                $message = "Document generated and issued successfully!";
                $preview_html = $html; // Show it to the user below
            }
        }
    }
}

// Fetch Active Templates
$stmt_templates = $pdo->prepare("SELECT id, document_name FROM document_templates WHERE school_id = ? AND status = 'active' ORDER BY document_name ASC");
$stmt_templates->execute([CURRENT_SCHOOL_ID]);
$templates = $stmt_templates->fetchAll(PDO::FETCH_ASSOC);

// Fetch Students and Teachers for Dropdowns
$stmt_stud = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE school_id = ? ORDER BY first_name ASC");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$students = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

$stmt_teach = $pdo->prepare("SELECT id, name FROM teachers WHERE school_id = ? ORDER BY name ASC");
$stmt_teach->execute([CURRENT_SCHOOL_ID]);
$teachers = $stmt_teach->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Generate Document | VIC ERP";
$page_header = "Certificate & Document Automation";
$active_menu = "documents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Generate & Issue Certificates</h5>
    <div class="d-flex gap-2">
        <a href="templates.php" class="btn btn-outline-primary">
            <i class="fa fa-file-code me-1"></i> Templates
        </a>
        <a href="generate.php" class="btn btn-primary">
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
    <!-- Generator Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Document Generation</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Template <span class="text-danger">*</span></label>
                        <select name="template_id" class="form-select" required>
                            <option value="">Select Template...</option>
                            <?php foreach($templates as $tpl): ?>
                                <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['document_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">User Type <span class="text-danger">*</span></label>
                        <select name="user_type" id="user_type" class="form-select" onchange="toggleUserSelect()" required>
                            <option value="student">Student</option>
                            <option value="staff">Staff/Teacher</option>
                        </select>
                    </div>

                    <div class="mb-4" id="student_select_div">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="user_id" id="student_id" class="form-select">
                            <option value="">Search Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4 d-none" id="staff_select_div">
                        <label class="form-label fw-semibold">Select Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" id="staff_id" class="form-select">
                            <option value="">Search Staff...</option>
                            <?php foreach($teachers as $th): ?>
                                <option value="<?= $th['id'] ?>">
                                    <?= htmlspecialchars($th['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="generate_document" class="btn btn-primary w-100" onclick="prepareSubmit()">
                        <i class="fa fa-magic me-1"></i> Generate & Issue
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Preview Box -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden; min-height: 400px;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Generated Output Preview</h5>
                <?php if($preview_html): ?>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa fa-print me-1"></i> Print</button>
                <?php endif; ?>
            </div>
            <div class="card-body p-4 bg-light">
                <?php if($preview_html): ?>
                    <div class="bg-white p-5 shadow-sm border" style="min-height: 500px;">
                        <?= $preview_html ?>
                    </div>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 flex-column text-muted py-5">
                        <i class="fa fa-file-pdf fs-1 mb-3 text-secondary" style="opacity: 0.5;"></i>
                        <p class="mb-0">Select a template and user to generate a document preview.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleUserSelect() {
    const type = document.getElementById('user_type').value;
    if (type === 'student') {
        document.getElementById('student_select_div').classList.remove('d-none');
        document.getElementById('staff_select_div').classList.add('d-none');
        document.getElementById('student_id').setAttribute('name', 'user_id');
        document.getElementById('staff_id').removeAttribute('name');
    } else {
        document.getElementById('student_select_div').classList.add('d-none');
        document.getElementById('staff_select_div').classList.remove('d-none');
        document.getElementById('staff_id').setAttribute('name', 'user_id');
        document.getElementById('student_id').removeAttribute('name');
    }
}
function prepareSubmit() {
    // Ensure the correct select field has the 'user_id' name attribute right before submit
    toggleUserSelect();
}
</script>

<?php require_once('../includes/footer.php'); ?>
