<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Room
if (isset($_POST['save_room'])) {
    $hostel_id   = (int)$_POST['hostel_id'];
    $floor_no    = (int)$_POST['floor_no'];
    $room_no     = trim($_POST['room_no']);
    $total_beds  = (int)$_POST['total_beds'];
    $status      = $_POST['status'];

    if (empty($hostel_id) || empty($room_no) || $total_beds <= 0) {
        $error = "Hostel Building, Room Number, and Total Beds are required.";
    } else {
        // Check if room_no already exists in this hostel building
        $check = $pdo->prepare("SELECT id FROM hostel_rooms WHERE school_id = ? AND hostel_id = ? AND room_no = ?");
        $check->execute([CURRENT_SCHOOL_ID, $hostel_id, $room_no]);
        
        if ($check->rowCount() > 0) {
            $error = "This room number is already registered in this hostel block.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO hostel_rooms (school_id, hostel_id, room_no, floor_no, total_beds, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                CURRENT_SCHOOL_ID,
                $hostel_id,
                $room_no,
                $floor_no,
                $total_beds,
                $status
            ]);

            // Auto-create bed entities in hostel_beds for allocation
            $room_id = $pdo->lastInsertId();
            for ($i = 1; $i <= $total_beds; $i++) {
                $bed_no = $room_no . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
                $stmt_bed = $pdo->prepare("
                    INSERT INTO hostel_beds (school_id, room_id, bed_no, student_id, occupied)
                    VALUES (?, ?, ?, NULL, 0)
                ");
                $stmt_bed->execute([CURRENT_SCHOOL_ID, $room_id, $bed_no]);
            }

            $message = "Room and its beds registered successfully!";
        }
    }
}

// Fetch Hostels for dropdown
$stmt_hostels = $pdo->prepare("SELECT * FROM hostels WHERE school_id = ? ORDER BY hostel_name ASC");
$stmt_hostels->execute([CURRENT_SCHOOL_ID]);
$hostelList = $stmt_hostels->fetchAll(PDO::FETCH_ASSOC);

// Fetch Rooms with Hostel names
$stmt_rooms = $pdo->prepare("
    SELECT hr.*, h.hostel_name, h.hostel_type 
    FROM hostel_rooms hr 
    JOIN hostels h ON hr.hostel_id = h.id 
    WHERE hr.school_id = ? 
    ORDER BY hr.id DESC
");
$stmt_rooms->execute([CURRENT_SCHOOL_ID]);
$rooms = $stmt_rooms->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
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
        <a href="hostels.php" class="btn btn-outline-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-primary">
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
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Register Room -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Room</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hostel Block <span class="text-danger">*</span></label>
                        <select name="hostel_id" class="form-select" required>
                            <option value="">Select Hostel...</option>
                            <?php foreach($hostelList as $hostel): ?>
                                <option value="<?= $hostel['id'] ?>">
                                    <?= htmlspecialchars($hostel['hostel_name']) ?> (<?= ucfirst($hostel['hostel_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Floor Number</label>
                        <input type="number" name="floor_no" class="form-control" placeholder="e.g. 1" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Room Number <span class="text-danger">*</span></label>
                        <input type="text" name="room_no" class="form-control" placeholder="e.g. 101" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Beds <span class="text-danger">*</span></label>
                        <input type="number" name="total_beds" class="form-control" min="1" max="10" placeholder="e.g. 2" required>
                        <span class="text-muted small">Beds will be auto-generated for allocation.</span>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="available">Available</option>
                            <option value="full">Full</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>

                    <button type="submit" name="save_room" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- List Rooms -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Rooms Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel Block</th>
                                <th>Floor</th>
                                <th>Room No</th>
                                <th>Total Beds</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($rooms) > 0): ?>
                                <?php foreach($rooms as $r): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark">
                                            <?= htmlspecialchars($r['hostel_name']) ?>
                                            <span class="small text-muted block">(<?= ucfirst($r['hostel_type']) ?>)</span>
                                        </td>
                                        <td>Floor <?= (int)$r['floor_no'] ?></td>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($r['room_no']) ?></td>
                                        <td><?= (int)$r['total_beds'] ?> Beds</td>
                                        <td>
                                            <?php if ($r['status'] === 'available'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Available</span>
                                            <?php elseif ($r['status'] === 'full'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Full</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2">Maintenance</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-door-closed fs-2 mb-2 d-block"></i>
                                        No rooms configured.
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
