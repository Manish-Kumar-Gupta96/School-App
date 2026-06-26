<?php
require_once('../config/database.php');
require_once('../includes/auth.php');

if ($_SESSION['role'] !== 'guard' && $_SESSION['role'] !== 'admin') {
    die("Access Denied.");
}

$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;
$message = '';
$error = '';

// Handle Gate Pass Generation
if (isset($_POST['generate_pass'])) {
    $entry_id = (int)$_POST['visitor_entry_id'];
    
    // Generate unique pass no
    $pass_no = "VIS-" . date('Y') . "-" . str_pad($entry_id, 5, '0', STR_PAD_LEFT);
    $valid_until = date('Y-m-d H:i:s', strtotime('+4 hours')); // Valid for 4 hours
    $qr_data = $pass_no;
    
    try {
        $pdo->beginTransaction();
        
        // Check if gate pass already exists
        $stmt_check = $pdo->prepare("SELECT id FROM gate_passes WHERE visitor_entry_id = ?");
        $stmt_check->execute([$entry_id]);
        if (!$stmt_check->fetchColumn()) {
            $stmt_ins = $pdo->prepare("INSERT INTO gate_passes (school_id, visitor_entry_id, qr_code, gate_pass_no, valid_until) VALUES (?, ?, ?, ?, ?)");
            $stmt_ins->execute([$schoolId, $entry_id, $qr_data, $pass_no, $valid_until]);
        }
        
        $pdo->commit();
        $message = "Gate Pass generated successfully.";
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error generating pass: " . $e->getMessage();
    }
}

