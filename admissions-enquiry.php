<?php
// Secure Public Data Intake Processor
require_once __DIR__ . '/config/database.php';

$message = '';
$status = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_enquiry'])) {
    $db = getDBConnection();

    // Sanitize Input Data Vectors
    $studentName = filter_input(INPUT_POST, 'student_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $classRequested = filter_input(INPUT_POST, 'class_requested', FILTER_SANITIZE_SPECIAL_CHARS);
    $parentName = filter_input(INPUT_POST, 'parent_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $parentPhone = filter_input(INPUT_POST, 'parent_phone', FILTER_SANITIZE_SPECIAL_CHARS);
    $parentEmail = filter_input(INPUT_POST, 'parent_email', FILTER_VALIDATE_EMAIL);
    $prevSchool = filter_input(INPUT_POST, 'previous_school', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($studentName) || empty($classRequested) || empty($parentName) || empty($parentPhone)) {
        $message = "Please fill in all mandatory fields highlighted with (*).";
        $status = false;
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO admission_enquiries (student_name, class_requested, parent_name, parent_phone, parent_email, previous_school, status) 
                                  VALUES (:sname, :class, :pname, :pphone, :pemail, :prev_school, 'PENDING')");
            
            $stmt->execute([
                ':sname'        => $studentName,
                ':class'        => $classRequested,
                ':pname'        => $parentName,
                ':pphone'       => $parentPhone,
                ':pemail'       => $parentEmail ? $parentEmail : null,
                ':prev_school'  => $prevSchool
            ]);

            $message = "Thank you! Your admission enquiry has been logged successfully. Our administrative team will contact you shortly.";
        } catch (PDOException $e) {
            $message = "System Exception: Unable to save your application. Please try again later.";
            $status = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Online Admission Application | VIC Academy</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .form-card { border-radius: 12px; border: none; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
        .form-header { background: #1e3d59; color: #fff; border-radius: 12px 12px 0 0; padding: 25px; }
    </style>
</head>
<body>
<div class="container my-5">
    <div class="card form-card max-width-md mx-auto" style="max-width: 700px;">
        <div class="form-header text-center">
            <h4 class="mb-1 font-weight-bold">New Student Registration & Enquiry</h4>
            <p class="mb-0 text-white-50 small">Academic Session 2026-2027</p>
        </div>
        <div class="card-body p-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <h6 class="text-secondary border-bottom pb-2 mb-3">Student Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-7">
                        <label class="form-label small font-weight-bold">Student Full Name *</label>
                        <input type="text" name="student_name" class="form-control form-control-sm" required placeholder="Enter student's name">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small font-weight-bold">Class Seeking Admission *</label>
                        <select name="class_requested" class="form-select form-select-sm" required>
                            <option value="">-- Select Class --</option>
                            <option value="Class 10">Class 10</option>
                            <option value="Class 11">Class 11</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small font-weight-bold">Previous Institution/School Name</label>
                        <input type="text" name="previous_school" class="form-control form-control-sm" placeholder="e.g. Saint Mary Convent School">
                    </div>
                </div>

                <h6 class="text-secondary border-bottom pb-2 mb-3">Guardian Contact Information</h6>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label small font-weight-bold">Father / Mother / Guardian Name *</label>
                        <input type="text" name="parent_name" class="form-control form-control-sm" required placeholder="Enter parent or guardian full name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small font-weight-bold">Primary Mobile Number *</label>
                        <input type="tel" name="parent_phone" class="form-control form-control-sm" required placeholder="e.g. +91XXXXXXXXXX">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small font-weight-bold">Email Address</label>
                        <input type="email" name="parent_email" class="form-control form-control-sm" placeholder="e.g. parent@example.com">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="submit_enquiry" class="btn btn-warning text-dark font-weight-bold w-100">Transmit Digital Application</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
