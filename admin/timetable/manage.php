<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;

// Handle Allocation Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Security Validation Breach.");
    }

    $className = filter_input(INPUT_POST, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $dayOfWeek = filter_input(INPUT_POST, 'day_of_week', FILTER_SANITIZE_SPECIAL_CHARS);
    $periodNumber = filter_input(INPUT_POST, 'period_number', FILTER_VALIDATE_INT);
    $subjectName = filter_input(INPUT_POST, 'subject_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $teacherId = filter_input(INPUT_POST, 'teacher_id', FILTER_VALIDATE_INT);
    $roomNumber = filter_input(INPUT_POST, 'room_number', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($className) || empty($dayOfWeek) || !$periodNumber || empty($subjectName) || !$teacherId) {
        $message = "All field parameters are strictly mandatory.";
        $status = false;
    } else {
        try {
            // Check Teacher Conflict manually for robust error reporting
            $conflictCheck = $db->prepare("SELECT id FROM school_timetable WHERE teacher_id = :tid AND day_of_week = :day AND period_number = :period LIMIT 1");
            $conflictCheck->execute([':tid' => $teacherId, ':day' => $dayOfWeek, ':period' => $periodNumber]);
            
            if ($conflictCheck->fetch()) {
                $message = "Conflict Error: Target teacher is already allocated to another classroom during this specific period.";
                $status = false;
            } else {
                // Secure Insert / Upsert
                $stmt = $db->prepare("INSERT INTO school_timetable (class_name, day_of_week, period_number, subject_name, teacher_id, room_number) 
                                      VALUES (:class, :day, :period, :subject, :tid, :room)
                                      ON DUPLICATE KEY UPDATE subject_name = :subject, teacher_id = :tid, room_number = :room");
                
                $stmt->execute([
                    ':class'   => $className,
                    ':day'     => $dayOfWeek,
                    ':period'  => $periodNumber,
                    ':subject' => $subjectName,
                    ':tid'     => $teacherId,
                    ':room'    => $roomNumber
                ]);

                AuditLogger::log('TIMETABLE_CONFIGURED', "Scheduled {$subjectName} for {$className} on {$dayOfWeek} Period {$periodNumber}");
                $message = "Timetable slot mapped and locked successfully!";
            }
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $status = false;
        }
    }
}

// Fetch all available teachers for dropdown menu logic mapping
$teachers = $db->query("SELECT id, username FROM users WHERE role = 'teacher' ORDER BY username ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enterprise Timetable Allocator</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white font-weight-bold">Timetable Period Allocator Console</div>
        <div class="card-body">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="row g-3">
                <?php Csrf::injectInput(); ?>
                
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Target Class</label>
                    <select name="class_name" class="form-select form-select-sm" required>
                        <option value="Class 10">Class 10</option>
                        <option value="Class 11">Class 11</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Day</label>
                    <select name="day_of_week" class="form-select form-select-sm" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Period Slot Number</label>
                    <input type="number" name="period_number" min="1" max="8" class="form-control form-control-sm" placeholder="e.g. 1" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Subject Name</label>
                    <input type="text" name="subject_name" class="form-control form-control-sm" placeholder="e.g. Physics" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Assigned Educator</label>
                    <select name="teacher_id" class="form-select form-select-sm" required>
                        <option value="">-- Choose Instructor --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-weight-bold">Room Allocation Code</label>
                    <input type="text" name="room_number" class="form-control form-control-sm" placeholder="e.g. Lab-3A">
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" name="save_schedule" class="btn btn-primary btn-sm px-4">Lock Schedule Row</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
