<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

// Fetch Audit Logs
$logs = $pdo->prepare("
    SELECT a.*, 
           COALESCE(u.name, 'System') as user_name
    FROM audit_logs a
    LEFT JOIN admins u ON a.user_id = u.id
    WHERE a.school_id = ?
    ORDER BY a.created_at DESC
    LIMIT 200
");
$logs->execute([$school_id]);
$audit_logs = $logs->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Audit Logs | VIC School ERP";
$page_header = "System Audit Trail";
$active_menu = "security";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
            <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Audit Log Viewer</h5>
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search logs...">
                <button class="btn btn-sm btn-outline-secondary"><i class="fa fa-filter"></i></button>
                <button class="btn btn-sm btn-outline-success"><i class="fa fa-download"></i> Export</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="auditTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Timestamp</th>
                            <th>Actor</th>
                            <th>Module</th>
                            <th>Action Details</th>
                            <th>IP Address</th>
                            <th class="pe-4 text-end">Changes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($audit_logs) > 0): ?>
                            <?php foreach ($audit_logs as $log): ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?= date('d M Y, h:i:s A', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['user_name']) ?></div>
                                        <div class="text-muted small">ID: <?= $log['user_id'] ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($log['module']) ?></span></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td class="text-muted font-monospace small"><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td class="pe-4 text-end">
                                        <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#diffModal<?= $log['id'] ?>">View Diff</button>
                                        <?php else: ?>
                                            <span class="text-muted small">No Diff</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Diff Modal -->
                                <?php if (!empty($log['old_value']) || !empty($log['new_value'])): ?>
                                <div class="modal fade" id="diffModal<?= $log['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Data Changes</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-0">
                                                <div class="row g-0">
                                                    <div class="col-6 border-end p-3 bg-light">
                                                        <h6 class="fw-bold text-danger">Old Value</h6>
                                                        <pre class="small text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($log['old_value']) ?></pre>
                                                    </div>
                                                    <div class="col-6 p-3 bg-light">
                                                        <h6 class="fw-bold text-success">New Value</h6>
                                                        <pre class="small text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($log['new_value']) ?></pre>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No audit events recorded.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#auditTable tbody tr');
    rows.forEach(row => { 
        if(!row.classList.contains('modal')) {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; 
        }
    });
});
</script>

<?php require_once('../includes/footer.php'); ?>
