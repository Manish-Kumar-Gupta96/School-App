<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['assign_transport'])){
    $student_id   = (int)$_POST['student_id'];
    $route_id     = (int)$_POST['route_id'];
    $vehicle_id   = (int)$_POST['vehicle_id'];
    $pickup_point = trim($_POST['pickup_point']);
    $monthly_fee  = (float)$_POST['monthly_fee'];

    if(empty($student_id) || empty($route_id) || empty($vehicle_id)){
        $error = "Student, Route, and Vehicle are required.";
    } else {
        // Check if student is already assigned
        $check = $pdo->prepare("SELECT id FROM student_transport WHERE student_id = ? AND status='Active'");
        $check->execute([$student_id]);

        if ($check->rowCount() > 0) {
            $error = "This student is already assigned to active transport.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO student_transport(student_id, route_id, vehicle_id, pickup_point, monthly_fee, assigned_date, status)
                VALUES(?,?,?,?,?,CURDATE(),'Active')
            ");
            $stmt->execute([$student_id, $route_id, $vehicle_id, $pickup_point, $monthly_fee]);
            $message = "Transport Assigned Successfully!";
        }
    }
}

// Fetch all students
$students = $pdo->query("
    SELECT id, admission_no, first_name, last_name 
    FROM students 
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch active routes
$routes = $pdo->query("
    SELECT id, route_name, monthly_fee 
    FROM transport_routes 
    WHERE status='Active' 
    ORDER BY route_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch active vehicles
$vehicles = $pdo->query("
    SELECT id, vehicle_no, vehicle_type 
    FROM vehicles 
    WHERE status='Active' 
    ORDER BY vehicle_no ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch transport assignments
$assignedList = $pdo->query("
    SELECT st.*, s.admission_no, s.first_name, s.last_name, r.route_name, v.vehicle_no, v.vehicle_type
    FROM student_transport st
    LEFT JOIN students s ON st.student_id = s.id
    LEFT JOIN transport_routes r ON st.route_id = r.id
    LEFT JOIN vehicles v ON st.vehicle_id = v.id
    ORDER BY st.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Assign Student Transport | VIC ERP";
$page_header = "Transport Management";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Assign students to transit routes and vehicles</h5>
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
        <a href="assign-students.php" class="btn btn-primary">
            <i class="fa fa-user-plus me-1"></i> Assign Students
        </a>
        <a href="transport-fees.php" class="btn btn-outline-warning">
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
    <!-- ASSIGN FORM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Assign Transport</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Choose Student...</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transit Route <span class="text-danger">*</span></label>
                        <select name="route_id" id="route_id" class="form-select" onchange="autoFillFee()" required>
                            <option value="">Choose Route...</option>
                            <?php foreach($routes as $rt): ?>
                                <option value="<?= $rt['id'] ?>" data-fee="<?= $rt['monthly_fee'] ?>">
                                    <?= htmlspecialchars($rt['route_name']) ?> (₹ <?= number_format($rt['monthly_fee'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transit Vehicle <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">Choose Vehicle...</option>
                            <?php foreach($vehicles as $vh): ?>
                                <option value="<?= $vh['id'] ?>">
                                    <?= htmlspecialchars($vh['vehicle_no']) ?> (<?= htmlspecialchars($vh['vehicle_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pickup Point Location</label>
                        <input type="text" name="pickup_point" class="form-control" placeholder="e.g. Cross Street Crossing">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Monthly Transport Fee (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monthly_fee" id="monthly_fee" class="form-control" required min="0">
                    </div>

                    <button type="submit" name="assign_transport" class="btn btn-success w-100">
                        <i class="fa fa-user-plus me-1"></i> Assign Transport
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST ASSIGNMENTS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Active Transit Allocations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Student Details</th>
                                <th>Route Assigned</th>
                                <th>Vehicle assigned</th>
                                <th>Pickup Point</th>
                                <th>Monthly Fee</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($assignedList) > 0): ?>
                                <?php foreach($assignedList as $row): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($row['admission_no']) ?></span>
                                        </td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($row['route_name'] ?: '-') ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark mb-0"><?= htmlspecialchars($row['vehicle_no'] ?: '-') ?></div>
                                            <span class="text-muted small"><?= htmlspecialchars($row['vehicle_type']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['pickup_point'] ?: '-') ?></td>
                                        <td class="fw-bold">₹ <?= number_format($row['monthly_fee'], 2) ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-user-slash fs-2 mb-2 d-block"></i>
                                        No student transit allocations found.
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
    function autoFillFee() {
        const routeSelect = document.getElementById('route_id');
        const selectedOption = routeSelect.options[routeSelect.selectedIndex];
        const fee = selectedOption.getAttribute('data-fee');
        if(fee) {
            document.getElementById('monthly_fee').value = fee;
        } else {
            document.getElementById('monthly_fee').value = '';
        }
    }
</script>

<?php
require_once('../includes/footer.php');
?>
