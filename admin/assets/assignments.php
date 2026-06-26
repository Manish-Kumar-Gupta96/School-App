<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_asset') {
        $asset_id = (int)$_POST['asset_id'];
        $employee_id = (int)$_POST['employee_id'];
        $assigned_date = $_POST['assigned_date'] ?: date('Y-m-d');
        $remarks = trim($_POST['remarks']);
        
        if ($asset_id > 0 && $employee_id > 0) {
            $stmt = $pdo->prepare("INSERT INTO asset_assignments (asset_id, employee_id, assigned_date, remarks) VALUES (?, ?, ?, ?)");
            $stmt->execute([$asset_id, $employee_id, $assigned_date, $remarks]);
            $_SESSION['success'] = "Asset assigned successfully.";
        } else {
            $_SESSION['error'] = "Asset and Employee are required.";
        }
        header("Location: assignments.php");
        exit;
    }
}

// Fetch active assets that are not currently assigned
$available_assets = $pdo->query("
    SELECT * FROM assets 
    WHERE status = 'active' 
      AND id NOT IN (SELECT asset_id FROM asset_assignments)
    ORDER BY asset_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch employees (assume from a simple query or teachers table if HRMS not fully mapped)
// For simplicity, we fetch teachers and admins to act as employees
$employees = $pdo->query("
    SELECT id, name, 'Teacher' as role FROM teachers
    UNION ALL
    SELECT id, name, 'Admin' as role FROM admins
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Assignments
$assignments = $pdo->query("
    SELECT aa.*, a.asset_name, a.asset_code, a.category,
           COALESCE(t.name, ad.name, 'Unknown') as employee_name
    FROM asset_assignments aa
    JOIN assets a ON aa.asset_id = a.id
    LEFT JOIN teachers t ON aa.employee_id = t.id
    LEFT JOIN admins ad ON aa.employee_id = ad.id
    ORDER BY aa.assigned_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Asset Assignments | VIC School ERP";
$page_header = "Assign Assets";
$active_menu = "assets";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Assign Asset Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa fa-user-tag me-2"></i>Assign Asset</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="action" value="assign_asset">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Asset <span class="text-danger">*</span></label>
                            <select name="asset_id" class="form-select" required>
                                <option value="">Select Available Asset</option>
                                <?php foreach($available_assets as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['asset_code'] . ' - ' . $a['asset_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(count($available_assets) == 0): ?>
                                <small class="text-danger">No unassigned active assets available.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">Select Employee</option>
                                <?php foreach($employees as $e): ?>
                                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?> (<?= $e['role'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Assigned Date</label>
                            <input type="date" name="assigned_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Condition upon assignment..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100" <?= count($available_assets) == 0 ? 'disabled' : '' ?>><i class="fa fa-check me-2"></i> Confirm Assignment</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assignment List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Current Assignments</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Asset Details</th>
                                    <th>Assigned To</th>
                                    <th>Assigned Date</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($assignments) > 0): ?>
                                    <?php foreach ($assignments as $a): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($a['asset_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($a['asset_code']) ?></div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary"><i class="fa fa-user-tie me-2"></i><?= htmlspecialchars($a['employee_name']) ?></div>
                                            </td>
                                            <td><?= date('d M Y', strtotime($a['assigned_date'])) ?></td>
                                            <td class="text-muted small"><?= htmlspecialchars($a['remarks'] ?: '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted">No assignments found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
