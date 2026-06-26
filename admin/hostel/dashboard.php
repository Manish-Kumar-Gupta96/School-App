<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'Admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// 1. Total Buildings & Capacity
$stmt_b = $pdo->prepare("SELECT COUNT(*) as total_buildings, SUM(capacity) as total_capacity FROM hostel_buildings WHERE school_id = ?");
$stmt_b->execute([$schoolId]);
$b_data = $stmt_b->fetch(PDO::FETCH_ASSOC);
$total_buildings = $b_data['total_buildings'] ?: 0;
$total_capacity = $b_data['total_capacity'] ?: 0;

// 2. Active Allocations (Occupied Beds)
$stmt_alloc = $pdo->prepare("SELECT COUNT(*) FROM hostel_allocations WHERE school_id = ? AND status = 'active'");
$stmt_alloc->execute([$schoolId]);
$occupied_beds = $stmt_alloc->fetchColumn();

$vacant_beds = $total_capacity - $occupied_beds;
$occupancy_rate = $total_capacity > 0 ? round(($occupied_beds / $total_capacity) * 100) : 0;

// 3. Pending Leave Requests
$stmt_leave = $pdo->prepare("SELECT COUNT(*) FROM hostel_leave_requests WHERE school_id = ? AND status = 'pending'");
$stmt_leave->execute([$schoolId]);
$pending_leaves = $stmt_leave->fetchColumn();

$root_path = "../../";
$page_title = "Hostel Dashboard | Admin Portal";
$page_header = "Hostel Management";
$active_menu = "hostel_dashboard";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Hostel Overview</h5>
    <div class="d-flex gap-2">
        <a href="rooms.php" class="btn btn-primary"><i class="fa fa-building me-1"></i> Manage Rooms</a>
        <a href="allocation.php" class="btn btn-outline-info"><i class="fa fa-bed me-1"></i> Allocations</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Buildings</h6>
                <h2 class="fw-bold mb-0"><?= number_format($total_buildings) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Occupied Beds</h6>
                <h2 class="fw-bold mb-0"><?= number_format($occupied_beds) ?> <span class="fs-6 opacity-75">/ <?= number_format($total_capacity) ?></span></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-info text-white h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Occupancy Rate</h6>
                <h2 class="fw-bold mb-0"><?= $occupancy_rate ?>%</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-dark h-100" style="border-radius: 12px;">
            <div class="card-body text-center">
                <h6 class="fw-bold opacity-75">Pending Leaves</h6>
                <h2 class="fw-bold mb-0"><?= number_format($pending_leaves) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-body">
                <h5 class="fw-bold mb-4">Quick Links</h5>
                <div class="d-grid gap-3">
                    <a href="attendance.php" class="btn btn-light text-start py-3 border">
                        <i class="fa fa-list-check text-primary me-3 fs-5 align-middle"></i> 
                        <span class="fw-bold text-dark">Evening Attendance</span>
                    </a>
                    <a href="mess.php" class="btn btn-light text-start py-3 border">
                        <i class="fa fa-utensils text-success me-3 fs-5 align-middle"></i> 
                        <span class="fw-bold text-dark">Mess Menu Setup</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
