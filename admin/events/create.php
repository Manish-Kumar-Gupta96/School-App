<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Schedule School Event | Admin Control";
$active_menu = "events";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date        = $_POST['date'];
    $time        = $_POST['time'];
    $location    = trim($_POST['location']);

    if(empty($title) || empty($date) || empty($time) || empty($location)){
        $error = "All fields except description are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO events (title, description, event_date, event_time, location, created_by)
            VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$title, $description, $date, $time, $location, $_SESSION['user_id'] ?? 1]);
        $message = "Upcoming school event scheduled successfully!";
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📅 Schedule Upcoming Event</h2>
            <p class="text-muted mb-0">Publish special campus events, orientations, or parent-teacher meeting notifications.</p>
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
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-calendar-plus me-2 text-primary"></i>New Event Details</h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-12">
                <label class="form-label fw-semibold">Event Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Annual Athletic Meet 2026" required>
            </div>
            
            <div class="col-md-12">
                <label class="form-label fw-semibold">Event Description / Guidelines</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Enter details or schedule guidelines..."></textarea>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Event Date</label>
                <input type="date" name="date" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-semibold">Event Time</label>
                <input type="time" name="time" class="form-control" required>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Event Location / Venue</label>
                <input type="text" name="location" class="form-control" placeholder="e.g. School Main Sports Ground" required>
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="save" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-calendar-check me-1"></i> Save Event
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
