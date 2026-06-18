<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_route'])){
    $route_name  = trim($_POST['route_name']);
    $start_point = trim($_POST['start_point']);
    $end_point   = trim($_POST['end_point']);
    $distance    = !empty($_POST['distance_km']) ? (float)$_POST['distance_km'] : 0.00;
    $fee         = !empty($_POST['monthly_fee']) ? (float)$_POST['monthly_fee'] : 0.00;
    $status      = $_POST['status'];

    if(empty($route_name)){
        $error = "Route Name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transport_routes(route_name, start_point, end_point, distance_km, monthly_fee, status)
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->execute([$route_name, $start_point, $end_point, $distance, $fee, $status]);
        $message = "Transport Route Added Successfully!";
    }
}

$routes = $pdo->query("
    SELECT *
    FROM transport_routes
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Transport Routes | VIC ERP";
$page_header = "Transport Management";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Browse and manage the school transit bus routes</h5>
    <div class="d-flex gap-2">
        <a href="routes.php" class="btn btn-primary">
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
    <!-- ADD ROUTE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add New Route</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Route Name <span class="text-danger">*</span></label>
                        <input type="text" name="route_name" class="form-control" placeholder="e.g. East Route - Noida" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Start Point</label>
                        <input type="text" name="start_point" class="form-control" placeholder="e.g. School Campus">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">End Point</label>
                        <input type="text" name="end_point" class="form-control" placeholder="e.g. Noida Sector 62">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Distance (KM)</label>
                            <input type="number" step="0.01" name="distance_km" value="0.00" class="form-control" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Monthly Fee (₹)</label>
                            <input type="number" step="0.01" name="monthly_fee" value="0.00" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" name="save_route" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Route
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST ROUTES -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Route Name</th>
                                <th>Route Path</th>
                                <th>Distance</th>
                                <th>Monthly Fee</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($routes) > 0): ?>
                                <?php foreach($routes as $route): ?>
                                    <tr>
                                        <td class="ps-4"><?= htmlspecialchars($route['id']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($route['route_name']) ?></td>
                                        <td>
                                            <div class="small">
                                                <i class="fa fa-map-marker-alt text-success me-1"></i> <?= htmlspecialchars($route['start_point'] ?: '-') ?>
                                            </div>
                                            <div class="small">
                                                <i class="fa fa-map-pin text-danger me-1"></i> <?= htmlspecialchars($route['end_point'] ?: '-') ?>
                                            </div>
                                        </td>
                                        <td><?= number_format($route['distance_km'], 2) ?> KM</td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($route['monthly_fee'], 2) ?></td>
                                        <td>
                                            <?php if($route['status'] == 'Active'): ?>
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
                                        <i class="fa fa-route fs-2 mb-2 d-block"></i>
                                        No transport routes configured.
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
