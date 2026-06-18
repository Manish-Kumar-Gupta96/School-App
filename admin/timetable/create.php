<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Create Timetable Schedule | Admin Control";
$active_menu = "timetable";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $class        = trim($_POST['class']);
    $day          = $_POST['day'];
    $period       = (int)$_POST['period'];
    $subject      = trim($_POST['subject']);
    $teacher_name = trim($_POST['teacher']);
    $start        = $_POST['start'];
    $end          = $_POST['end'];

    if(empty($class) || empty($subject) || empty($teacher_name) || empty($start) || empty($end)){
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO timetable (class, day, period, subject, teacher_name, start_time, end_time)
            VALUES (?,?,?,?,?,?,?)
        ");
        $stmt->execute([$class, $day, $period, $subject, $teacher_name, $start, $end]);
        $message = "Timetable slot saved successfully!";
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📅 Create Timetable Slot</h2>
            <p class="text-muted mb-0">Map subjects, time periods, and mentors to weekly timetables.</p>
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

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px; max-width: 700px;">
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-plus me-2 text-primary"></i>Schedule Period Slot</h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Class Group</label>
                <input type="text" name="class" class="form-control" placeholder="e.g. 10-A" required>
            </div>
            
            <div class="col-md-6">
                <label class="form-label fw-semibold">Day of Week</label>
                <select name="day" class="form-select" required>
                    <option>Monday</option>
                    <option>Tuesday</option>
                    <option>Wednesday</option>
                    <option>Thursday</option>
                    <option>Friday</option>
                    <option>Saturday</option>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Period Number</label>
                <input type="number" name="period" class="form-control" placeholder="e.g. 1" required min="1">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Subject</label>
                <input type="text" name="subject" class="form-control" placeholder="e.g. Mathematics" required>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Assigned Teacher Name</label>
                <input type="text" name="teacher" class="form-control" placeholder="e.g. Mr. Rajesh Sharma" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Start Time</label>
                <input type="time" name="start" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">End Time</label>
                <input type="time" name="end" class="form-control" required>
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="save" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-save me-1"></i> Save Timetable Slot
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
