<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$error = '';

if(isset($_POST['save'])){
    $title        = trim($_POST['title']);
    $notice_date  = $_POST['notice_date'];
    $expiry_date  = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $notice_for   = $_POST['notice_for'];
    $description  = $_POST['description'];
    $status       = $_POST['status'];

    $attachment = '';

    if(!empty($_FILES['attachment']['name'])){
        $extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($extension), $allowed)) {
            $error = "Invalid file type. Only PDF, DOC, DOCX, and images are allowed.";
        } else {
            // Ensure uploads/notices/ exists
            if (!is_dir("../../uploads/notices/")) {
                mkdir("../../uploads/notices/", 0777, true);
            }
            $attachment = time() . "_" . rand(1000,9999) . "." . $extension;
            if(!move_uploaded_file($_FILES['attachment']['tmp_name'], "../../uploads/notices/" . $attachment)) {
                $error = "Failed to upload file.";
            }
        }
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("
            INSERT INTO notices(
                title,
                notice_date,
                expiry_date,
                notice_for,
                description,
                attachment,
                status
            )
            VALUES(?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $title,
            $notice_date,
            $expiry_date,
            $notice_for,
            $description,
            $attachment,
            $status
        ]);

        header("Location: index.php?saved=1");
        exit();
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Create Notice | VIC ERP";
$page_header = "Create Notice";
$active_menu = "notices";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Publish a new notice or announcement</h5>
    <a href="index.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Notices
    </a>
</div>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow border-0 mb-4" style="border-radius: 15px;">
    <div class="card-body p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Notice Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="Enter notice title" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Notice Date <span class="text-danger">*</span></label>
                    <input type="date" name="notice_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Audience (Notice For) <span class="text-danger">*</span></label>
                    <select name="notice_for" class="form-select" required>
                        <option value="All">All Roles</option>
                        <option value="Students">Students Only</option>
                        <option value="Teachers">Teachers Only</option>
                        <option value="Parents">Parents Only</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="Published">Published</option>
                        <option value="Draft">Draft</option>
                    </select>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" rows="10" class="form-control" required></textarea>
                </div>

                <div class="col-12 mb-4">
                    <label class="form-label fw-semibold">Attachment (PDF / DOC / Image)</label>
                    <input type="file" name="attachment" class="form-control">
                    <div class="form-text text-muted">Supported formats: pdf, doc, docx, jpg, jpeg, png (Max size: 5MB)</div>
                </div>

                <div class="col-12">
                    <button type="submit" name="save" class="btn btn-success px-4">
                        <i class="fa fa-paper-plane me-1"></i> Publish Notice
                    </button>
                    <a href="index.php" class="btn btn-light px-4 ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- CKEditor Script -->
<script src="https://cdn.ckeditor.com/4.25.1/standard/ckeditor.js"></script>
<script>
    CKEDITOR.replace('description');
</script>

<?php
require_once('../includes/footer.php');
?>
