<?php 
require_once('config/database.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_name  = sanitize($_POST['student_name'] ?? '');
    $father_name   = sanitize($_POST['father_name'] ?? '');
    $mother_name   = sanitize($_POST['mother_name'] ?? '');
    $dob           = sanitize($_POST['dob'] ?? '');
    $gender        = sanitize($_POST['gender'] ?? '');
    $class_applied = sanitize($_POST['class_applied'] ?? '');
    $phone         = sanitize($_POST['phone'] ?? '');
    $email         = sanitize($_POST['email'] ?? '');
    $address       = sanitize($_POST['address'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = "CSRF token validation failed. Please try again.";
    } elseif (!empty($student_name) && !empty($father_name) && !empty($phone) && !empty($email) && !empty($class_applied)) {
        try {
            $photo = '';
            $document = '';
            
            $upload_dir = "uploads/admissions/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Handle student photo upload with strict validation
            if (!empty($_FILES['photo']['name'])) {
                $photo_val = validate_uploaded_file($_FILES['photo'], ['jpg', 'jpeg', 'png', 'webp'], 2097152); // max 2MB
                if ($photo_val !== true) {
                    throw new Exception("Photo Validation Error: " . $photo_val);
                }
                $photo_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = time() . "_photo_" . rand(1000, 9999) . "." . $photo_ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo);
            }

            // Handle birth certificate or document upload with strict validation
            if (!empty($_FILES['document']['name'])) {
                $doc_val = validate_uploaded_file($_FILES['document'], ['pdf', 'jpg', 'jpeg', 'png', 'docx'], 2097152); // max 2MB
                if ($doc_val !== true) {
                    throw new Exception("Document Validation Error: " . $doc_val);
                }
                $doc_ext = pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION);
                $document = time() . "_doc_" . rand(1000, 9999) . "." . $doc_ext;
                move_uploaded_file($_FILES['document']['tmp_name'], $upload_dir . $document);
            }

            // Generate Application Number: APP-YYYY-XXXX
            $application_no = 'APP-' . date('Y') . '-' . rand(1000, 9999);

            $stmt = $pdo->prepare("
                INSERT INTO admissions (
                    application_no, student_name, father_name, mother_name,
                    dob, gender, class_applied, phone,
                    email, address, photo, document, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $stmt->execute([
                $application_no, $student_name, $father_name, $mother_name,
                $dob, $gender, $class_applied, $phone,
                $email, $address, $photo, $document
            ]);

            // Create lead in CRM Leads
            $stmt_crm = $pdo->prepare("
                INSERT INTO crm_leads (
                    school_id, name, email, phone, class_applied, message, source, status
                ) VALUES (?, ?, ?, ?, ?, ?, 'Admission Portal', 'New Lead')
            ");
            $message_content = "Online Admission Application submitted. Father: {$father_name}, Mother: {$mother_name}. App No: {$application_no}.";
            $stmt_crm->execute([
                CURRENT_SCHOOL_ID,
                $student_name,
                $email,
                $phone,
                $class_applied,
                $message_content
            ]);

            $message = "Your admission application has been submitted successfully! Application Number: " . $application_no;
        } catch (Exception $e) {
            $error = "Failed to submit application: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

include('includes/header.php'); 
include('includes/navbar.php'); 
?>

<!-- ==========================
PAGE BANNER
========================= -->
<section class="page-banner">
    <div class="container">
        <h1>Admissions Open 2026-27</h1>
        <p>
            Begin Your Child's Journey Towards Excellence
        </p>
    </div>
</section>

<!-- ==========================
ADMISSION OVERVIEW
========================== -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <img src="assets/images/admission.jpg"
                    class="img-fluid rounded-4 shadow"
                    alt="Admissions">
            </div>
            <div class="col-lg-6">
                <h2>Admissions at VIC School</h2>
                <p>
                    We welcome students who are eager to learn,
                    grow and achieve excellence in academics,
                    sports and co-curricular activities.
                </p>
                <p>
                    Our admission process is simple,
                    transparent and student-friendly.
                </p>
                <a href="#admission-form"
                    class="primary-btn">
                    Apply Online
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
ADMISSION PROCESS
========================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Admission Process</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="feature-card">
                    <i class="fas fa-file-alt"></i>
                    <h4>Step 1</h4>
                    <p>Fill Online Application Form</p>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="feature-card">
                    <i class="fas fa-upload"></i>
                    <h4>Step 2</h4>
                    <p>Submit Required Documents</p>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="feature-card">
                    <i class="fas fa-user-check"></i>
                    <h4>Step 3</h4>
                    <p>Interaction / Assessment</p>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="feature-card">
                    <i class="fas fa-check-circle"></i>
                    <h4>Step 4</h4>
                    <p>Admission Confirmation</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
ELIGIBILITY
========================== -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Eligibility Criteria</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Age Criteria</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nursery</td>
                        <td>3+ Years</td>
                    </tr>
                    <tr>
                        <td>LKG</td>
                        <td>4+ Years</td>
                    </tr>
                    <tr>
                        <td>UKG</td>
                        <td>5+ Years</td>
                    </tr>
                    <tr>
                        <td>Class I</td>
                        <td>6+ Years</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ==========================
REQUIRED DOCUMENTS
========================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Required Documents</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-id-card"></i>
                    <h4>Birth Certificate</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-image"></i>
                    <h4>Passport Size Photos</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-school"></i>
                    <h4>Previous School Records</h4>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
FEE STRUCTURE
========================== -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Fee Structure</h2>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Annual Fee</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nursery - UKG</td>
                        <td>₹25,000</td>
                    </tr>
                    <tr>
                        <td>Class I - V</td>
                        <td>₹35,000</td>
                    </tr>
                    <tr>
                        <td>Class VI - VIII</td>
                        <td>₹45,000</td>
                    </tr>
                    <tr>
                        <td>Class IX - XII</td>
                        <td>₹55,000</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ==========================
ONLINE APPLICATION FORM
========================== -->
<section id="admission-form" class="py-5 bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Online Admission Form</h2>
        </div>
        <?php if (!empty($message)): ?>
            <div class="alert alert-success mb-4">
                <i class="fa fa-check-circle me-1"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4">
                <i class="fa fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
            <div class="row">
                <!-- Student Name -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Student Name *</label>
                    <input type="text"
                        name="student_name"
                        class="form-control"
                        placeholder="Student Name"
                        required>
                </div>
                <!-- Date of Birth -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Date Of Birth *</label>
                    <input type="date"
                        name="dob"
                        class="form-control"
                        required>
                </div>
                <!-- Gender -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Gender *</label>
                    <select name="gender" class="form-select" required>
                        <option value="">Select Gender</option>
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                </div>
                <!-- Class Applied -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Class Applied *</label>
                    <select class="form-select" name="class_applied" required>
                        <option value="">Select Class</option>
                        <option>Nursery</option>
                        <option>LKG</option>
                        <option>UKG</option>
                        <option>Class I</option>
                        <option>Class II</option>
                        <option>Class III</option>
                        <option>Class IV</option>
                        <option>Class V</option>
                        <option>Class VI</option>
                        <option>Class VII</option>
                        <option>Class VIII</option>
                        <option>Class IX</option>
                        <option>Class X</option>
                        <option>Class XI</option>
                        <option>Class XII</option>
                    </select>
                </div>
                <!-- Father's Name -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Father's Name *</label>
                    <input type="text"
                        name="father_name"
                        class="form-control"
                        placeholder="Father's Name"
                        required>
                </div>
                <!-- Mother's Name -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Mother's Name *</label>
                    <input type="text"
                        name="mother_name"
                        class="form-control"
                        placeholder="Mother's Name"
                        required>
                </div>
                <!-- Phone -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Mobile Number *</label>
                    <input type="tel"
                        name="phone"
                        class="form-control"
                        placeholder="10-digit Mobile Number"
                        required>
                </div>
                <!-- Email -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Email Address *</label>
                    <input type="email"
                        name="email"
                        class="form-control"
                        placeholder="Email Address"
                        required>
                </div>
                <!-- Student Photo -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Student Photo</label>
                    <input type="file"
                        name="photo"
                        class="form-control"
                        accept="image/*">
                </div>
                <!-- Birth Certificate Document -->
                <div class="col-lg-6 mb-3">
                    <label class="form-label text-muted small fw-semibold">Birth Certificate / Document (PDF, PNG, JPG)</label>
                    <input type="file"
                        name="document"
                        class="form-control"
                        accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <!-- Address -->
                <div class="col-12 mb-3">
                    <label class="form-label text-muted small fw-semibold">Permanent Address *</label>
                    <textarea
                        class="form-control"
                        rows="4"
                        name="address"
                        placeholder="Residential Address"
                        required></textarea>
                </div>
                <div class="col-12">
                    <button
                        type="submit"
                        class="primary-btn border-0">
                        Submit Application
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- ==========================
CTA
========================== -->
<section class="py-5">
    <div class="container">
        <div class="admission-banner text-center">
            <h2>
                Seats Are Limited
            </h2>
            <p class="mb-4">
                Apply today and secure your child's future.
            </p>
            <a href="#admission-form"
                class="primary-btn">
                Apply Now
            </a>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
