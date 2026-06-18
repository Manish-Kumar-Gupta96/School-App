<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "School Events List | Admin Control";
$active_menu = "events";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$message = '';
$error = '';

// Handle Delete Event
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "Event deleted successfully.";
    } catch (Exception $e) {
        $error = "Failed to delete event: " . $e->getMessage();
    }
}

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY event_date DESC, event_time DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📅 Campus Events Calendar</h2>
            <p class="text-muted mb-0">Manage and schedule public events, athletic meets, orientations, and assemblies.</p>
        </div>
        <a href="create.php" class="btn btn-primary">
            <i class="fa fa-plus-circle me-1"></i> Schedule New Event
        </a>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- EVENTS LISTING -->
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Event Date & Time</th>
                            <th>Event Title</th>
                            <th>Description</th>
                            <th>Venue / Location</th>
                            <th class="pe-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($events) > 0): ?>
                            <?php foreach($events as $e): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= date('d M Y', strtotime($e['event_date'])) ?></div>
                                        <span class="text-muted small"><i class="fa fa-clock me-1"></i> <?= date('h:i A', strtotime($e['event_time'])) ?></span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($e['title']) ?></td>
                                    <td>
                                        <div class="text-secondary small" style="max-width: 300px; white-space: normal;"><?= nl2br(htmlspecialchars($e['description'])) ?></div>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><i class="fa fa-map-marker-alt text-danger me-1"></i> <?= htmlspecialchars($e['location']) ?></span></td>
                                    <td class="pe-4 text-center">
                                        <a href="?delete_id=<?= $e['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this event?');">
                                            <i class="fa fa-trash-alt"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa fa-calendar-times fs-2 mb-2 d-block text-secondary"></i>
                                    No campus events scheduled yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
