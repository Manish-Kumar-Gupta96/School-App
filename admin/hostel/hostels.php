<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Hostel
if (isset($_POST['save_hostel'])) {
    $hostel_name = trim($_POST['hostel_name']);
    $hostel_type = $_POST['hostel_type'];
    $total_rooms = (int)$_POST['total_rooms'];
    $warden_id   = !empty($_POST['warden_id']) ? (int)$_POST['warden_id'] : null;

    if (empty($hostel_name) || empty($hostel_type) || $total_rooms <= 0) {
        $error = "Hostel Name, Type, and Total Rooms are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO hostels (school_id, hostel_name, hostel_type, total_rooms, warden_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $hostel_name,
            $hostel_type,
            $total_rooms,
            $warden_id
        ]);
        $message = "Hostel Building registered successfully!";
    }
}

// Fetch Teachers for Warden Dropdown
$stmt_t = $pdo->prepare("SELECT id, name, employee_id FROM teachers WHERE school_id = ? ORDER BY name ASC");
$stmt_t->execute([CURRENT_SCHOOL_ID]);
$teachers = $stmt_t->fetchAll(PDO::FETCH_ASSOC);

// Fetch Hostels with Warden Details
$stmt_h = $pdo->prepare("
    SELECT h.*, t.name AS warden_name 
    FROM hostels h 
    LEFT JOIN teachers t ON h.warden_id = t.id 
    WHERE h.school_id = ? 
    ORDER BY h.id DESC
");
$stmt_h->execute([CURRENT_SCHOOL_ID]);
$hostels = $stmt_h->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Hostel Management | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Configure hostel buildings and wardens</h5>
    <div class="d-flex gap-2">
        <a href="hostels.php" class="btn btn-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="beds.php" class="btn btn-outline-primary">
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
    <!-- Register Hostel -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Register Hostel</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hostel Name / Building <span class="text-danger">*</span></label>
                        <input type="text" name="hostel_name" class="form-control" placeholder="e.g. Tagore Boys Hostel" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hostel Type <span class="text-danger">*</span></label>
                        <select name="hostel_type" class="form-select" required>
                            <option value="boys">Boys</option>
                            <option value="girls">Girls</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Total Rooms <span class="text-danger">*</span></label>
                        <input type="number" name="total_rooms" class="form-control" min="1" placeholder="e.g. 50" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Warden (Teacher)</label>
                        <select name="warden_id" class="form-select">
                            <option value="">Choose Warden...</option>
                            <?php foreach($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['employee_id']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="save_hostel" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Building
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- List Hostels -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Hostel Buildings</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Hostel / Building</th>
                                <th>Type</th>
                                <th>Total Rooms</th>
                                <th>Warden</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($hostels) > 0): ?>
                                <?php foreach($hostels as $h): ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold text-dark">
                                            <i class="fa fa-building text-primary me-2"></i>
                                            <?= htmlspecialchars($h['hostel_name']) ?>
                                        </td>
                                        <td>
                                            <?php if ($h['hostel_type'] === 'boys'): ?>
                                                <span class="badge bg-primary-subtle text-primary"><i class="fa fa-mars me-1"></i> Boys</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger"><i class="fa fa-venus me-1"></i> Girls</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$h['total_rooms'] ?> Rooms</td>
                                        <td>
                                            <i class="fa fa-user-shield text-muted me-1"></i>
                                            <?= htmlspecialchars($h['warden_name'] ?: 'Not Assigned') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-hotel fs-2 mb-2 d-block"></i>
                                        No hostels configured.
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
