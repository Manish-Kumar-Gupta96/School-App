<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Upload Material - Download Center | Admin Control";
$active_menu = "downloads";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['upload'])){
    $title        = trim($_POST['title']);
    $category     = $_POST['category'];
    $sub_category = trim($_POST['sub_category']);
    $class        = trim($_POST['class']);

    if(empty($title) || empty($_FILES['file']['name'])){
        $error = "Title and File are required.";
    } else {
        $file_name = $_FILES['file']['name'];
        $tmp       = $_FILES['file']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'])){
            $error = "Invalid file type. Only PDF, DOC, DOCX, JPG, and PNG are allowed.";
        } else {
            $upload_dir = "../../../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = "dl_" . time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name);
            $path = "uploads/" . $new_filename;

            if (move_uploaded_file($tmp, $upload_dir . $new_filename)) {
                $stmt = $pdo->prepare("
                    INSERT INTO downloads (title, category, sub_category, file_path, class)
                    VALUES (?,?,?,?,?)
                ");
                $stmt->execute([
                    $title,
                    $category,
                    $sub_category,
                    $path,
                    $class
                ]);
                $message = "File uploaded to Download Center successfully!";
            } else {
                $error = "Failed to upload file.";
            }
        }
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📥 Upload Download Material</h2>
            <p class="text-muted mb-0">Publish general syllabus worksheets, public forms, or holiday circulars.</p>
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
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-file-upload me-2 text-primary"></i>Upload to Repository</h5>
        
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">Material Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Academic Calendar 2026-27" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-semibold">Category</label>
                <select name="category" class="form-select" required>
                    <option>PDF</option>
                    <option>SYLLABUS</option>
                    <option>FORM</option>
                    <option>HOLIDAY</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Sub Category / Tag</label>
                <input type="text" name="sub_category" class="form-control" placeholder="e.g. General, Circular" required>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Target Class (Optional - Leave blank for all)</label>
                <input type="text" name="class" class="form-control" placeholder="e.g. 10-A">
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Select File (PDF, DOC, DOCX, JPG, PNG)</label>
                <input type="file" name="file" class="form-control" required>
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="upload" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-upload me-1"></i> Upload File
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
