<?php
require_once('../config/database.php');
$active_menu = "material";
$page_title = "Upload Study Material | Teacher Portal";
require_once('includes/header.php');

$message = '';
$error = '';

if(isset($_POST['upload'])){
    $title   = trim($_POST['title']);
    $subject = trim($_POST['subject']);
    $class   = trim($_POST['class']);

    if(empty($title) || empty($subject) || empty($class) || !isset($_FILES['file'])){
        $error = "All fields are required.";
    } else {
        $file_name = $_FILES['file']['name'];
        $tmp       = $_FILES['file']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if(!in_array($ext, ['pdf', 'doc', 'docx', 'jpg', 'png', 'jpeg'])){
            $error = "Invalid file type. Only PDF, DOC, DOCX, JPG, JPEG, and PNG are allowed.";
        } else {
            // Ensure uploads directory exists
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename = time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name);
            $path = "uploads/" . $new_filename;

            if (move_uploaded_file($tmp, $upload_dir . $new_filename)) {
                $stmt = $pdo->prepare("
                    INSERT INTO study_materials (title, subject, class, file_path, uploaded_by, upload_date)
                    VALUES (?,?,?,?,?,NOW())
                ");
                $stmt->execute([
                    $title,
                    $subject,
                    $class,
                    $path,
                    $_SESSION['teacher_id']
                ]);
                $message = "Study material uploaded successfully!";
            } else {
                $error = "Failed to upload file.";
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📤 Upload Study Material</h2>
        <p class="text-muted mb-0">Share educational files, textbooks, or documents directly with students.</p>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4 text-start" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 p-4 mb-4 text-start" style="border-radius: 12px; max-width: 700px;">
    <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-file-upload me-2 text-success"></i>Upload Document</h5>
    
    <form method="POST" enctype="multipart/form-data" class="row g-3">
        <div class="col-md-12">
            <label class="form-label fw-semibold">Material Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1: Introduction to Calculus" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Subject</label>
            <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics" required>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Target Class</label>
            <input type="text" name="class" class="form-control" placeholder="e.g. 10-A" required>
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">Select File (PDF, DOC, DOCX, JPG, PNG)</label>
            <input type="file" name="file" class="form-control" required>
        </div>

        <div class="col-md-12 mt-4 text-end">
            <button type="submit" name="upload" class="btn btn-success px-5 py-2 fw-semibold">
                <i class="fa fa-upload me-1"></i> Upload Material
            </button>
        </div>
    </form>
</div>

<?php require_once('includes/footer.php'); ?>
