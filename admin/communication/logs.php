<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch Logs
$logs = $pdo->query("
    SELECT c.*, 
           COALESCE(s.first_name, p.father_name, t.name, 'Unknown') as recipient_name,
           COALESCE(s.class, 'Parent', 'Teacher') as recipient_type
    FROM communication_logs c
    LEFT JOIN students s ON c.user_id = s.id AND c.channel != 'sms' -- Simplistic join logic
    LEFT JOIN parents p ON c.user_id = p.id
    LEFT JOIN teachers t ON c.user_id = t.id
    ORDER BY c.sent_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_sent = $pdo->query("SELECT COUNT(*) FROM communication_logs")->fetchColumn();
$sms_sent = $pdo->query("SELECT COUNT(*) FROM communication_logs WHERE channel='sms'")->fetchColumn();
$push_sent = $pdo->query("SELECT COUNT(*) FROM communication_logs WHERE channel='push'")->fetchColumn();

$root_path = "../../";
$page_title = "Communication Logs | VIC School ERP";
$page_header = "Message Logs";
$active_menu = "communication";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4 mb-4">
        <!-- Stats -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white p-3 rounded-4">
                <h6 class="mb-1 text-uppercase fw-bold text-white-50">Total Messages Sent</h6>
                <h3 class="mb-0 fw-bold"><?= number_format($total_sent) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-info text-white p-3 rounded-4">
                <h6 class="mb-1 text-uppercase fw-bold text-white-50">SMS Dispatched</h6>
                <h3 class="mb-0 fw-bold"><?= number_format($sms_sent) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-success text-white p-3 rounded-4">
                <h6 class="mb-1 text-uppercase fw-bold text-white-50">App Push Sent</h6>
                <h3 class="mb-0 fw-bold"><?= number_format($push_sent) ?></h3>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-history me-2 text-primary"></i>Recent Dispatches (Last 100)</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search logs...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="logsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Channel</th>
                            <th>Recipient</th>
                            <th>Message Preview</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td class="ps-4">
                                        <?php if($l['channel'] == 'push'): ?>
                                            <span class="badge bg-primary"><i class="fa fa-bell"></i> Push</span>
                                        <?php elseif($l['channel'] == 'whatsapp'): ?>
                                            <span class="badge bg-success"><i class="fab fa-whatsapp"></i> WhatsApp</span>
                                        <?php elseif($l['channel'] == 'sms'): ?>
                                            <span class="badge bg-info text-dark"><i class="fa fa-sms"></i> SMS</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fa fa-envelope"></i> Email</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($l['recipient_name']) ?></div>
                                        <div class="text-muted small">ID: <?= htmlspecialchars($l['user_id']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small text-dark"><?= htmlspecialchars($l['subject']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width:300px;"><?= htmlspecialchars($l['message']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">Sent</span>
                                    </td>
                                    <td class="pe-4 text-end text-muted small">
                                        <?= date('d M y, h:i A', strtotime($l['sent_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No logs found.</td></tr>
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
    let rows = document.querySelectorAll('#logsTable tbody tr');
    rows.forEach(row => { row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none'; });
});
</script>

<?php require_once('../includes/footer.php'); ?>
