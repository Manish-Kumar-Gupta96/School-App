<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Broadcast History | Admin Control";
$active_menu = "notifications";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$type_filter = trim($_GET['type_filter'] ?? '');

$query_str = "SELECT * FROM notifications WHERE 1=1";
$params = [];

if ($type_filter !== '') {
    $query_str .= " AND type = ?";
    $params[] = $type_filter;
}

$query_str .= " ORDER BY id DESC LIMIT 50";
$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📜 Broadcast Dispatch Log</h2>
            <p class="text-muted mb-0">Trace history records of previously sent alerts and check delivery status logs.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="send.php" class="btn btn-outline-primary"><i class="fa fa-paper-plane me-1"></i> Send Alert</a>
            <a href="templates.php" class="btn btn-outline-primary"><i class="fa fa-file-invoice me-1"></i> Templates</a>
            <a href="settings.php" class="btn btn-outline-primary"><i class="fa fa-cogs me-1"></i> Settings</a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <select name="type_filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Channels</option>
                        <option value="IN_APP" <?= $type_filter === 'IN_APP' ? 'selected' : '' ?>>In-App</option>
                        <option value="EMAIL" <?= $type_filter === 'EMAIL' ? 'selected' : '' ?>>Email</option>
                        <option value="SMS" <?= $type_filter === 'SMS' ? 'selected' : '' ?>>SMS</option>
                        <option value="WHATSAPP" <?= $type_filter === 'WHATSAPP' ? 'selected' : '' ?>>WhatsApp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <span class="text-muted small">Showing last 50 sent records</span>
                </div>
            </form>
        </div>
    </div>

    <!-- HISTORY LIST -->
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 80px;">ID</th>
                            <th>Subject / Title</th>
                            <th>Alert Message</th>
                            <th>Channel Type</th>
                            <th>Sender ID</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($notifs) > 0): ?>
                            <?php foreach($notifs as $n): ?>
                                <?php
                                $chan_badge = 'bg-secondary';
                                if ($n['type'] === 'IN_APP') $chan_badge = 'bg-primary';
                                elseif ($n['type'] === 'EMAIL') $chan_badge = 'bg-success';
                                elseif ($n['type'] === 'SMS') $chan_badge = 'bg-warning text-dark';
                                elseif ($n['type'] === 'WHATSAPP') $chan_badge = 'bg-info text-white';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?= $n['id'] ?></td>
                                    <td class="fw-bold text-dark" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($n['title']) ?></td>
                                    <td class="text-secondary small" style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($n['message']) ?></td>
                                    <td>
                                        <span class="badge <?= $chan_badge ?> px-2 py-1 small">
                                            <?= htmlspecialchars($n['type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted">User #<?= (int)$n['sender_id'] ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($n['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa fa-history fs-2 mb-2 d-block text-secondary"></i>
                                    No broadcast notifications found in history logs.
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
