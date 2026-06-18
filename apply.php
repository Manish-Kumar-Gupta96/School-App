<?php
require_once('config/database.php');

$message = '';
$error = '';

$job_id = $_GET['job_id'] ?? 0;

// Verify job exists
$job = [];
if ($job_id) {
    $stmt = $pdo->prepare("SELECT * FROM job_openings WHERE id = ? AND status='OPEN'");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$job) {
    header("Location: career.php");
    exit;
}

if(isset($_POST['apply'])){
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($_FILES['resume']['name'])) {
        $error = "All fields are required, including your resume.";
    } else {
        $file_name = $_FILES['resume']['name'];
        $tmp       = $_FILES['resume']['tmp_name'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
            $error = "Invalid file type. Only PDF, DOC, and DOCX files are allowed.";
        } else {
            $upload_dir = "uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $new_filename = "resume_" . time() . "_" . preg_replace("/[^a-zA-Z0-9\._-]/", "_", $file_name);
            $path = $upload_dir . $new_filename;

            if (move_uploaded_file($tmp, $path)) {
                $stmt = $pdo->prepare("
                    INSERT INTO job_applications (job_id, name, email, phone, resume, status)
                    VALUES (?,?,?,?,?,'NEW')
                ");
                $stmt->execute([
                    $job_id,
                    $name,
                    $email,
                    $phone,
                    $path
                ]);
                $message = "Application submitted successfully! Our HR team will review your profile shortly.";
            } else {
                $error = "Failed to upload resume file.";
            }
        }
    }
}

include('includes/header.php');
include('includes/navbar.php');
?>

<section class="page-banner">
    <div class="container">
        <h1>Job Application</h1>
        <p>Apply for: <?= htmlspecialchars($job['title']) ?></p>
    </div>
</section>

<section class="py-5 text-start">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card shadow border-0 p-4" style="border-radius: 15px;">
                    <h4 class="fw-bold mb-1 text-dark">Submit Application</h4>
                    <p class="text-muted mb-4">Department: <?= htmlspecialchars($job['department']) ?></p>

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle me-1"></i> <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-muted small">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-muted small">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-muted small">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Phone" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold text-muted small">Upload Resume (PDF, DOC, DOCX)</label>
                            <input type="file" name="resume" class="form-control" required>
                        </div>
                        <div class="col-md-12 mt-4">
                            <button type="submit" name="apply" class="btn btn-primary w-100 py-2 fw-semibold">Submit Application</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
