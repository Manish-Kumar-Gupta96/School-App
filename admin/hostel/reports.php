<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Total Rooms
$stmt_tr = $pdo->prepare("SELECT COUNT(*) FROM hostel_rooms WHERE school_id = ?");
$stmt_tr->execute([CURRENT_SCHOOL_ID]);
$totalRooms = $stmt_tr->fetchColumn();

// Total Beds
$stmt_tb = $pdo->prepare("SELECT COUNT(*) FROM hostel_beds WHERE school_id = ?");
$stmt_tb->execute([CURRENT_SCHOOL_ID]);
$totalBeds = $stmt_tb->fetchColumn();

// Occupied Beds
$stmt_ob = $pdo->prepare("SELECT COUNT(*) FROM hostel_beds WHERE occupied = 1 AND school_id = ?");
$stmt_ob->execute([CURRENT_SCHOOL_ID]);
$occupiedBeds = $stmt_ob->fetchColumn();

$availableBeds = $totalBeds - $occupiedBeds;

// Total Residents
$stmt_stud = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM hostel_beds WHERE student_id IS NOT NULL AND school_id = ?");
$stmt_stud->execute([CURRENT_SCHOOL_ID]);
$totalStudents = $stmt_stud->fetchColumn();

// Total Paid Fees Collection
$stmt_fees = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM hostel_fees WHERE status = 'paid' AND school_id = ?");
$stmt_fees->execute([CURRENT_SCHOOL_ID]);
$totalCollection = $stmt_fees->fetchColumn();

// Total Visitors
$stmt_vis_count = $pdo->prepare("SELECT COUNT(*) FROM hostel_visitors WHERE school_id = ?");
$stmt_vis_count->execute([CURRENT_SCHOOL_ID]);
$totalVisitors = $stmt_vis_count->fetchColumn();

// Room Occupancy Ledger
$stmt_occ = $pdo->prepare("
    SELECT hr.*, h.hostel_name, h.hostel_type,
           (SELECT COUNT(*) FROM hostel_beds WHERE room_id = hr.id AND occupied = 1) AS occupied_beds
    FROM hostel_rooms hr
    JOIN hostels h ON hr.hostel_id = h.id
    WHERE hr.school_id = ?
    ORDER BY h.hostel_name ASC, hr.room_no ASC
");
$stmt_occ->execute([CURRENT_SCHOOL_ID]);
$occupancy = $stmt_occ->fetchAll(PDO::FETCH_ASSOC);

// Visitor Report
$stmt_vis_rep = $pdo->prepare("
    SELECT hv.*, s.first_name, s.last_name, s.admission_no
    FROM hostel_visitors hv
    JOIN students s ON hv.student_id = s.id
    WHERE hv.school_id = ?
    ORDER BY hv.id DESC
    LIMIT 10
");
$stmt_vis_rep->execute([CURRENT_SCHOOL_ID]);
$visitorReport = $stmt_vis_rep->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Hostel Analytics | VIC ERP";
$page_header = "Hostel Analytics Board";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Overview of hostel rooms, occupied beds, visitors, and collections</h5>
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
        <a href="fees.php" class="btn btn-outline-warning">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="reports.php" class="btn btn-primary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
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
                <span class="text-uppercase small" style="font-size: 0.72rem;">Paid Billing</span>
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
                                <th class="ps-4">Hostel Block</th>
                                <th>Room</th>
                                <th>Total Beds</th>
                                <th>Occupied</th>
                                <th>Beds Free</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($occupancy) > 0): ?>
                                <?php foreach($occupancy as $room): 
                                    $free = $room['total_beds'] - $room['occupied_beds'];
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold">
                                            <?= htmlspecialchars($room['hostel_name']) ?>
                                            <span class="small text-muted block">(<?= ucfirst($room['hostel_type']) ?>)</span>
                                        </td>
                                        <td class="fw-bold text-primary">Room <?= htmlspecialchars($room['room_no']) ?></td>
                                        <td><?= (int)$room['total_beds'] ?></td>
                                        <td><?= (int)$room['occupied_beds'] ?></td>
                                        <td class="fw-bold text-success"><?= $free ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No room listings found.</td>
                                </tr>
                            <?php endif; ?>
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
                <span class="badge bg-danger">Total Logs: <?= $totalVisitors ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Visitor</th>
                                <th>Student Met</th>
                                <th>Check-In</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($visitorReport) > 0): ?>
                                <?php foreach($visitorReport as $visitor): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($visitor['visitor_name']) ?></div>
                                            <span class="text-muted small">Relation: <?= htmlspecialchars($visitor['relation_name'] ?: '-') ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary mb-0"><?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($visitor['admission_no']) ?></span>
                                        </td>
                                        <td><span class="small text-muted"><i class="fa fa-clock text-success me-1"></i> <?= date('d M Y h:i A', strtotime($visitor['visit_time'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">No visitors registered.</td>
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
