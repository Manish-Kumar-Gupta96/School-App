<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['teacher', 'admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Attack Blocked.");
    }

    $className = filter_input(INPUT_POST, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $assignmentType = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_SPECIAL_CHARS);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
    
    $uploadedFile = $_FILES['resource_file'] ?? null;
    $finalFilePath = null;

    if (empty($className) || empty($assignmentType) || empty($title)) {
        $message = "All text parameters fields are required.";
        $status = false;
    } else {
        try {
            // Process attached document binary payload safely if provided
            if ($uploadedFile && $uploadedFile['error'] === UPLOAD_ERR_OK) {
                $uploadDir = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'assignments' . DIRECTORY_SEPARATOR;
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = basename($uploadedFile['name']);
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

                if (!in_array($fileExt, $allowedExtensions)) {
                    throw new Exception("File format not allowed. Only docs/images are permitted.");
                }

                // Generates an unguessable unique crypto identifier for security reasons
                $safeName = bin2hex(random_bytes(10)) . '.' . $fileExt;
                $targetFile = $uploadDir . $safeName;

                if (move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
                    $finalFilePath = 'uploads/assignments/' . $safeName;
                } else {
                    throw new Exception("Filesystem operations fault during safe moving.");
                }
            }

            // Save records inside schema parameters
            $stmt = $db->prepare("INSERT INTO school_assignments (class_name, type, title, description, file_path, uploaded_by, created_at) 
                                  VALUES (:class, :type, :title, :descr, :path, :uid, NOW())");
            
            $stmt->execute([
                ':class' => $className,
                ':type'  => $assignmentType,
                ':title' => $title,
                ':descr' => $description,
                ':path'  => $finalFilePath,
                ':uid'   => $_SESSION['user_id']
            ]);

            AuditLogger::log('RESOURCE_UPLOADED', "Uploaded {$assignmentType} for {$className}: {$title}");
            $message = "Assignment / Syllabus item indexed flawlessly.";
        } catch (Exception $e) {
            $message = "Operational Error: " . $e->getMessage();
            $status = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Resource Dispatch Console</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm border-0" style="max-width: 650px; margin: auto;">
        <div class="card-header bg-dark text-white font-weight-bold">Upload Academic Resource / Assignment</div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <?php Csrf::injectInput(); ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Target Class Allocation</label>
                        <select name="class_name" class="form-select form-select-sm" required>
                            <option value="Class 10">Class 10</option>
                            <option value="Class 11">Class 11</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label font-weight-bold">Classification Type</label>
                        <select name="type" class="form-select form-select-sm" required>
                            <option value="HOMEWORK">Daily Homework</option>
                            <option value="SYLLABUS">Academic Syllabus Structure</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-weight-bold">Resource Title Header</label>
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Chapter 4 Integration Exercises" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-weight-bold">Instructions Summary / Outline Text</label>
                        <textarea name="description" class="form-control form-control-sm" rows="4" placeholder="Type instructions details or homework guidelines description..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label font-weight-bold">Attach Resource Document Attachment</label>
                        <input type="file" name="resource_file" class="form-control form-control-sm">
                        <small class="text-muted text-wrap">Permitted: pdf, docx, png, jpg up to 5MB.</small>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" name="upload_resource" class="btn btn-primary btn-sm px-4">Broadcast Resource Node</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
