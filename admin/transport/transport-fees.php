<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
SAVE PAYMENT
========================== */
if(isset($_POST['pay_fee'])){
    $student_id = (int)$_POST['student_id'];
    $route_id   = (int)$_POST['route_id'];
    $month      = $_POST['month'];
    $amount     = (float)$_POST['amount'];
    $mode       = $_POST['payment_mode'];

    $receipt_no = "TRN" . date('Ymd') . rand(1000,9999);

    if(empty($student_id) || empty($route_id) || empty($month) || $amount <= 0){
        $error = "Please load a valid student, route details, month, and amount.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transport_fee_payments(student_id, route_id, month, amount, payment_date, payment_mode, receipt_no, status)
            VALUES(?,?,?,?, CURDATE(), ?, ?, 'Paid')
        ");
        $stmt->execute([
            $student_id,
            $route_id,
            $month,
            $amount,
            $mode,
            $receipt_no
        ]);
        $message = "Transport Fee Paid Successfully! Receipt: " . $receipt_no;
    }
}

/* ==========================
LOAD ASSIGNED STUDENTS (Only active transit)
========================== */
$students = $pdo->query("
    SELECT 
        st.student_id,
        st.route_id,
        st.monthly_fee,
        s.admission_no,
        s.first_name,
        s.last_name,
        r.route_name
    FROM student_transport st
    LEFT JOIN students s ON st.student_id=s.id
    LEFT JOIN transport_routes r ON st.route_id=r.id
    WHERE st.status='Active'
    ORDER BY s.first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
PAYMENT HISTORY
========================== */
$payments = $pdo->query("
    SELECT p.*, s.admission_no, s.first_name, s.last_name, r.route_name
    FROM transport_fee_payments p
    LEFT JOIN students s ON p.student_id=s.id
    LEFT JOIN transport_routes r ON p.route_id=r.id
    ORDER BY p.id DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Transport Fee Collection | VIC ERP";
$page_header = "Transport Management";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record student transport fee payments</h5>
    <div class="d-flex gap-2">
        <a href="routes.php" class="btn btn-outline-primary">
            <i class="fa fa-route me-1"></i> Routes
        </a>
        <a href="vehicles.php" class="btn btn-outline-primary">
            <i class="fa fa-bus me-1"></i> Vehicles
        </a>
        <a href="drivers.php" class="btn btn-outline-primary">
            <i class="fa fa-id-card me-1"></i> Drivers
        </a>
        <a href="assign-students.php" class="btn btn-outline-success">
            <i class="fa fa-user-plus me-1"></i> Assign Students
        </a>
        <a href="transport-fees.php" class="btn btn-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
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
                <h5 class="fw-bold mb-0 text-dark">Collect Fee Form</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Student Borrower <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-select" onchange="autoFillRouteDetails()" required>
                            <option value="">Select Borrower</option>
                            <?php foreach($students as $student): ?>
                                <option value="<?= $student['student_id'] ?>" data-route-id="<?= $student['route_id'] ?>" data-fee="<?= $student['monthly_fee'] ?>">
                                    <?= htmlspecialchars($student['admission_no']) ?> - <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?> (<?= htmlspecialchars($student['route_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hidden Route ID and Readonly Display -->
                    <input type="hidden" name="route_id" id="route_id" required>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assigned Route Path</label>
                        <input type="text" id="route_display" class="form-control bg-light" readonly placeholder="Selected student's route">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Billing Month <span class="text-danger">*</span></label>
                        <input type="month" name="month" class="form-control" required value="<?= date('Y-m') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Collect Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" required min="0">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                            <option value="Card">Card</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <button type="submit" name="pay_fee" class="btn btn-success w-100">
                        <i class="fa fa-cash-register me-1"></i> Collect Fee
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST HISTORY -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Lending Fee Collection History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Receipt</th>
                                <th>Student Name</th>
                                <th>Route Assigned</th>
                                <th>Billing Month</th>
                                <th>Fee Collected</th>
                                <th>Date Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($payments) > 0): ?>
                                <?php foreach($payments as $p): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars($p['receipt_no']) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($p['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($p['route_name'] ?: '-') ?></td>
                                        <td><?= date('F Y', strtotime($p['month'] . '-01')) ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($p['amount'], 2) ?></td>
                                        <td><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                                        <td><span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Paid</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-receipt fs-2 mb-2 d-block"></i>
                                        No fee transactions loaded.
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
    function autoFillRouteDetails() {
        const select = document.getElementById('student_id');
        const selectedOption = select.options[select.selectedIndex];
        
        const routeId = selectedOption.getAttribute('data-route-id');
        const fee = selectedOption.getAttribute('data-fee');
        
        // Extract route name from text
        const optionText = selectedOption.text;
        const startIdx = optionText.lastIndexOf('(');
        const endIdx = optionText.lastIndexOf(')');
        const routeName = (startIdx !== -1 && endIdx !== -1) ? optionText.substring(startIdx + 1, endIdx) : 'Assigned Route';

        if (routeId) {
            document.getElementById('route_id').value = routeId;
            document.getElementById('route_display').value = routeName;
            document.getElementById('amount').value = fee;
        } else {
            document.getElementById('route_id').value = '';
            document.getElementById('route_display').value = '';
            document.getElementById('amount').value = '';
        }
    }
</script>

<?php
require_once('../includes/footer.php');
?>
