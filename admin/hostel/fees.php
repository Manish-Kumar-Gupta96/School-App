<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Fee Demand
if (isset($_POST['demand_fee'])) {
    $student_id = (int)$_POST['student_id'];
    $amount     = (float)$_POST['amount'];
    $due_date   = $_POST['due_date'];

    if (empty($student_id) || $amount <= 0 || empty($due_date)) {
        $error = "Student, Amount, and Due Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostel_fees (school_id, student_id, amount, due_date, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $student_id,
            $amount,
            $due_date
        ]);
        $message = "Hostel fee invoice generated successfully!";
    }
}

// Mark Paid
if (isset($_GET['mark_paid'])) {
    $fee_id = (int)$_GET['mark_paid'];

    $stmt = $pdo->prepare("
        UPDATE hostel_fees 
        SET status = 'paid' 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$fee_id, CURRENT_SCHOOL_ID]);
    $message = "Invoice marked as PAID!";
    header("Location: fees.php");
    exit();
}

// Fetch Boarders (students with assigned beds)
$stmt_stud = $pdo->prepare("
    SELECT DISTINCT s.id, s.first_name, s.last_name, s.admission_no 
    FROM students s
    JOIN hostel_beds hb ON s.id = hb.student_id
    WHERE s.school_id = ?
    ORDER BY s.first_name ASC
");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$boarders = $stmt_stud->fetchAll(PDO::FETCH_ASSOC);

// Fetch Demands
$stmt_demands = $pdo->prepare("
    SELECT hf.*, s.first_name, s.last_name, s.admission_no, hr.room_no 
    FROM hostel_fees hf
    JOIN students s ON hf.student_id = s.id
    LEFT JOIN hostel_beds hb ON s.id = hb.student_id
    LEFT JOIN hostel_rooms hr ON hb.room_id = hr.id
    WHERE hf.school_id = ?
    ORDER BY hf.due_date DESC
");
$stmt_demands->execute([CURRENT_SCHOOL_ID]);
$demands = $stmt_demands->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Hostel Fees | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and process monthly boarding fee payments</h5>
    <div class="d-flex gap-2">
        <a href="hostels.php" class="btn btn-outline-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="beds.php" class="btn btn-outline-primary">
            <i class="fa fa-bed me-1"></i> Beds
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="visitors.php" class="btn btn-outline-danger">
            <i class="fa fa-users me-1"></i> Visitors
        </a>
        <a href="mess-menu.php" class="btn btn-outline-success">
            <i class="fa fa-utensils me-1"></i> Mess Menu
        </a>
        <a href="fees.php" class="btn btn-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
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
    <!-- Invoice Demand Generation -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Collect Hostel Fee</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Resident <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student...</option>
                            <?php foreach($boarders as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collect Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 5000" min="0" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <button type="submit" name="demand_fee" class="btn btn-success w-100">
                        <i class="fa fa-cash-register me-1"></i> Generate Invoice
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Ledger & Demands Register -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Billing Collection History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student Name</th>
                                <th>Room No</th>
                                <th>Fee Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($demands) > 0): ?>
                                <?php foreach($demands as $d): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($d['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-semibold text-primary">Room <?= htmlspecialchars($d['room_no'] ?: 'N/A') ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($d['amount'], 2) ?></td>
                                        <td><span class="small text-muted"><i class="fa fa-calendar-days text-muted me-1"></i> <?= date('d M Y', strtotime($d['due_date'])) ?></span></td>
                                        <td>
                                            <?php if ($d['status'] === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($d['status'] === 'pending'): ?>
                                                <a href="?mark_paid=<?= $d['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Confirm payment collection for this invoice?')">
                                                    <i class="fa fa-check me-1"></i> Collect
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-file-invoice-dollar fs-2 mb-2 d-block"></i>
                                        No fee invoices registered.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
