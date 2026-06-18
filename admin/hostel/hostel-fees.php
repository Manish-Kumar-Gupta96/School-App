<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
COLLECT FEE
========================== */
if(isset($_POST['collect_fee'])){
    $allocation_id = (int)$_POST['allocation_id'];
    $student_id    = (int)$_POST['student_id'];
    $month         = $_POST['month'];
    $amount        = (float)$_POST['amount'];
    $payment_mode  = $_POST['payment_mode'];
    $remarks       = trim($_POST['remarks']);

    $receipt_no = "HF" . date('Ym') . rand(1000,9999);

    if(empty($allocation_id) || empty($student_id) || empty($month) || $amount <= 0){
        $error = "Please load a valid hostel resident, billing month, and amount.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostel_fee_payments(hostel_allocation_id, student_id, month, amount, payment_date, payment_mode, receipt_no, remarks, status)
            VALUES(?,?,?,?, CURDATE(), ?, ?, ?, 'Paid')
        ");
        $stmt->execute([
            $allocation_id,
            $student_id,
            $month,
            $amount,
            $payment_mode,
            $receipt_no,
            $remarks
        ]);
        $message = "Hostel Fee Collected Successfully! Receipt: " . $receipt_no;
    }
}

/* ACTIVE HOSTEL RESIDENTS */
$students = $pdo->query("
    SELECT 
        ha.id AS allocation_id,
        ha.hostel_id,
        s.id AS student_id,
        s.admission_no,
        s.first_name,
        s.last_name,
        hr.room_no,
        hrt.monthly_fee
    FROM hostel_allocations ha
    LEFT JOIN students s ON ha.student_id = s.id
    LEFT JOIN hostel_rooms hr ON ha.room_id = hr.id
    LEFT JOIN hostel_room_types hrt ON hr.room_type_id = hrt.id
    WHERE ha.status = 'Active'
    ORDER BY s.first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* PAYMENT HISTORY */
$payments = $pdo->query("
    SELECT hfp.*, s.admission_no, s.first_name, s.last_name
    FROM hostel_fee_payments hfp
    LEFT JOIN students s ON hfp.student_id=s.id
    ORDER BY hfp.id DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Hostel Fee Collection | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and process monthly boarding fee payments</h5>
    <div class="d-flex gap-2">
        <a href="room-types.php" class="btn btn-outline-primary">
            <i class="fa fa-sliders-h me-1"></i> Room Types
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="allocate-room.php" class="btn btn-outline-primary">
            <i class="fa fa-user-tag me-1"></i> Allocations
        </a>
        <a href="hostel-fees.php" class="btn btn-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="visitors.php" class="btn btn-outline-danger">
            <i class="fa fa-users me-1"></i> Visitors
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
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

<div class="row">
    <!-- COLLECT FEE CARD -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Collect Hostel Fee</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Resident <span class="text-danger">*</span></label>
                        <select name="allocation_id" id="allocation_id" class="form-select" onchange="autoFillAllocationDetails()" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['allocation_id'] ?>" data-student-id="<?= $student['student_id'] ?>" data-fee="<?= $student['monthly_fee'] ?>">
                                    <?= htmlspecialchars($student['hostel_id']) ?> - <?= htmlspecialchars($student['admission_no']) ?> - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> (Room <?= htmlspecialchars($student['room_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hidden/Readonly inputs to store student particulars -->
                    <input type="hidden" name="student_id" id="student_id" required>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student Admission ID</label>
                        <input type="text" id="admission_no_display" class="form-control bg-light" readonly placeholder="Resident ID">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Billing Month <span class="text-danger">*</span></label>
                        <input type="month" name="month" class="form-control" required value="<?= date('Y-m') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collect Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" required min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Paid in full">
                    </div>

                    <button type="submit" name="collect_fee" class="btn btn-success w-100">
                        <i class="fa fa-cash-register me-1"></i> Collect Fee
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PAYMENT HISTORY -->
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
                                <th class="ps-4">Receipt</th>
                                <th>Student Name</th>
                                <th>Billing Month</th>
                                <th>Fee Collected</th>
                                <th>Date Paid</th>
                                <th>Mode</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($payments) > 0): ?>
                                <?php foreach($payments as $payment): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars($payment['receipt_no']) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($payment['admission_no']) ?></span>
                                        </td>
                                        <td><?= date('F Y', strtotime($payment['month'] . '-01')) ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($payment['amount'], 2) ?></td>
                                        <td><?= date('d M Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1"><?= htmlspecialchars($payment['payment_mode']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-receipt fs-2 mb-2 d-block"></i>
                                        No hostel fee payments recorded.
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

<script>
    function autoFillAllocationDetails() {
        const select = document.getElementById('allocation_id');
        const selectedOption = select.options[select.selectedIndex];
        
        const studentId = selectedOption.getAttribute('data-student-id');
        const fee = selectedOption.getAttribute('data-fee');
        
        // Extract admission number from option text
        const optionText = selectedOption.text;
        const parts = optionText.split('-');
        const admissionNo = (parts.length > 1) ? parts[1].trim() : 'N/A';

        if (studentId) {
            document.getElementById('student_id').value = studentId;
            document.getElementById('admission_no_display').value = admissionNo;
            document.getElementById('amount').value = fee;
        } else {
            document.getElementById('student_id').value = '';
            document.getElementById('admission_no_display').value = '';
            document.getElementById('amount').value = '';
        }
    }
</script>

<?php
require_once('../includes/footer.php');
?>
