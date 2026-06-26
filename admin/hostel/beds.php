<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Allocate Bed
if (isset($_POST['allocate_bed'])) {
    $bed_id     = (int)$_POST['bed_id'];
    $student_id = (int)$_POST['student_id'];

    if (empty($bed_id) || empty($student_id)) {
        $error = "Bed and Student are required.";
    } else {
        // Verify bed is empty
        $stmt_check = $pdo->prepare("SELECT occupied FROM hostel_beds WHERE id = ? AND school_id = ?");
        $stmt_check->execute([$bed_id, CURRENT_SCHOOL_ID]);
        $occupied = $stmt_check->fetchColumn();

        if ($occupied) {
            $error = "This bed is already occupied.";
        } else {
            // Check if student is already allocated somewhere
            $stmt_stud = $pdo->prepare("SELECT id FROM hostel_beds WHERE student_id = ? AND school_id = ?");
            $stmt_stud->execute([$student_id, CURRENT_SCHOOL_ID]);
            if ($stmt_stud->rowCount() > 0) {
                $error = "This student is already allocated a bed in the hostel.";
            } else {
                $stmt = $pdo->prepare("
                    UPDATE hostel_beds 
                    SET student_id = ?, occupied = 1 
                    WHERE id = ? AND school_id = ?
                ");
                $stmt->execute([$student_id, $bed_id, CURRENT_SCHOOL_ID]);
                $message = "Bed allocated successfully!";
            }
        }
    }
}

// Deallocate Bed
if (isset($_GET['deallocate'])) {
    $bed_id = (int)$_GET['deallocate'];

    $stmt = $pdo->prepare("
        UPDATE hostel_beds 
        SET student_id = NULL, occupied = 0 
        WHERE id = ? AND school_id = ?
    ");
    $stmt->execute([$bed_id, CURRENT_SCHOOL_ID]);
    $message = "Bed deallocated successfully!";
    header("Location: beds.php");
    exit();
}

// Fetch all students (not in hostel)
$stmt_students = $pdo->prepare("
    SELECT id, first_name, last_name, admission_no 
    FROM students 
    WHERE school_id = ? AND id NOT IN (
        SELECT student_id FROM hostel_beds WHERE student_id IS NOT NULL AND school_id = ?
    )
    ORDER BY first_name ASC
");
$stmt_students->execute([CURRENT_SCHOOL_ID, CURRENT_SCHOOL_ID]);
$studentsList = $stmt_students->fetchAll(PDO::FETCH_ASSOC);

// Fetch unoccupied beds
$stmt_unocc = $pdo->prepare("
    SELECT hb.id, hb.bed_no, hr.room_no, h.hostel_name 
    FROM hostel_beds hb
    JOIN hostel_rooms hr ON hb.room_id = hr.id
    JOIN hostels h ON hr.hostel_id = h.id
    WHERE hb.school_id = ? AND hb.occupied = 0
    ORDER BY h.hostel_name ASC, hr.room_no ASC, hb.bed_no ASC
");
$stmt_unocc->execute([CURRENT_SCHOOL_ID]);
$unoccupiedBeds = $stmt_unocc->fetchAll(PDO::FETCH_ASSOC);

// Fetch all beds with occupancy details
$stmt_all = $pdo->prepare("
    SELECT hb.*, hr.room_no, h.hostel_name, h.hostel_type, s.first_name, s.last_name, s.admission_no 
    FROM hostel_beds hb 
    JOIN hostel_rooms hr ON hb.room_id = hr.id 
    JOIN hostels h ON hr.hostel_id = h.id 
    LEFT JOIN students s ON hb.student_id = s.id 
    WHERE hb.school_id = ? 
    ORDER BY h.hostel_name ASC, hr.room_no ASC, hb.bed_no ASC
");
$stmt_all->execute([CURRENT_SCHOOL_ID]);
$beds = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Bed Allocations | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage bed spacing and resident assignments</h5>
    <div class="d-flex gap-2">
        <a href="hostels.php" class="btn btn-outline-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="beds.php" class="btn btn-primary">
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
    <!-- Bed Allocation Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Allocate Bed</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Available Beds <span class="text-danger">*</span></label>
                        <select name="bed_id" class="form-select" required>
                            <option value="">Select Bed...</option>
                            <?php foreach($unoccupiedBeds as $bed): ?>
                                <option value="<?= $bed['id'] ?>">
                                    <?= htmlspecialchars($bed['hostel_name']) ?> - Room <?= htmlspecialchars($bed['room_no']) ?> (Bed <?= htmlspecialchars($bed['bed_no']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Student <span class="text-danger">*</span></label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student...</option>
                            <?php foreach($studentsList as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['admission_no']) ?> - <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="allocate_bed" class="btn btn-success w-100">
                        <i class="fa fa-user-plus me-1"></i> Allocate Bed
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bed Allocation Directory -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Bed Allocation Register</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel / Building</th>
                                <th>Room & Bed</th>
                                <th>Occupant</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($beds) > 0): ?>
                                <?php foreach($beds as $b): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($b['hostel_name']) ?></div>
                                            <span class="small text-muted">(<?= ucfirst($b['hostel_type']) ?>)</span>
                                        </td>
                                        <td>
                                            <div class="text-primary fw-semibold">Room <?= htmlspecialchars($b['room_no']) ?></div>
                                            <span class="small text-muted">Bed: <?= htmlspecialchars($b['bed_no']) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($b['occupied']): ?>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></div>
                                                <span class="badge bg-secondary-subtle text-secondary small">ID: <?= htmlspecialchars($b['admission_no']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted italic">Vacant</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($b['occupied']): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1">Occupied</span>
                                            <?php else: ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">Vacant</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($b['occupied']): ?>
                                                <a href="?deallocate=<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Deallocate student from this bed?')">
                                                    <i class="fa fa-user-minus"></i> Release
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-bed fs-2 mb-2 d-block"></i>
                                        No beds registered. Setup rooms to auto-generate beds.
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
