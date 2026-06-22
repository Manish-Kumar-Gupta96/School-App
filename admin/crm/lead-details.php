<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$lead_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Lead Core Info
$stmt = $pdo->prepare("SELECT * FROM crm_leads WHERE id = ? AND school_id = ?");
$stmt->execute([$lead_id, CURRENT_SCHOOL_ID]);
$lead = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lead) {
    header("Location: leads.php?error=" . urlencode("Lead not found."));
    exit;
}

$success = '';
$error = '';

// Handle Note Addition
if (isset($_POST['add_note'])) {
    $note = sanitize($_POST['note'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($note)) {
        $error = 'Note text cannot be empty.';
    } else {
        try {
            $stmt_note = $pdo->prepare("INSERT INTO crm_notes (lead_id, note) VALUES (?, ?)");
            $stmt_note->execute([$lead_id, $note]);
            $success = 'Note added successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving note: ' . $e->getMessage();
        }
    }
}

// Handle Follow-up Log
if (isset($_POST['add_followup'])) {
    $followup_date = sanitize($_POST['followup_date'] ?? '');
    $response = sanitize($_POST['response'] ?? '');
    $method = sanitize($_POST['method'] ?? 'Call');
    $next_followup_date = sanitize($_POST['next_followup_date'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($followup_date) || empty($response)) {
        $error = 'Followup date and response description are required.';
    } else {
        try {
            $next_date = empty($next_followup_date) ? null : $next_followup_date;
            $stmt_f = $pdo->prepare("INSERT INTO crm_followups (lead_id, followup_date, response, method, next_followup_date) VALUES (?, ?, ?, ?, ?)");
            $stmt_f->execute([$lead_id, $followup_date, $response, $method, $next_date]);
            
            // Auto update status if selected in follow-up form
            if (!empty($_POST['update_status_to'])) {
                $new_status = sanitize($_POST['update_status_to']);
                $stmt_stat = $pdo->prepare("UPDATE crm_leads SET status = ? WHERE id = ?");
                $stmt_stat->execute([$new_status, $lead_id]);
                $lead['status'] = $new_status; // Reflect update instantly
            }
            
            $success = 'Interaction logged successfully.';
        } catch (PDOException $e) {
            $error = 'Error saving interaction: ' . $e->getMessage();
        }
    }
}

// Handle Task addition
if (isset($_POST['add_task'])) {
    $task_title = sanitize($_POST['task_title'] ?? '');
    $task_desc = sanitize($_POST['task_desc'] ?? '');
    $due_date = sanitize($_POST['due_date'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($task_title) || empty($due_date)) {
        $error = 'Task title and due date are required.';
    } else {
        try {
            $stmt_t = $pdo->prepare("INSERT INTO crm_tasks (lead_id, task_title, task_desc, due_date, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt_t->execute([$lead_id, $task_title, $task_desc, $due_date]);
            $success = 'Task scheduled successfully.';
        } catch (PDOException $e) {
            $error = 'Error scheduling task: ' . $e->getMessage();
        }
    }
}

// Handle Task Toggle Status
if (isset($_GET['complete_task']) && isset($_GET['task_id'])) {
    $task_id = (int)$_GET['task_id'];
    $submitted_token = $_GET['csrf'] ?? '';
    
    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } else {
        try {
            $stmt_tc = $pdo->prepare("UPDATE crm_tasks SET status = 'Completed' WHERE id = ? AND lead_id = ?");
            $stmt_tc->execute([$task_id, $lead_id]);
            $success = 'Task completed successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating task: ' . $e->getMessage();
        }
    }
}

// Handle Core Status update
if (isset($_POST['update_core_status'])) {
    $new_status = sanitize($_POST['status'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($new_status)) {
        $error = 'Status cannot be empty.';
    } else {
        try {
            $stmt_stat = $pdo->prepare("UPDATE crm_leads SET status = ? WHERE id = ?");
            $stmt_stat->execute([$new_status, $lead_id]);
            $lead['status'] = $new_status;
            $success = 'Status updated successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating status: ' . $e->getMessage();
        }
    }
}

// Fetch Notes
$notes = $pdo->prepare("SELECT * FROM crm_notes WHERE lead_id = ? ORDER BY id DESC");
$notes->execute([$lead_id]);
$notes_list = $notes->fetchAll(PDO::FETCH_ASSOC);

// Fetch Follow-ups
$followups = $pdo->prepare("SELECT * FROM crm_followups WHERE lead_id = ? ORDER BY followup_date DESC");
$followups->execute([$lead_id]);
$followups_list = $followups->fetchAll(PDO::FETCH_ASSOC);

// Fetch Tasks
$tasks = $pdo->prepare("SELECT * FROM crm_tasks WHERE lead_id = ? ORDER BY due_date ASC");
$tasks->execute([$lead_id]);
$tasks_list = $tasks->fetchAll(PDO::FETCH_ASSOC);

// Combine Notes & Follow-ups chronologically for unified timeline
$timeline = [];
foreach ($notes_list as $n) {
    $timeline[] = [
        'type' => 'note',
        'date' => $n['created_at'],
        'content' => $n['note'],
        'badge' => 'Internal Note',
        'badge_class' => 'bg-secondary'
    ];
}
foreach ($followups_list as $f) {
    $timeline[] = [
        'type' => 'interaction',
        'date' => $f['followup_date'],
        'content' => $f['response'],
        'badge' => $f['method'] . ' Log',
        'badge_class' => 'bg-info text-dark',
        'next_date' => $f['next_followup_date']
    ];
}
// Sort timeline descending
usort($timeline, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});

// Setup WA Pre-formatted message link
$clean_phone = preg_replace('/[^0-9]/', '', $lead['phone']);
if (strlen($clean_phone) === 10) {
    $clean_phone = '91' . $clean_phone; // Default to India prefix
}
$wa_template_1 = rawurlencode("Hello " . $lead['name'] . ", Thank you for your inquiry at VIC School. We tried reaching out to you. Please let us know a suitable time to connect regarding admissions. Regards, Admissions Desk.");
$wa_link = "https://wa.me/" . $clean_phone . "?text=" . $wa_template_1;

$page_title = "Lead Profile: " . htmlspecialchars($lead['name']) . " | VIC ERP";
$page_header = "CRM Candidate Profile";
$active_menu = "crm";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <!-- Back Navigation -->
    <div class="mb-3 text-start">
        <a href="leads.php" class="btn btn-sm btn-light"><i class="fa fa-arrow-left me-1"></i> Back to Directory</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show small text-start" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small text-start" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 text-start">
        <!-- LEFT PANEL: Candidate Profile Overview -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                <div class="text-center mb-4">
                    <div class="d-inline-flex justify-content-center align-items-center bg-primary text-white fw-bold rounded-circle mb-3" style="width: 70px; height: 70px; font-size: 28px;">
                        <?= strtoupper(substr($lead['name'], 0, 1)) ?>
                    </div>
                    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($lead['name']) ?></h4>
                    <span class="badge bg-light text-muted border"><?= htmlspecialchars($lead['source']) ?> Lead</span>
                </div>

                <hr class="text-muted">

                <div class="mb-3">
                    <small class="text-muted fw-semibold d-block">Phone Number</small>
                    <span class="fw-bold text-dark"><?= htmlspecialchars($lead['phone']) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-semibold d-block">Email Address</small>
                    <span class="text-dark"><?= htmlspecialchars($lead['email'] ?: 'N/A') ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-semibold d-block">Class Applied</small>
                    <span class="badge bg-secondary"><?= htmlspecialchars($lead['class_applied'] ?: 'Not Specified') ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-semibold d-block">Created On</small>
                    <span class="text-dark small"><?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted fw-semibold d-block">Original Message/Query</small>
                    <p class="text-secondary small bg-light p-2 rounded mb-0" style="white-space: pre-line;"><?= htmlspecialchars($lead['message'] ?: 'No message attached.') ?></p>
                </div>

                <hr class="text-muted">

                <!-- Quick Pipeline stage update -->
                <form method="POST" class="mb-3">
                    <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                    <label class="form-label fw-bold small text-muted">Pipeline Funnel Stage</label>
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <option value="New Lead" <?= $lead['status'] === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
                            <option value="Contacted" <?= $lead['status'] === 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                            <option value="Interested" <?= $lead['status'] === 'Interested' ? 'selected' : '' ?>>Interested</option>
                            <option value="Visit Scheduled" <?= $lead['status'] === 'Visit Scheduled' ? 'selected' : '' ?>>Visit Scheduled</option>
                            <option value="Admission Confirmed" <?= $lead['status'] === 'Admission Confirmed' ? 'selected' : '' ?>>Admission Confirmed</option>
                        </select>
                        <button type="submit" name="update_core_status" class="btn btn-primary">Update</button>
                    </div>
                </form>

                <!-- Quick marketing helpers -->
                <div class="mt-4">
                    <h6 class="fw-bold small text-muted mb-2">Lead Engagement Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="<?= $wa_link ?>" target="_blank" class="btn btn-success fw-semibold btn-sm">
                            <i class="fab fa-whatsapp me-1"></i> Send WhatsApp Notice
                        </a>
                        <?php if (!empty($lead['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($lead['email']) ?>?subject=Admissions Inquiry - VIC School" class="btn btn-outline-info text-dark fw-semibold btn-sm">
                                <i class="fa fa-envelope me-1"></i> Send Direct Email
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Action timeline logging & task schedules -->
        <div class="col-lg-8">
            <!-- Tabs panel toggles -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-0">
                    <ul class="nav nav-tabs border-0" id="crmDetailTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-3 px-4 fw-semibold border-0 rounded-0" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline-content" type="button" role="tab" aria-controls="timeline-content" aria-selected="true">
                                <i class="fa fa-clock-rotate-left me-1"></i> Interaction Timeline
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 px-4 fw-semibold border-0 rounded-0" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks-content" type="button" role="tab" aria-controls="tasks-content" aria-selected="false">
                                <i class="fa fa-check-double me-1"></i> Reminders & Tasks (<?= count(array_filter($tasks_list, function($t){return $t['status'] === 'Pending';})) ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-3 px-4 fw-semibold border-0 rounded-0" id="log-tab" data-bs-toggle="tab" data-bs-target="#log-content" type="button" role="tab" aria-controls="log-content" aria-selected="false">
                                <i class="fa fa-pen-nib me-1"></i> Log Interaction
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Tab Content Sheets -->
            <div class="tab-content" id="crmDetailTabsContent">
                
                <!-- Tab 1: Chronological Interaction list -->
                <div class="tab-pane fade show active" id="timeline-content" role="tabpanel" aria-labelledby="timeline-tab">
                    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                        <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-bars-staggered me-2 text-primary"></i>Interaction History</h5>
                        
                        <?php if (count($timeline) > 0): ?>
                            <div class="position-relative border-start border-2 border-light ps-4 text-start ms-2">
                                <?php foreach ($timeline as $item): ?>
                                    <div class="mb-4 position-relative">
                                        <!-- Timeline circle marker -->
                                        <div class="position-absolute bg-white rounded-circle border border-primary" style="width: 12px; height: 12px; left: -31px; top: 6px;"></div>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div>
                                                <span class="badge <?= $item['badge_class'] ?> small"><?= $item['badge'] ?></span>
                                                <small class="text-muted ms-2"><?= date('d M Y, h:i A', strtotime($item['date'])) ?></small>
                                            </div>
                                        </div>
                                        <p class="text-dark bg-light p-3 rounded small mb-2" style="white-space: pre-line;"><?= htmlspecialchars($item['content']) ?></p>
                                        
                                        <?php if (!empty($item['next_date'])): ?>
                                            <div class="text-danger small fw-semibold"><i class="fa fa-bell me-1"></i>Next Scheduled Call: <?= date('d M Y, h:i A', strtotime($item['next_date'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fa fa-clock fs-1 mb-2 text-light"></i><br>
                                No followups or notes have been logged for this lead yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab 2: Reminders & Tasks scheduler -->
                <div class="tab-pane fade" id="tasks-content" role="tabpanel" aria-labelledby="tasks-tab">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-list-check me-2 text-primary"></i>Task Checklist</h5>
                                
                                <?php if (count($tasks_list) > 0): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($tasks_list as $t): 
                                            $is_pending = $t['status'] === 'Pending';
                                        ?>
                                            <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-start bg-transparent">
                                                <div>
                                                    <span class="badge <?= $is_pending ? 'bg-warning text-dark' : 'bg-success' ?> small mb-2"><?= $t['status'] ?></span>
                                                    <h6 class="fw-bold mb-1 text-dark <?= !$is_pending ? 'text-decoration-line-through text-muted' : '' ?>"><?= htmlspecialchars($t['task_title']) ?></h6>
                                                    <p class="text-muted small mb-1"><?= htmlspecialchars($t['task_desc']) ?></p>
                                                    <small class="text-danger fw-semibold d-block"><i class="fa fa-calendar-days me-1"></i>Due: <?= date('d M Y, h:i A', strtotime($t['due_date'])) ?></small>
                                                </div>
                                                <?php if ($is_pending): ?>
                                                    <a href="?id=<?= $lead_id ?>&complete_task=1&task_id=<?= $t['id'] ?>&csrf=<?= csrf() ?>" class="btn btn-sm btn-success fw-semibold">Complete</a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fa fa-tasks fs-1 mb-2 text-light"></i><br>
                                        No tasks or reminders assigned.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-plus me-1 text-primary"></i>Schedule Task</h5>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Task Title *</label>
                                        <input type="text" name="task_title" class="form-control" placeholder="e.g. Schedule school campus tour" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Description</label>
                                        <textarea class="form-control" name="task_desc" rows="3" placeholder="Additional task specifics..."></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Due Date *</label>
                                        <input type="datetime-local" name="due_date" class="form-control" required>
                                    </div>
                                    <button type="submit" name="add_task" class="btn btn-primary w-100 fw-semibold mt-2">Schedule Reminder</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Logging Interaction Form -->
                <div class="tab-pane fade" id="log-content" role="tabpanel" aria-labelledby="log-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-phone-volume me-2 text-primary"></i>Log Call / Message</h5>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Interaction Method</label>
                                        <select class="form-select" name="method">
                                            <option>Call</option>
                                            <option>WhatsApp</option>
                                            <option>Email</option>
                                            <option>In-Person</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Interaction Date *</label>
                                        <input type="datetime-local" name="followup_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Response / Call Description *</label>
                                        <textarea class="form-control" name="response" rows="3" placeholder="Parent replied saying they will visit the school on Monday." required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Next Scheduled Follow-up (Optional)</label>
                                        <input type="datetime-local" name="next_followup_date" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Progress Funnel Status To</label>
                                        <select class="form-select" name="update_status_to">
                                            <option value="">-- Don't Change Status --</option>
                                            <option value="New Lead">New Lead</option>
                                            <option value="Contacted">Contacted</option>
                                            <option value="Interested">Interested</option>
                                            <option value="Visit Scheduled">Visit Scheduled</option>
                                            <option value="Admission Confirmed">Admission Confirmed</option>
                                        </select>
                                    </div>
                                    <button type="submit" name="add_followup" class="btn btn-primary w-100 fw-semibold">Save Interaction</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                                <h5 class="fw-bold mb-4 text-dark"><i class="fa fa-note-sticky me-2 text-primary"></i>Add Internal Note</h5>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small text-muted">Note Content *</label>
                                        <textarea class="form-control" name="note" rows="5" placeholder="Write internal admin notes here..." required></textarea>
                                    </div>
                                    <button type="submit" name="add_note" class="btn btn-secondary w-100 fw-semibold">Save Administrative Note</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
