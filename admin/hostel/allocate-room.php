<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

/* ==========================
ALLOCATE ROOM
========================== */
if(isset($_POST['allocate_room'])){
    $student_id   = (int)$_POST['student_id'];
    $room_id      = (int)$_POST['room_id'];
    $bed_no       = trim($_POST['bed_no']);
    $joining_date = $_POST['joining_date'];

    $hostel_id = "HOST" . date('Y') . rand(1000,9999);

    if(empty($student_id) || empty($room_id) || empty($joining_date)){
        $error = "Student, Room, and Joining Date are required fields.";
    } else {
        // CHECK ROOM
        $roomStmt = $pdo->prepare("SELECT * FROM hostel_rooms WHERE id=?");
        $roomStmt->execute([$room_id]);
        $room = $roomStmt->fetch(PDO::FETCH_ASSOC);

        if(!$room){
            $error = "Selected Room Not Found.";
        } else {
            $availableBeds = (int)$room['total_beds'] - (int)$room['occupied_beds'];

            if($availableBeds <= 0){
                $error = "Failed to allocate: Selected room is already full.";
            } else {
                try {
                    $pdo->beginTransaction();

                    // Insert allocation
                    $stmt = $pdo->prepare("
                        INSERT INTO hostel_allocations(hostel_id, student_id, room_id, bed_no, joining_date, status)
                        VALUES(?,?,?,?,?,'Active')
                    ");
                    $stmt->execute([
                        $hostel_id,
                        $student_id,
                        $room_id,
                        $bed_no,
                        $joining_date
                    ]);

                    // Update occupancy
                    $update = $pdo->prepare("
                        UPDATE hostel_rooms
                        SET occupied_beds = occupied_beds + 1
                        WHERE id=?
                    ");
                    $update->execute([$room_id]);

                    // Automatically update status to Full if capacity reached
                    if ((int)$room['occupied_beds'] + 1 >= (int)$room['total_beds']) {
                        $status_update = $pdo->prepare("UPDATE hostel_rooms SET status='Full' WHERE id=?");
                        $status_update->execute([$room_id]);
                    }

                    $pdo->commit();
                    $message = "Room Allocated Successfully! Assigned ID: " . $hostel_id;
                } catch(Exception $e) {
                    $pdo->rollBack();
                    $error = "Failed to allocate room: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch students
$students = $pdo->query("
    SELECT id, admission_no, first_name, last_name 
    FROM students 
    ORDER BY first_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch available rooms (where beds are free)
$rooms = $pdo->query("
    SELECT id, hostel_name, room_no, total_beds, occupied_beds 
    FROM hostel_rooms 
    WHERE status='Available' AND (total_beds - occupied_beds) > 0
    ORDER BY room_no ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch allocation list
$allocations = $pdo->query("
    SELECT ha.*, s.admission_no, s.first_name, s.last_name, hr.room_no, hr.hostel_name
    FROM hostel_allocations ha
    LEFT JOIN students s ON ha.student_id=s.id
    LEFT JOIN hostel_rooms hr ON ha.room_id=hr.id
    ORDER BY ha.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Allocate Hostel Room | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and manage student bed assignments</h5>
    <div class="d-flex gap-2">
        <a href="room-types.php" class="btn btn-outline-primary">
            <i class="fa fa-sliders-h me-1"></i> Room Types
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="allocate-room.php" class="btn btn-primary">
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
    <!-- FORM ALLOCATION -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Allocate Room</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach($students as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Hostel Room <span class="text-danger">*</span></label>
                        <select name="room_id" class="form-select" required>
                            <option value="">Select Room</option>
                            <?php foreach($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>">
                                    <?= htmlspecialchars($r['hostel_name']) ?> - Room <?= htmlspecialchars($r['room_no']) ?> (Free: <?= $r['total_beds'] - $r['occupied_beds'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bed Number Location</label>
                        <input type="text" name="bed_no" class="form-control" placeholder="e.g. Bed A-01">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Allocation Joining Date <span class="text-danger">*</span></label>
                        <input type="date" name="joining_date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>

                    <button type="submit" name="allocate_room" class="btn btn-success w-100">
                        <i class="fa fa-user-tag me-1"></i> Allocate Room
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ALLOCATIONS LIST -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Active Resident Allocations</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel ID</th>
                                <th>Student details</th>
                                <th>Hostel Block</th>
                                <th>Room No</th>
                                <th>Bed Location</th>
                                <th>Joining Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($allocations) > 0): ?>
                                <?php foreach($allocations as $row): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-dark"><?= htmlspecialchars($row['hostel_id']) ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($row['admission_no']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($row['hostel_name'] ?: '-') ?></td>
                                        <td class="fw-bold text-primary">Room <?= htmlspecialchars($row['room_no'] ?: '-') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($row['bed_no'] ?: '-') ?></span></td>
                                        <td><?= date('d M Y', strtotime($row['joining_date'])) ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Left</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fa fa-user-tag fs-2 mb-2 d-block"></i>
                                        No student room allocations found.
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
