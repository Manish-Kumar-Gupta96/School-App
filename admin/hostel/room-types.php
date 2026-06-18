<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_room_type'])){
    $room_type   = trim($_POST['room_type']);
    $capacity    = (int)$_POST['bed_capacity'];
    $monthly_fee = !empty($_POST['monthly_fee']) ? (float)$_POST['monthly_fee'] : 0.00;
    $description = trim($_POST['description']);
    $status      = $_POST['status'];

    if(empty($room_type) || $capacity <= 0){
        $error = "Room Type and Bed Capacity are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostel_room_types(room_type, bed_capacity, monthly_fee, description, status)
            VALUES(?,?,?,?,?)
        ");
        $stmt->execute([
            $room_type,
            $capacity,
            $monthly_fee,
            $description,
            $status
        ]);
        $message = "Room Type Added Successfully!";
    }
}

$roomTypes = $pdo->query("
    SELECT *
    FROM hostel_room_types
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Hostel Room Types | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Configure room types and rates</h5>
    <div class="d-flex gap-2">
        <a href="room-types.php" class="btn btn-primary">
            <i class="fa fa-sliders-h me-1"></i> Room Types
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="allocate-room.php" class="btn btn-outline-primary">
            <i class="fa fa-user-tag me-1"></i> Allocations
        </a>
        <a href="hostel-fees.php" class="btn btn-outline-warning">
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
    <!-- ADD ROOM TYPE -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add Room Type</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Room Type Name <span class="text-danger">*</span></label>
                        <input type="text" name="room_type" class="form-control" placeholder="e.g. AC Double Sharing" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bed Capacity <span class="text-danger">*</span></label>
                        <input type="number" name="bed_capacity" value="2" min="1" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Monthly Fee (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="monthly_fee" value="0.00" class="form-control" required min="0">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Facilities, features, details..."></textarea>
                    </div>

                    <button type="submit" name="save_room_type" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST ROOM TYPES -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">ID</th>
                                <th>Room Type</th>
                                <th>Bed Capacity</th>
                                <th>Monthly Fee</th>
                                <th>Description</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($roomTypes) > 0): ?>
                                <?php foreach($roomTypes as $room): ?>
                                    <tr>
                                        <td class="ps-4"><?= htmlspecialchars($room['id']) ?></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($room['room_type']) ?></td>
                                        <td><?= (int)$room['bed_capacity'] ?> Beds</td>
                                        <td class="fw-bold text-primary">₹ <?= number_format($room['monthly_fee'], 2) ?></td>
                                        <td><span class="text-muted small"><?= htmlspecialchars($room['description'] ?: '-') ?></span></td>
                                        <td>
                                            <?php if($room['status'] == 'Active'): ?>
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
                                        <i class="fa fa-sliders-h fs-2 mb-2 d-block"></i>
                                        No hostel room types defined.
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
