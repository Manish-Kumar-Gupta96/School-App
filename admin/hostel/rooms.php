<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_room'])){
    $hostel_name = trim($_POST['hostel_name']);
    $floor_no    = trim($_POST['floor_no']);
    $room_no     = trim($_POST['room_no']);
    $room_type   = (int)$_POST['room_type_id'];
    $total_beds  = (int)$_POST['total_beds'];
    $status      = $_POST['status'];

    if(empty($hostel_name) || empty($floor_no) || empty($room_no) || $total_beds <= 0 || empty($room_type)){
        $error = "Please fill in all required fields.";
    } else {
        // Check if room_no already exists in this hostel building
        $check = $pdo->prepare("SELECT id FROM hostel_rooms WHERE hostel_name = ? AND room_no = ?");
        $check->execute([$hostel_name, $room_no]);
        
        if($check->rowCount() > 0){
            $error = "This room number is already registered in this hostel block.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO hostel_rooms(hostel_name, floor_no, room_no, room_type_id, total_beds, occupied_beds, status)
                VALUES(?,?,?,?,?,0,?)
            ");
            $stmt->execute([
                $hostel_name,
                $floor_no,
                $room_no,
                $room_type,
                $total_beds,
                $status
            ]);
            $message = "Hostel Room Registered Successfully!";
        }
    }
}

// Fetch active room types for dropdown
$roomTypes = $pdo->query("SELECT * FROM hostel_room_types WHERE status='Active' ORDER BY room_type ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch hostel rooms with category name joined
$rooms = $pdo->query("
    SELECT hr.*, hrt.room_type, hrt.monthly_fee
    FROM hostel_rooms hr
    LEFT JOIN hostel_room_types hrt ON hr.room_type_id=hrt.id
    ORDER BY hr.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Hostel Rooms | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Configure actual rooms and bed listings</h5>
    <div class="d-flex gap-2">
        <a href="room-types.php" class="btn btn-outline-primary">
            <i class="fa fa-sliders-h me-1"></i> Room Types
        </a>
        <a href="rooms.php" class="btn btn-primary">
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
    <!-- ADD ROOM -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Room</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hostel Name / Building <span class="text-danger">*</span></label>
                        <input type="text" name="hostel_name" class="form-control" placeholder="e.g. Boys Hostel Block A" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Number <span class="text-danger">*</span></label>
                        <input type="text" name="floor_no" class="form-control" placeholder="e.g. First Floor" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
                        <input type="text" name="room_no" class="form-control" placeholder="e.g. 101" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Room Type <span class="text-danger">*</span></label>
                        <select name="room_type_id" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach($roomTypes as $type): ?>
                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['room_type']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Beds <span class="text-danger">*</span></label>
                        <input type="number" name="total_beds" value="2" min="1" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Available">Available</option>
                            <option value="Full">Full</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>

                    <button type="submit" name="save_room" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST ROOMS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel / Block</th>
                                <th>Floor</th>
                                <th>Room No</th>
                                <th>Room Type</th>
                                <th>Monthly Fee</th>
                                <th>Beds Info</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($rooms) > 0): ?>
                                <?php foreach($rooms as $r): 
                                    $av_beds = (int)$r['total_beds'] - (int)$r['occupied_beds'];
                                ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($r['hostel_name']) ?></td>
                                        <td><?= htmlspecialchars($r['floor_no']) ?></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($r['room_no']) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($r['room_type']) ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($r['monthly_fee'], 2) ?></td>
                                        <td>
                                            <div class="small"><i class="fa fa-bed me-1 text-muted"></i> <strong>Total:</strong> <?= (int)$r['total_beds'] ?></div>
                                            <div class="small"><i class="fa fa-user me-1 text-muted"></i> <strong>Occupied:</strong> <?= (int)$r['occupied_beds'] ?></div>
                                            <div class="small"><i class="fa fa-check-circle me-1 text-muted"></i> <strong>Free:</strong> <span class="text-success fw-bold"><?= $av_beds ?></span></div>
                                        </td>
                                        <td>
                                            <?php if($r['status'] == 'Available'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Available</span>
                                            <?php elseif($r['status'] == 'Full'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Full</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Maintenance</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-door-closed fs-2 mb-2 d-block"></i>
                                        No rooms configured in the hostel blocks.
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
