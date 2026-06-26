<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

if ($_SESSION['role'] !== 'guard' && $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

if (isset($_POST['register_visitor'])) {
    $v_name = trim($_POST['visitor_name']);
    $mobile = trim($_POST['mobile_no']);
    $email = trim($_POST['email'] ?? '');
    $type = $_POST['visitor_type'];
    $id_type = trim($_POST['id_proof_type']);
    $id_no = trim($_POST['id_proof_number']);
    
    $purpose = trim($_POST['purpose']);
    $meet_type = $_POST['person_to_meet_type'];
    $meet_id = (int)$_POST['person_to_meet_id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insert Visitor Master or find existing by mobile
        $stmt_check = $pdo->prepare("SELECT id FROM visitors WHERE mobile_no = ? AND school_id = ?");
        $stmt_check->execute([$mobile, $schoolId]);
        $visitor_id = $stmt_check->fetchColumn();
        
        if (!$visitor_id) {
            $stmt_ins = $pdo->prepare("INSERT INTO visitors (school_id, visitor_name, mobile_no, email, visitor_type, id_proof_type, id_proof_number) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_ins->execute([$schoolId, $v_name, $mobile, $email, $type, $id_type, $id_no]);
            $visitor_id = $pdo->lastInsertId();
        }
        
        // 2. Insert Visitor Entry
        $stmt_entry = $pdo->prepare("INSERT INTO visitor_entries (school_id, visitor_id, purpose, person_to_meet_type, person_to_meet_id, entry_time, status) VALUES (?, ?, ?, ?, ?, NOW(), 'pending')");
        $stmt_entry->execute([$schoolId, $visitor_id, $purpose, $meet_type, $meet_id]);
        
        $pdo->commit();
        $message = "Visitor registered successfully. Waiting for approval.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error registering visitor: " . $e->getMessage();
    }
}

$root_path = "../";
$page_title = "Visitor Entry | Security Portal";
$page_header = "Security Management";
$active_menu = "visitor_entry";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Register New Visitor</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">Visitor Registration Form</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Personal Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name</label>
                            <input type="text" name="visitor_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Number</label>
                            <input type="text" name="mobile_no" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Visitor Type</label>
                            <select name="visitor_type" class="form-select" required>
                                <option value="Parent">Parent</option>
                                <option value="Vendor">Vendor</option>
                                <option value="Guest">Guest</option>
                                <option value="Delivery Person">Delivery Person</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ID Proof Type</label>
                            <select name="id_proof_type" class="form-select">
                                <option value="Aadhaar">Aadhaar Card</option>
                                <option value="Driving License">Driving License</option>
                                <option value="Voter ID">Voter ID</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">ID Proof Number</label>
                            <input type="text" name="id_proof_number" class="form-control">
                        </div>
                    </div>

                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Visit Details</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label fw-bold">Purpose of Visit</label>
                            <textarea name="purpose" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">To Meet (Type)</label>
                            <select name="person_to_meet_type" class="form-select" required>
                                <option value="teacher">Teacher</option>
                                <option value="admin">Admin / Principal</option>
                                <option value="student">Student</option>
                                <option value="staff">Other Staff</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Person ID</label>
                            <input type="number" name="person_to_meet_id" class="form-control" required placeholder="User ID">
                        </div>
                    </div>
                    
                    <div class="text-end">
                        <button type="submit" name="register_visitor" class="btn btn-primary px-5 py-2 fw-bold">Register & Request Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