// Handle Exit Scan
if (isset($_POST['scan_exit'])) {
    $scan_code = trim($_POST['gate_pass_no']);
    
    try {
        $pdo->beginTransaction();
        
        // Find gate pass
        $stmt_find = $pdo->prepare("
            SELECT gp.visitor_entry_id, ve.visitor_id 
            FROM gate_passes gp
            JOIN visitor_entries ve ON gp.visitor_entry_id = ve.id
            WHERE gp.gate_pass_no = ? AND ve.school_id = ? AND ve.exit_time IS NULL
        ");
        $stmt_find->execute([$scan_code, $schoolId]);
        $res = $stmt_find->fetch(PDO::FETCH_ASSOC);
        
        if ($res) {
            $entry_id = $res['visitor_entry_id'];
            $visitor_id = $res['visitor_id'];
            
            // Mark exit time
            $stmt_exit = $pdo->prepare("UPDATE visitor_entries SET exit_time = NOW(), status = 'completed' WHERE id = ?");
            $stmt_exit->execute([$entry_id]);
            
            // Log in history
            $stmt_hist = $pdo->prepare("INSERT INTO visitor_history (school_id, visitor_id, visit_date, remarks) VALUES (?, ?, CURDATE(), 'Visit completed via QR Exit Scan')");
            $stmt_hist->execute([$schoolId, $visitor_id]);
            
            $pdo->commit();
            $message = "Exit verified successfully. Goodbye!";
        } else {
            $pdo->rollBack();
            $error = "Invalid Gate Pass or visitor already exited.";
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = "Error verifying exit: " . $e->getMessage();
    }
}

// Fetch Approved Entries without Gate Pass
$stmt_app = $pdo->prepare("
    SELECT ve.*, v.visitor_name, v.mobile_no 
    FROM visitor_entries ve
    JOIN visitors v ON ve.visitor_id = v.id
    LEFT JOIN gate_passes gp ON ve.id = gp.visitor_entry_id
    WHERE ve.school_id = ? AND ve.status = 'approved' AND gp.id IS NULL
");
$stmt_app->execute([$schoolId]);
$needs_pass = $stmt_app->fetchAll(PDO::FETCH_ASSOC);

// Fetch Active Gate Passes
$stmt_active = $pdo->prepare("
    SELECT gp.*, v.visitor_name, v.mobile_no, ve.person_to_meet_type, ve.person_to_meet_id
    FROM gate_passes gp
    JOIN visitor_entries ve ON gp.visitor_entry_id = ve.id
    JOIN visitors v ON ve.visitor_id = v.id
    WHERE gp.school_id = ? AND ve.exit_time IS NULL AND ve.status = 'approved'
");
$stmt_active->execute([$schoolId]);
$active_passes = $stmt_active->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../";
$page_title = "Gate Pass Management | Security Portal";
$page_header = "Security Management";
$active_menu = "gate_pass";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Gate Pass & Exit Scanner</h5>
    <div class="d-flex gap-2">
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Dashboard</a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Scanner Section -->
    <div class="col-lg-4">
        <div class="card shadow border-0 bg-light" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <div class="mb-4">
                    <i class="fa fa-barcode fa-4x text-muted opacity-50"></i>
                </div>
                <h5 class="fw-bold mb-3">Scan Gate Pass (Exit)</h5>
                <form method="POST">
                    <div class="input-group mb-3">
                        <input type="text" name="gate_pass_no" class="form-control form-control-lg" placeholder="VIS-2026-..." required autofocus>
                        <button type="submit" name="scan_exit" class="btn btn-primary"><i class="fa fa-check"></i> Verify</button>
                    </div>
                </form>
                <div class="small text-muted mt-2">
                    Use physical barcode scanner or type manually.
                </div>
            </div>
        </div>
    </div>

    <!-- Active Passes Section -->
    <div class="col-lg-8">
        <?php if(count($needs_pass) > 0): ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <h6 class="fw-bold"><i class="fa fa-bell me-2"></i> Visitors Approved (Pending Gate Pass)</h6>
                <table class="table table-sm mt-3 mb-0">
                    <tbody>
                        <?php foreach($needs_pass as $np): ?>
                            <tr>
                                <td class="align-middle fw-bold"><?= htmlspecialchars($np['visitor_name']) ?></td>
                                <td class="align-middle text-muted"><?= htmlspecialchars($np['mobile_no']) ?></td>
                                <td class="text-end">
                                    <form method="POST">
                                        <input type="hidden" name="visitor_entry_id" value="<?= $np['id'] ?>">
                                        <button type="submit" name="generate_pass" class="btn btn-sm btn-dark">Generate Pass</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0">Active Gate Passes (Inside Campus)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Pass No.</th>
                                <th>Visitor Name</th>
                                <th>Valid Until</th>
                                <th class="pe-4 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($active_passes as $ap): ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-primary"><?= htmlspecialchars($ap['gate_pass_no']) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($ap['visitor_name']) ?></div>
                                        <div class="small text-muted text-uppercase">To Meet: <?= htmlspecialchars($ap['person_to_meet_type']) ?> (<?= htmlspecialchars($ap['person_to_meet_id']) ?>)</div>
                                    </td>
                                    <td>
                                        <?php 
                                            $valid = strtotime($ap['valid_until']);
                                            $now = time();
                                        ?>
                                        <span class="<?= ($now > $valid) ? 'text-danger fw-bold' : '' ?>"><?= date('h:i A', $valid) ?></span>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="showQR('<?= htmlspecialchars($ap['gate_pass_no']) ?>', '<?= htmlspecialchars(addslashes($ap['visitor_name'])) ?>')">
                                            <i class="fa fa-qrcode"></i> View QR
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($active_passes)): ?>
                                <tr><td colspan="4" class="text-center p-5 text-muted">No active gate passes inside campus.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content text-center border-0 shadow-lg">
      <div class="modal-header border-0 pb-0 justify-content-center">
        <h6 class="modal-title fw-bold text-primary" id="qrVisitorName">Visitor Name</h6>
      </div>
      <div class="modal-body p-4">
        <div class="mb-2 text-muted small">VISITOR GATE PASS</div>
        <img id="qrImage" src="" alt="QR Code" class="img-fluid border p-2 rounded mb-3" style="width: 200px; height: 200px;">
        <div class="font-monospace fw-bold fs-5 bg-light py-2 rounded" id="qrPassNo"></div>
      </div>
      <div class="modal-footer border-0 pt-0 pb-4 justify-content-center">
        <button type="button" class="btn btn-primary px-4 rounded-pill shadow-sm" onclick="window.print()"><i class="fa fa-print me-1"></i> Print Pass</button>
        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function showQR(passNo, name) {
    document.getElementById('qrVisitorName').innerText = name;
    document.getElementById('qrPassNo').innerText = passNo;
    document.getElementById('qrImage').src = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(passNo);
    new bootstrap.Modal(document.getElementById('qrModal')).show();
}
</script>

<?php require_once('../includes/footer.php'); ?>
