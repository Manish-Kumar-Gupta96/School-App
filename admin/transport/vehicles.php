<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_vehicle'])){
    $vehicle_no       = trim($_POST['vehicle_no']);
    $vehicle_type     = trim($_POST['vehicle_type']);
    $capacity         = (int)$_POST['capacity'];
    $driver_id        = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
    $insurance_expiry = !empty($_POST['insurance_expiry']) ? $_POST['insurance_expiry'] : null;
    $fitness_expiry   = !empty($_POST['fitness_expiry']) ? $_POST['fitness_expiry'] : null;
    $status           = $_POST['status'];

    if(empty($vehicle_no) || $capacity <= 0){
        $error = "Vehicle Number and valid Capacity are required.";
    } else {
        // Check unique vehicle_no
        $check = $pdo->prepare("SELECT id FROM vehicles WHERE vehicle_no = ?");
        $check->execute([$vehicle_no]);

        if ($check->rowCount() > 0) {
            $error = "This Vehicle Number is already registered.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO vehicles(vehicle_no, vehicle_type, capacity, driver_id, insurance_expiry, fitness_expiry, status)
                VALUES(?,?,?,?,?,?,?)
            ");
            $stmt->execute([$vehicle_no, $vehicle_type, $capacity, $driver_id, $insurance_expiry, $fitness_expiry, $status]);
            $message = "Vehicle Registered Successfully!";
        }
    }
}

// Fetch active drivers
$drivers = $pdo->query("SELECT id, driver_name FROM drivers WHERE status='Active' ORDER BY driver_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch vehicles with assigned driver name
$vehicles = $pdo->query("
    SELECT v.*, d.driver_name
    FROM vehicles v
    LEFT JOIN drivers d ON v.driver_id = d.id
    ORDER BY v.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Vehicle Management | VIC ERP";
$page_header = "Transport Management";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Configure school transit buses and vans</h5>
    <div class="d-flex gap-2">
        <a href="routes.php" class="btn btn-outline-primary">
            <i class="fa fa-route me-1"></i> Routes
        </a>
        <a href="vehicles.php" class="btn btn-primary">
            <i class="fa fa-bus me-1"></i> Vehicles
        </a>
        <a href="drivers.php" class="btn btn-outline-primary">
            <i class="fa fa-id-card me-1"></i> Drivers
        </a>
        <a href="assign-students.php" class="btn btn-outline-success">
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
    <!-- ADD VEHICLE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Vehicle</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_no" class="form-control" placeholder="e.g. DL-1PB-1234" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="School Bus">School Bus</option>
                            <option value="School Van">School Van</option>
                            <option value="Mini Bus">Mini Bus</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seating Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="capacity" value="40" min="1" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">No Driver Assigned</option>
                            <?php foreach($drivers as $driver): ?>
                                <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['driver_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Insurance Expiry</label>
                        <input type="date" name="insurance_expiry" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fitness Expiry</label>
                        <input type="date" name="fitness_expiry" class="form-control">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>

                    <button type="submit" name="save_vehicle" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Vehicle
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST VEHICLES -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Vehicle Details</th>
                                <th>Capacity</th>
                                <th>Assigned Driver</th>
                                <th>Insurance Expiry</th>
                                <th>Fitness Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($vehicles) > 0): ?>
                                <?php foreach($vehicles as $v): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($v['vehicle_no']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($v['vehicle_type']) ?></span>
                                        </td>
                                        <td class="fw-semibold"><?= (int)$v['capacity'] ?> Seated</td>
                                        <td>
                                            <?php if($v['driver_name']): ?>
                                                <span class="fw-bold text-primary"><i class="fa fa-user me-1 text-muted"></i> <?= htmlspecialchars($v['driver_name']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted small italic">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $v['insurance_expiry'] ? date('d M Y', strtotime($v['insurance_expiry'])) : '-' ?></td>
                                        <td><?= $v['fitness_expiry'] ? date('d M Y', strtotime($v['fitness_expiry'])) : '-' ?></td>
                                        <td>
                                            <?php if($v['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php elseif($v['status'] == 'Maintenance'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Maintenance</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-bus fs-2 mb-2 d-block"></i>
                                        No transit vehicles registered.
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
