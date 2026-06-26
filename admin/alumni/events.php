<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Create Event
if (isset($_POST['save_event'])) {
    $title       = trim($_POST['title']);
    $event_date  = $_POST['event_date'];
    $description = trim($_POST['description']);

    if (empty($title) || empty($event_date)) {
        $error = "Event Title and Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO alumni_events (school_id, title, event_date, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $title,
            $event_date,
            $description
        ]);
        $message = "Alumni event scheduled successfully!";
    }
}

// Register Alumni to Event
if (isset($_POST['register_alumni'])) {
    $alumni_id = (int)$_POST['alumni_id'];
    $event_id  = (int)$_POST['event_id'];

    if (empty($alumni_id) || empty($event_id)) {
        $error = "Alumni and Event are required.";
    } else {
        // Check if already registered
        $check = $pdo->prepare("SELECT id FROM alumni_event_registrations WHERE alumni_id = ? AND event_id = ? AND school_id = ?");
        $check->execute([$alumni_id, $event_id, CURRENT_SCHOOL_ID]);

        if ($check->rowCount() > 0) {
            $error = "This alumni is already registered for the event.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO alumni_event_registrations (school_id, alumni_id, event_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                CURRENT_SCHOOL_ID,
                $alumni_id,
                $event_id
            ]);
            $message = "Alumni RSVP recorded successfully!";
        }
    }
}

// Fetch Events
$stmt_events = $pdo->prepare("SELECT * FROM alumni_events WHERE school_id = ? ORDER BY event_date DESC");
$stmt_events->execute([CURRENT_SCHOOL_ID]);
$events = $stmt_events->fetchAll(PDO::FETCH_ASSOC);

// Fetch Alumni for registration dropdown
$stmt_alumni = $pdo->prepare("
    SELECT a.id, s.first_name, s.last_name, a.passout_year 
    FROM alumni a
    JOIN students s ON a.student_id = s.id
    WHERE a.school_id = ?
    ORDER BY s.first_name ASC
");
$stmt_alumni->execute([CURRENT_SCHOOL_ID]);
$alumniList = $stmt_alumni->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get registrations count for an event
function getRegistrations($event_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT aer.*, s.first_name, s.last_name, a.passout_year 
        FROM alumni_event_registrations aer
        JOIN alumni a ON aer.alumni_id = a.id
        JOIN students s ON a.student_id = s.id
        WHERE aer.event_id = ? AND aer.school_id = ?
    ");
    $stmt->execute([$event_id, CURRENT_SCHOOL_ID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Layout variables
$root_path = "../../";
$page_title = "Alumni Meet Events | VIC ERP";
$page_header = "Alumni Management";
$active_menu = "alumni";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Schedule and record registrations for alumni gatherings</h5>
    <div class="d-flex gap-2">
        <a href="directory.php" class="btn btn-outline-primary">
            <i class="fa fa-graduation-cap me-1"></i> Alumni Directory
        </a>
        <a href="events.php" class="btn btn-primary">
            <i class="fa fa-calendar-alt me-1"></i> Meet Events
        </a>
        <a href="donations.php" class="btn btn-outline-success">
            <i class="fa fa-hand-holding-dollar me-1"></i> Donations
        </a>
        <a href="portal.php" class="btn btn-outline-info">
            <i class="fa fa-share-nodes me-1"></i> Networking & Stories
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
    <!-- Schedule Event -->
    <div class="col-lg-4 mb-4">
        <!-- Schedule card -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Schedule Meet</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Event Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Annual Alumni Meet 2026" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Event Date <span class="text-danger">*</span></label>
                        <input type="date" name="event_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Details about event, venue, agenda..."></textarea>
                    </div>

                    <button type="submit" name="save_event" class="btn btn-success w-100">
                        <i class="fa fa-calendar-plus me-1"></i> Publish Event
                    </button>
                </form>
            </div>
        </div>

        <!-- RSVP RSVP Register Card -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Alumni RSVP</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choose Alumni <span class="text-danger">*</span></label>
                        <select name="alumni_id" class="form-select" required>
                            <option value="">Select Alumni...</option>
                            <?php foreach($alumniList as $al): ?>
                                <option value="<?= $al['id'] ?>">
                                    <?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?> (Class of <?= (int)$al['passout_year'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Choose Event <span class="text-danger">*</span></label>
                        <select name="event_id" class="form-select" required>
                            <option value="">Select Event...</option>
                            <?php foreach($events as $ev): ?>
                                <option value="<?= $ev['id'] ?>">
                                    <?= htmlspecialchars($ev['title']) ?> (<?= date('d M Y', strtotime($ev['event_date'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="register_alumni" class="btn btn-primary w-100">
                        <i class="fa fa-clipboard-check me-1"></i> Register RSVP
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Event List and Registrations -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Scheduled Meets & RSVPs</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($events) > 0): ?>
                    <div class="accordion accordion-flush" id="eventsAccordion">
                        <?php foreach($events as $index => $ev): 
                            $regs = getRegistrations($ev['id'], $pdo);
                        ?>
                            <div class="accordion-item border-bottom">
                                <h2 class="accordion-header" id="heading_<?= $ev['id'] ?>">
                                    <button class="accordion-button collapsed py-4 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $ev['id'] ?>" aria-expanded="false" aria-controls="collapse_<?= $ev['id'] ?>">
                                        <div class="d-flex justify-content-between w-100 align-items-center me-3">
                                            <div>
                                                <span class="fw-bold text-dark fs-6 d-block"><?= htmlspecialchars($ev['title']) ?></span>
                                                <span class="small text-muted"><i class="fa fa-calendar me-1"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                                <?= count($regs) ?> Registered
                                            </span>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse_<?= $ev['id'] ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= $ev['id'] ?>" data-bs-parent="#eventsAccordion">
                                    <div class="accordion-body px-4 pb-4">
                                        <p class="text-muted whitespace-pre-line"><?= htmlspecialchars($ev['description'] ?: 'No description provided.') ?></p>
                                        
                                        <h6 class="fw-bold text-dark mt-4 mb-2">Registered Attendees</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Passout Class</th>
                                                        <th class="text-end">RSVP Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (count($regs) > 0): ?>
                                                        <?php foreach($regs as $r): ?>
                                                            <tr>
                                                                <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                                                <td>Class of <?= (int)$r['passout_year'] ?></td>
                                                                <td class="text-end text-muted small">Registered</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted small py-3">No RSVPs recorded yet.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-calendar-times fs-2 mb-2 d-block"></i>
                        No alumni meetups scheduled.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
