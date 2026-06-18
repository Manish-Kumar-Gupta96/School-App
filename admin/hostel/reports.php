<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
HOSTEL SUMMARY
========================== */
$totalRooms = $pdo->query("SELECT COUNT(*) as total FROM hostel_rooms")->fetch(PDO::FETCH_ASSOC)['total'];
$totalBeds = $pdo->query("SELECT IFNULL(SUM(total_beds),0) as total FROM hostel_rooms")->fetch(PDO::FETCH_ASSOC)['total'];
$occupiedBeds = $pdo->query("SELECT IFNULL(SUM(occupied_beds),0) as total FROM hostel_rooms")->fetch(PDO::FETCH_ASSOC)['total'];
$availableBeds = (int)$totalBeds - (int)$occupiedBeds;

$totalStudents = $pdo->query("SELECT COUNT(*) as total FROM hostel_allocations WHERE status='Active'")->fetch(PDO::FETCH_ASSOC)['total'];
$totalCollection = $pdo->query("SELECT IFNULL(SUM(amount),0) as total FROM hostel_fee_payments")->fetch(PDO::FETCH_ASSOC)['total'];
$totalVisitors = $pdo->query("SELECT COUNT(*) as total FROM hostel_visitors")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
ROOM OCCUPANCY
========================== */
$occupancy = $pdo->query("
    SELECT hr.hostel_name, hr.room_no, hr.total_beds, hr.occupied_beds, hrt.room_type
    FROM hostel_rooms hr
    LEFT JOIN hostel_room_types hrt ON hr.room_type_id = hrt.id
    ORDER BY hr.hostel_name, hr.room_no
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================
VISITOR REPORT
========================== */
$visitorReport = $pdo->query("
    SELECT hv.visitor_name, hv.relation_with_student, hv.entry_time, hv.exit_time, s.first_name, s.last_name, s.admission_no
    FROM hostel_visitors hv
    LEFT JOIN students s ON hv.student_id = s.id
    ORDER BY hv.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Hostel Analytics | VIC ERP";
$page_header = "Hostel Analytics Board";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Overview of hostel rooms, occupied beds, visitors, and collections</h5>
    <a href="room-types.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Configurations
    </a>
</div>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4 text-center">
    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-door-open fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalRooms ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Total Rooms</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-info text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-bed fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalBeds ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Total Beds</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-secondary text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-user-check fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $occupiedBeds ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Occupied Beds</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-check-double fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $availableBeds ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Available Beds</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-warning text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-user-friends fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0"><?= $totalStudents ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Residents</span>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body py-3 px-2">
                <i class="fa fa-money-bill-wave fs-2 mb-1 opacity-75"></i>
                <h4 class="fw-bold mb-0" style="font-size: 1.15rem;">₹ <?= number_format($totalCollection, 2) ?></h4>
                <span class="text-uppercase small" style="font-size: 0.72rem;">Billing</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ROOM OCCUPANCY REPORT -->
    <div class="col-lg-6">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-home text-primary me-2"></i> Room Occupancy Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel / Block</th>
                                <th>Room</th>
                                <th>Category</th>
                                <th>Total Beds</th>
                                <th>Occupied</th>
                                <th>Beds Free</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php foreach($occupancy as $room): 
                                $free = $room['total_beds'] - $room['occupied_beds'];
                            ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($room['hostel_name']) ?></td>
                                    <td class="fw-bold text-primary">Room <?= htmlspecialchars($room['room_no']) ?></td>
                                    <td><?= htmlspecialchars($room['room_type']) ?></td>
                                    <td><?= (int)$room['total_beds'] ?></td>
                                    <td><?= (int)$room['occupied_beds'] ?></td>
                                    <td class="fw-bold text-success"><?= $free ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- VISITORS LIST LOG -->
    <div class="col-lg-6">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-users text-danger me-2"></i> Recent Visitors Log</h5>
                <span class="badge bg-danger">Total: <?= $totalVisitors ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Student Met</th>
                                <th>Check-In</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($visitorReport) > 0): ?>
                                <?php foreach($visitorReport as $visitor): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($visitor['visitor_name']) ?></div>
                                            <span class="text-muted small">Relation: <?= htmlspecialchars($visitor['relation_with_student'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary mb-0"><?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($visitor['admission_no']) ?></span>
                                        </td>
                                        <td><span class="small text-muted"><i class="fa fa-clock text-success me-1"></i> <?= date('d M Y h:i A', strtotime($visitor['entry_time'])) ?></span></td>
                                        <td>
                                            <?php if($visitor['exit_time']): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Exited</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Inside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No visitors registered.</td>
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
