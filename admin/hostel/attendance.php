<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Process Attendance Submission
if (isset($_POST['save_attendance'])) {
    $attendance_date = $_POST['attendance_date'];
    $status_array    = isset($_POST['status']) ? $_POST['status'] : [];

    if (empty($attendance_date)) {
        $error = "Date is required.";
    } else {
        // Fetch all active residents to ensure we record for all
        $stmt_res = $pdo->prepare("
            SELECT student_id 
            FROM hostel_beds 
            WHERE school_id = ? AND student_id IS NOT NULL
        ");
        $stmt_res->execute([CURRENT_SCHOOL_ID]);
        $residents = $stmt_res->fetchAll(PDO::FETCH_COLUMN);

        foreach ($residents as $stud_id) {
            $status = isset($status_array[$stud_id]) ? $status_array[$stud_id] : 'absent';
            
            // Check if record already exists for this student on this date
            $stmt_check = $pdo->prepare("
                SELECT id FROM hostel_attendance 
                WHERE student_id = ? AND attendance_date = ? AND school_id = ?
            ");
            $stmt_check->execute([$stud_id, $attendance_date, CURRENT_SCHOOL_ID]);
            $existing_id = $stmt_check->fetchColumn();

            if ($existing_id) {
                // Update
                $stmt_update = $pdo->prepare("
                    UPDATE hostel_attendance 
                    SET status = ? 
                    WHERE id = ? AND school_id = ?
                ");
                $stmt_update->execute([$status, $existing_id, CURRENT_SCHOOL_ID]);
            } else {
                // Insert
                $stmt_insert = $pdo->prepare("
                    INSERT INTO hostel_attendance (school_id, student_id, attendance_date, status)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt_insert->execute([CURRENT_SCHOOL_ID, $stud_id, $attendance_date, $status]);
            }
        }
        $message = "Attendance saved successfully for date " . date('d M Y', strtotime($attendance_date)) . "!";
        $date = $attendance_date;
    }
}

// Fetch Hostel Residents (students assigned to beds)
$stmt_residents = $pdo->prepare("
    SELECT hb.student_id, s.first_name, s.last_name, s.admission_no, hr.room_no, h.hostel_name,
           ha.status AS attendance_status
    FROM hostel_beds hb
    JOIN students s ON hb.student_id = s.id
    JOIN hostel_rooms hr ON hb.room_id = hr.id
    JOIN hostels h ON hr.hostel_id = h.id
    LEFT JOIN hostel_attendance ha ON s.id = ha.student_id AND ha.attendance_date = ? AND ha.school_id = ?
    WHERE hb.school_id = ? AND hb.student_id IS NOT NULL
    ORDER BY h.hostel_name ASC, s.first_name ASC
");
$stmt_residents->execute([$date, CURRENT_SCHOOL_ID, CURRENT_SCHOOL_ID]);
$boarders = $stmt_residents->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Hostel Attendance | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and review student attendance registers</h5>
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
        <a href="attendance.php" class="btn btn-primary">
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
    <!-- Filter Date -->
    <div class="col-12 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h5 class="fw-bold mb-0 text-dark">Date Filter</h5>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="fw-semibold mb-0 me-1">Choose Date:</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>
    </div>

    <!-- Attendance Form -->
    <div class="col-12">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Daily Attendance Sheet (<?= date('d M Y', strtotime($date)) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <form method="POST">
                    <input type="hidden" name="attendance_date" value="<?= htmlspecialchars($date) ?>">
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4" width="100">Admission No</th>
                                    <th>Student Name</th>
                                    <th>Hostel / Building</th>
                                    <th>Room No</th>
                                    <th class="text-center" width="250">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if (count($boarders) > 0): ?>
                                    <?php foreach($boarders as $b): 
                                        $status = $b['attendance_status'] ?: 'present';
                                    ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-secondary"><?= htmlspecialchars($b['admission_no']) ?></td>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></td>
                                            <td><?= htmlspecialchars($b['hostel_name']) ?></td>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($b['room_no']) ?></td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-3">
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="status[<?= $b['student_id'] ?>]" id="pres_<?= $b['student_id'] ?>" value="present" <?= $status === 'present' ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-success fw-semibold" for="pres_<?= $b['student_id'] ?>">Present</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="status[<?= $b['student_id'] ?>]" id="abs_<?= $b['student_id'] ?>" value="absent" <?= $status === 'absent' ? 'checked' : '' ?>>
                                                        <label class="form-check-label text-danger fw-semibold" for="abs_<?= $b['student_id'] ?>">Absent</label>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fa fa-users-slash fs-2 mb-2 d-block"></i>
                                            No student boarders registered in bed allocation.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (count($boarders) > 0): ?>
                        <div class="p-4 bg-light text-end">
                            <button type="submit" name="save_attendance" class="btn btn-success px-4 py-2">
                                <i class="fa fa-save me-1"></i> Save Daily Register
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
