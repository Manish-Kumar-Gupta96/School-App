<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->execute([$id]);
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$notice){
    die("Notice Not Found");
}

$error = '';

if(isset($_POST['update'])){
    $title        = trim($_POST['title']);
    $notice_date  = $_POST['notice_date'];
    $expiry_date  = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $notice_for   = $_POST['notice_for'];
    $description  = $_POST['description'];
    $status       = $_POST['status'];

    $attachment = $notice['attachment'];

    // If request to remove current attachment
    if(isset($_POST['remove_attachment']) && $_POST['remove_attachment'] == '1'){
        if (!empty($notice['attachment']) && file_exists("../../uploads/notices/" . $notice['attachment'])) {
            unlink("../../uploads/notices/" . $notice['attachment']);
        }
        $attachment = '';
    }

    if(!empty($_FILES['attachment']['name'])){
        $extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array(strtolower($extension), $allowed)) {
            $error = "Invalid file type. Only PDF, DOC, DOCX, and images are allowed.";
        } else {
            // Delete old attachment if exists
            if (!empty($notice['attachment']) && file_exists("../../uploads/notices/" . $notice['attachment'])) {
                unlink("../../uploads/notices/" . $notice['attachment']);
            }
            
            $attachment = time() . "_" . rand(1000,9999) . "." . $extension;
            if(!move_uploaded_file($_FILES['attachment']['tmp_name'], "../../uploads/notices/" . $attachment)) {
                $error = "Failed to upload file.";
            }
        }
    }

    if (empty($error)) {
        $stmt_update = $pdo->prepare("
            UPDATE notices
            SET title = ?, notice_date = ?, expiry_date = ?, notice_for = ?, description = ?, attachment = ?, status = ?
            WHERE id = ?
        ");

        $stmt_update->execute([
            $title,
            $notice_date,
            $expiry_date,
            $notice_for,
            $description,
            $attachment,
            $status,
            $id
        ]);

        header("Location: index.php?saved=1");
        exit();
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Edit Notice | VIC ERP";
$page_header = "Edit Notice";
$active_menu = "notices";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Modify notice or announcement details</h5>
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
                    <input type="text" name="title" class="form-control" placeholder="Enter notice title" required value="<?= htmlspecialchars($notice['title']) ?>">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Notice Date <span class="text-danger">*</span></label>
                    <input type="date" name="notice_date" class="form-control" required value="<?= htmlspecialchars($notice['notice_date']) ?>">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?= htmlspecialchars($notice['expiry_date'] ?: '') ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Audience (Notice For) <span class="text-danger">*</span></label>
                    <select name="notice_for" class="form-select" required>
                        <option value="All" <?= ($notice['notice_for'] == 'All') ? 'selected' : '' ?>>All Roles</option>
                        <option value="Students" <?= ($notice['notice_for'] == 'Students') ? 'selected' : '' ?>>Students Only</option>
                        <option value="Teachers" <?= ($notice['notice_for'] == 'Teachers') ? 'selected' : '' ?>>Teachers Only</option>
                        <option value="Parents" <?= ($notice['notice_for'] == 'Parents') ? 'selected' : '' ?>>Parents Only</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="Published" <?= ($notice['status'] == 'Published') ? 'selected' : '' ?>>Published</option>
                        <option value="Draft" <?= ($notice['status'] == 'Draft') ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" rows="10" class="form-control" required><?= htmlspecialchars($notice['description']) ?></textarea>
                </div>

                <div class="col-12 mb-4">
                    <label class="form-label fw-semibold">Attachment (PDF / DOC / Image)</label>
                    <?php if(!empty($notice['attachment'])): ?>
                        <div class="d-flex align-items-center mb-2 p-2 bg-light border rounded">
                            <i class="fa fa-file-alt text-muted fs-4 me-2"></i>
                            <span class="text-muted small me-3"><?= htmlspecialchars($notice['attachment']) ?></span>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="checkbox" name="remove_attachment" id="remove_attachment" value="1">
                                <label class="form-check-label text-danger small fw-semibold" for="remove_attachment">Remove Current Attachment</label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="attachment" class="form-control">
                    <div class="form-text text-muted">Leave empty to keep existing, or upload a new one. Supported formats: pdf, doc, docx, jpg, jpeg, png (Max: 5MB)</div>
                </div>

                <div class="col-12">
                    <button type="submit" name="update" class="btn btn-warning px-4">
                        <i class="fa fa-save me-1"></i> Update Notice
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
