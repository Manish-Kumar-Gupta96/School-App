<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');
require_once($root_path . 'helpers/security.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$success = '';
$error = '';

// Handle Delete Lead
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM crm_leads WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Lead deleted successfully.';
    } catch (PDOException $e) {
        $error = 'Error deleting lead: ' . $e->getMessage();
    }
}

// Handle Add Lead
if (isset($_POST['add_lead'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $class_applied = sanitize($_POST['class_applied'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    $source = sanitize($_POST['source'] ?? 'Manual');
    $status = sanitize($_POST['status'] ?? 'New Lead');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($name) || empty($phone)) {
        $error = 'Name and Phone fields are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO crm_leads (school_id, name, email, phone, class_applied, message, source, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([CURRENT_SCHOOL_ID, $name, $email, $phone, $class_applied, $message, $source, $status]);
            $success = 'Lead added successfully.';
        } catch (PDOException $e) {
            $error = 'Error adding lead: ' . $e->getMessage();
        }
    }
}

// Handle Quick Status Change
if (isset($_POST['update_status'])) {
    $id = (int)$_POST['lead_id'];
    $status = sanitize($_POST['status'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = 'CSRF verification failed.';
    } elseif (empty($status)) {
        $error = 'Status cannot be empty.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE crm_leads SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $success = 'Lead status updated successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating status: ' . $e->getMessage();
        }
    }
}

// Fetch stats
$stat_new = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status IN ('new', 'New Lead')")->fetchColumn();
$stat_contacted = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status IN ('contacted', 'Contacted')")->fetchColumn();
$stat_interested = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status IN ('interested', 'Interested')")->fetchColumn();
$stat_confirmed = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads WHERE status IN ('admission_confirmed', 'Admission Confirmed')")->fetchColumn();
$stat_total = (int)$pdo->query("SELECT COUNT(*) FROM crm_leads")->fetchColumn();

// Setup Filters
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');
$source_filter = sanitize($_GET['source'] ?? '');

$query_str = "SELECT * FROM crm_leads WHERE school_id = " . CURRENT_SCHOOL_ID;
$params = [];

if (!empty($search)) {
    $query_str .= " AND (name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($status_filter)) {
    if ($status_filter === 'New Lead') {
        $query_str .= " AND status IN ('New Lead', 'new')";
    } elseif ($status_filter === 'Contacted') {
        $query_str .= " AND status IN ('Contacted', 'contacted')";
    } elseif ($status_filter === 'Interested') {
        $query_str .= " AND status IN ('Interested', 'interested')";
    } elseif ($status_filter === 'Visit Scheduled') {
        $query_str .= " AND status IN ('Visit Scheduled', 'visit_scheduled')";
    } elseif ($status_filter === 'Admission Confirmed') {
        $query_str .= " AND status IN ('Admission Confirmed', 'admission_confirmed')";
    } else {
        $query_str .= " AND status = ?";
        $params[] = $status_filter;
    }
}
if (!empty($source_filter)) {
    $query_str .= " AND source = ?";
    $params[] = $source_filter;
}

$query_str .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = "CRM Leads | VIC ERP";
$page_header = "CRM Leads Management";
$active_menu = "crm";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 text-start">
        <div>
            <h2 class="fw-bold text-dark mb-1">CRM Admissions Funnel</h2>
            <p class="text-muted small mb-0">Track and nurture admission callback leads & public enquiries</p>
        </div>
        <div class="d-flex gap-2">
            <a href="analytics.php" class="btn btn-outline-primary fw-semibold"><i class="fa fa-chart-line me-1"></i> Funnel Analytics</a>
            <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                <i class="fa fa-user-plus me-1"></i> Add Manual Lead
            </button>
        </div>
    </div>

    <!-- Stats summary rows -->
    <div class="row g-3 mb-4 text-start">
        <div class="col-lg col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm p-3 card-hover" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <span class="text-muted small text-uppercase fw-semibold">Total Leads</span>
                <h3 class="fw-bold mb-0 text-dark"><?= $stat_total ?></h3>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm p-3 card-hover" style="border-radius: 12px; border-left: 4px solid #6610f2 !important;">
                <span class="text-muted small text-uppercase fw-semibold">New</span>
                <h3 class="fw-bold mb-0 text-dark"><?= $stat_new ?></h3>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm p-3 card-hover" style="border-radius: 12px; border-left: 4px solid #0dcaf0 !important;">
                <span class="text-muted small text-uppercase fw-semibold">Contacted</span>
                <h3 class="fw-bold mb-0 text-dark"><?= $stat_contacted ?></h3>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm p-3 card-hover" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
                <span class="text-muted small text-uppercase fw-semibold">Interested</span>
                <h3 class="fw-bold mb-0 text-dark"><?= $stat_interested ?></h3>
            </div>
        </div>
        <div class="col-lg col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm p-3 card-hover" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <span class="text-muted small text-uppercase fw-semibold">Confirmed</span>
                <h3 class="fw-bold mb-0 text-dark"><?= $stat_confirmed ?></h3>
            </div>
        </div>
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

    <!-- Filter Toolbar Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center text-start">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- All Statuses --</option>
                        <option value="New Lead" <?= $status_filter === 'New Lead' ? 'selected' : '' ?>>New Lead</option>
                        <option value="Contacted" <?= $status_filter === 'Contacted' ? 'selected' : '' ?>>Contacted</option>
                        <option value="Interested" <?= $status_filter === 'Interested' ? 'selected' : '' ?>>Interested</option>
                        <option value="Visit Scheduled" <?= $status_filter === 'Visit Scheduled' ? 'selected' : '' ?>>Visit Scheduled</option>
                        <option value="Admission Confirmed" <?= $status_filter === 'Admission Confirmed' ? 'selected' : '' ?>>Admission Confirmed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="source" class="form-select">
                        <option value="">-- All Sources --</option>
                        <option value="Website" <?= $source_filter === 'Website' ? 'selected' : '' ?>>Website</option>
                        <option value="Chatbot" <?= $source_filter === 'Chatbot' ? 'selected' : '' ?>>Chatbot</option>
                        <option value="Admission Portal" <?= $source_filter === 'Admission Portal' ? 'selected' : '' ?>>Admission Portal</option>
                        <option value="Manual" <?= $source_filter === 'Manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">Filter</button>
                    <a href="leads.php" class="btn btn-light w-100 fw-semibold">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Leads Table Grid -->
    <div class="card border-0 shadow-sm" style="border-radius: 15px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Student Name</th>
                        <th>Phone / Email</th>
                        <th>Class</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th class="pe-4 text-end">Action</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($leads) > 0): ?>
                        <?php foreach ($leads as $lead): 
                            $status_class = 'bg-primary';
                            $status_val = strtolower($lead['status']);
                            if ($status_val === 'contacted') $status_class = 'bg-info text-dark';
                            elseif ($status_val === 'interested') $status_class = 'bg-warning text-dark';
                            elseif ($status_val === 'visit scheduled' || $status_val === 'visit_scheduled') $status_class = 'bg-secondary';
                            elseif ($status_val === 'admission confirmed' || $status_val === 'admission_confirmed') $status_class = 'bg-success';
                            elseif ($status_val === 'rejected') $status_class = 'bg-danger';
                            
                            $source_class = 'badge bg-light text-dark border';
                            if ($lead['source'] === 'Chatbot') $source_class = 'badge bg-light text-primary border border-primary';
                            elseif ($lead['source'] === 'Admission Portal') $source_class = 'badge bg-light text-success border border-success';
                            elseif ($lead['source'] === 'Website') $source_class = 'badge bg-light text-info border border-info';
                        ?>
                            <tr>
                                <td class="ps-4 text-muted">#<?= $lead['id'] ?></td>
                                <td>
                                    <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($lead['name']) ?></h6>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars($lead['phone']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($lead['email'] ?: 'No email') ?></div>
                                </td>
                                <td><span class="badge bg-secondary text-light"><?= htmlspecialchars($lead['class_applied'] ?: 'N/A') ?></span></td>
                                <td><span class="<?= $source_class ?>"><?= htmlspecialchars($lead['source']) ?></span></td>
                                <td>
                                    <span class="badge <?= $status_class ?> p-2"><?= htmlspecialchars($lead['status']) ?></span>
                                </td>
                                <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($lead['created_at'])) ?></td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="lead-details.php?id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-primary" title="Log Follow-up / View Details">
                                            <i class="fa fa-eye"></i> Details
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="openStatusModal(<?= $lead['id'] ?>, '<?= htmlspecialchars($lead['status']) ?>')" title="Quick Status Change">
                                            <i class="fa fa-pen"></i> Status
                                        </button>
                                        <a href="?delete=1&id=<?= $lead['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this lead?')" title="Delete Lead">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fa fa-users fs-1 mb-2 text-light"></i><br>
                                No CRM leads found matching filters.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ==========================
ADD MANUAL LEAD MODAL
========================== -->
<div class="modal fade" id="addLeadModal" tabindex="-1" aria-labelledby="addLeadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                <div class="modal-header bg-white border-0 pt-4 ps-4">
                    <h5 class="modal-title fw-bold" id="addLeadModalLabel"><i class="fa fa-user-plus me-2 text-primary"></i>Create Manual Admission Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 text-start">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Candidate / Parent Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Candidate Name" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Phone Number *</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Mobile Number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="email@example.com">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Class Applied</label>
                            <select class="form-select" name="class_applied">
                                <option value="">Select Class</option>
                                <option>Nursery</option>
                                <option>LKG</option>
                                <option>UKG</option>
                                <option>Class I</option>
                                <option>Class II</option>
                                <option>Class III</option>
                                <option>Class IV</option>
                                <option>Class V</option>
                                <option>Class VI</option>
                                <option>Class VII</option>
                                <option>Class VIII</option>
                                <option>Class IX</option>
                                <option>Class X</option>
                                <option>Class XI</option>
                                <option>Class XII</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted">Lead Status</label>
                            <select class="form-select" name="status">
                                <option>New Lead</option>
                                <option>Contacted</option>
                                <option>Interested</option>
                                <option>Visit Scheduled</option>
                                <option>Admission Confirmed</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Enquiry Source</label>
                        <select class="form-select" name="source">
                            <option>Manual</option>
                            <option>Website</option>
                            <option>Chatbot</option>
                            <option>Admission Portal</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Initial Query / Remarks</label>
                        <textarea class="form-control" name="message" rows="3" placeholder="Additional candidate requirements..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 pe-4">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_lead" class="btn btn-primary fw-semibold px-4">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================
QUICK STATUS CHANGE MODAL
========================== -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                <input type="hidden" name="lead_id" id="status_lead_id">
                <div class="modal-header bg-white border-0 pt-4 ps-4">
                    <h5 class="modal-title fw-bold" id="statusModalLabel"><i class="fa fa-pen me-2 text-primary"></i>Update Lead Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 text-start">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Pipeline Stage</label>
                        <select class="form-select" name="status" id="status_select">
                            <option value="New Lead">New Lead</option>
                            <option value="Contacted">Contacted</option>
                            <option value="Interested">Interested</option>
                            <option value="Visit Scheduled">Visit Scheduled</option>
                            <option value="Admission Confirmed">Admission Confirmed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 pe-4">
                    <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_status" class="btn btn-primary fw-semibold">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openStatusModal(id, currentStatus) {
    document.getElementById('status_lead_id').value = id;
    document.getElementById('status_select').value = currentStatus;
    var myModal = new bootstrap.Modal(document.getElementById('statusModal'));
    myModal.show();
}
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
