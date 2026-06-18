<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Log Details | Audit Control";
$active_menu = "audit";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM audit_logs WHERE id = ?");
$stmt->execute([$id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    echo "<div class='alert alert-danger'>Log record not found.</div>";
    require_once('../includes/footer.php');
    exit;
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔍 Audit Log Details</h2>
            <p class="text-muted mb-0">Detailed inspect view of recorded security audit item #<?= $log['id'] ?></p>
        </div>
        <a href="logs.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Logs
        </a>
    </div>

    <div class="row g-4">
        <!-- Log Summary Info -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; height: 100%;">
                <h5 class="fw-bold text-dark mb-4 border-bottom pb-2 text-primary"><i class="fa fa-info-circle me-2"></i>Action Context</h5>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">User Name</label>
                    <span class="fs-5 fw-semibold text-dark"><?= htmlspecialchars($log['user_name'] ?: 'Guest / Public') ?></span>
                </div>
                
                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">User ID</label>
                    <span class="fs-6 text-secondary"><?= (int)$log['user_id'] ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Role Access Level</label>
                    <span class="badge bg-primary px-3 py-2"><?= htmlspecialchars($log['role_name'] ?: 'N/A') ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Module Area</label>
                    <span class="badge bg-light text-dark border px-3 py-2"><?= htmlspecialchars($log['module_name'] ?: 'System') ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Record ID Reference</label>
                    <span class="fs-6 text-dark font-monospace"><?= $log['record_id'] ? htmlspecialchars($log['record_id']) : 'None' ?></span>
                </div>
            </div>
        </div>

        <!-- System & Connection Details -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; height: 100%;">
                <h5 class="fw-bold text-dark mb-4 border-bottom pb-2 text-primary"><i class="fa fa-laptop me-2"></i>Security & Network Context</h5>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Action Performed</label>
                    <span class="fs-5 fw-bold text-danger"><?= htmlspecialchars($log['action']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">IP Address</label>
                    <span class="fs-6 font-monospace text-dark bg-light p-2 rounded border d-inline-block mt-1"><?= htmlspecialchars($log['ip_address']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">Timestamp (UTC/Server)</label>
                    <span class="fs-6 text-secondary"><i class="fa fa-clock me-1"></i> <?= htmlspecialchars($log['created_at']) ?></span>
                </div>

                <div class="mb-3">
                    <label class="text-muted small fw-bold uppercase d-block">User Agent Signature</label>
                    <textarea class="form-control font-monospace mt-1" rows="4" readonly style="font-size: 0.85rem; background-color: #f8f9fa; color: #495057; border: 1px solid #ced4da;"><?= htmlspecialchars($log['user_agent']) ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
