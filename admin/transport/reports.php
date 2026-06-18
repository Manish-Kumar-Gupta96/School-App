<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
DASHBOARD COUNTS
========================== */
$totalRoutes = $pdo->query("SELECT COUNT(*) as total FROM transport_routes")->fetch(PDO::FETCH_ASSOC)['total'];
$totalVehicles = $pdo->query("SELECT COUNT(*) as total FROM vehicles")->fetch(PDO::FETCH_ASSOC)['total'];
$totalDrivers = $pdo->query("SELECT COUNT(*) as total FROM drivers")->fetch(PDO::FETCH_ASSOC)['total'];
$totalStudents = $pdo->query("SELECT COUNT(*) as total FROM student_transport WHERE status='Active'")->fetch(PDO::FETCH_ASSOC)['total'];
$totalCollection = $pdo->query("SELECT IFNULL(SUM(amount), 0) as total FROM transport_fee_payments")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
ROUTE COLLECTION REPORT
========================== */
$routeCollection = $pdo->query("
    SELECT r.route_name, SUM(t.amount) as total_amount
    FROM transport_fee_payments t
    LEFT JOIN transport_routes r ON t.route_id = r.id
    GROUP BY t.route_id
    ORDER BY total_amount DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
VEHICLE WISE STUDENTS
========================== */
$vehicleStudents = $pdo->query("
    SELECT v.vehicle_no, v.vehicle_type, COUNT(st.id) as total_students
    FROM student_transport st
    LEFT JOIN vehicles v ON st.vehicle_id = v.id
    WHERE st.status='Active'
    GROUP BY st.vehicle_id
    ORDER BY total_students DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Transport Analytics | VIC ERP";
$page_header = "Transport Analytics Board";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Overview of transit logistics, routes, and collection data</h5>
    <a href="routes.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Routes
    </a>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4 text-center">
    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-route fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalRoutes ?></h4>
                <span class="text-uppercase small" style="font-size: 0.75rem;">Total Routes</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-info text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-bus fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalVehicles ?></h4>
                <span class="text-uppercase small" style="font-size: 0.75rem;">Vehicles</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-secondary text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-id-card fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalDrivers ?></h4>
                <span class="text-uppercase small" style="font-size: 0.75rem;">Drivers</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-users fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalStudents ?></h4>
                <span class="text-uppercase small" style="font-size: 0.75rem;">Assigned Students</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-money-bill-wave fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0">₹ <?= number_format($totalCollection, 2) ?></h4>
                <span class="text-uppercase small" style="font-size: 0.75rem;">Total Collections</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ROUTE WISE COLLECTION -->
    <div class="col-md-6">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-coins me-2 text-success"></i> Route-Wise Collections</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Route</th>
                                <th>Total Revenue Collection</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($routeCollection) > 0): ?>
                                <?php foreach($routeCollection as $route): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($route['route_name'] ?: 'Unknown Route') ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($route['total_amount'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">No collections recorded yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- VEHICLE WISE STUDENTS -->
    <div class="col-md-6">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-bus-alt me-2 text-primary"></i> Vehicle Passenger Load</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Vehicle Number</th>
                                <th>Type</th>
                                <th>Active Allocated Students</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($vehicleStudents) > 0): ?>
                                <?php foreach($vehicleStudents as $vehicle): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($vehicle['vehicle_no'] ?: 'N/A') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($vehicle['vehicle_type']) ?></span></td>
                                        <td class="fw-semibold text-primary"><?= (int)$vehicle['total_students'] ?> Students Allocated</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No students assigned to vehicles.</td>
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
