<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../includes/Csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Access Rules Enforcement
new BaseController();
BaseController::enforceRole(['teacher', 'admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;

// Current Date Defaulting
$targetDate = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_SPECIAL_CHARS) ?? date('Y-m-d');
$classFilter = filter_input(INPUT_GET, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Class 10';

// Handle Attendance Batch Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }

    $attendanceData = $_POST['attendance'] ?? []; // Array mapping: student_id => status (P/A/L)

    if (empty($attendanceData)) {
        $message = "No attendance data captured.";
        $status = false;
    } else {
        try {
            $db->beginTransaction();

            // Dynamic Upsert prepared statement
            $stmt = $db->prepare("INSERT INTO student_attendance (student_id, attendance_date, status, marked_by) 
                                  VALUES (:sid, :adate, :status, :uid) 
                                  ON DUPLICATE KEY UPDATE status = :status, marked_by = :uid");

            foreach ($attendanceData as $studentId => $currentStatus) {
                $stmt->execute([
                    ':sid'    => (int)$studentId,
                    ':adate'  => $targetDate,
                    ':status' => $currentStatus,
                    ':uid'    => $_SESSION['user_id']
                ]);
            }

            $db->commit();
            AuditLogger::log('ATTENDANCE_MARKED', "Attendance synchronized for {$classFilter} on {$targetDate}");
            $message = "Attendance sheet updated and synchronized successfully!";
        } catch (PDOException $e) {
            $db->rollBack();
            $message = "Database System Fault: " . $e->getMessage();
            $status = false;
        }
    }
}

// Fetch Students enrolled in the selected class
$stmt = $db->prepare("SELECT s.id, s.name, s.roll_no, a.status 
                      FROM students s 
                      LEFT JOIN student_attendance a ON s.id = a.student_id AND a.attendance_date = :adate
                      WHERE s.class_name = :class ORDER BY s.roll_no ASC");
$stmt->execute([':adate' => $targetDate, ':class' => $classFilter]);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Attendance Registry</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daily Student Attendance Management</h5>
            <span>Date: <strong><?php echo $targetDate; ?></strong></span>
        </div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Target Class</label>
                    <select name="class_name" class="form-select form-select-sm">
                        <option value="Class 10" <?php echo $classFilter === 'Class 10' ? 'selected' : ''; ?>>Class 10</option>
                        <option value="Class 11" <?php echo $classFilter === 'Class 11' ? 'selected' : ''; ?>>Class 11</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Registry Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?php echo $targetDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary btn-sm w-100">Load Attendance Sheet</button>
                </div>
            </form>

            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo Csrf::getToken(); ?>">
                
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th style="width: 10%;">Roll No</th>
                            <th>Student Name</th>
                            <th style="width: 40%;" class="text-center">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students) === 0): ?>
                            <tr><td colspan="3" class="text-center text-muted">No students mapped inside this class track.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $row): 
                                $currentStatus = $row['status'] ?? 'P'; // Defaulting value to Present
                            ?>
                            <tr>
                                <td><code><?php echo $row['roll_no']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $row['id']; ?>]" value="P" id="p_<?php echo $row['id']; ?>" <?php echo $currentStatus === 'P' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-success font-weight-bold" for="p_<?php echo $row['id']; ?>">Present</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $row['id']; ?>]" value="A" id="a_<?php echo $row['id']; ?>" <?php echo $currentStatus === 'A' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-danger font-weight-bold" for="a_<?php echo $row['id']; ?>">Absent</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="attendance[<?php echo $row['id']; ?>]" value="L" id="l_<?php echo $row['id']; ?>" <?php echo $currentStatus === 'L' ? 'checked' : ''; ?>>
                                            <label class="form-check-label text-warning font-weight-bold" for="l_<?php echo $row['id']; ?>">Leave</label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if (count($students) > 0): ?>
                    <button type="submit" name="save_attendance" class="btn btn-success btn-sm px-4"><i class="fas fa-save"></i> Save Roll State</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
</body>
</html>
