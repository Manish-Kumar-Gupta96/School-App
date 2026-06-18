<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "System Audit Logs | Admin Control";
$active_menu = "audit";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$keyword = trim($_GET['keyword'] ?? '');
$module_filter = trim($_GET['module_filter'] ?? '');
$from_date = trim($_GET['from_date'] ?? '');
$to_date = trim($_GET['to_date'] ?? '');

// Pagination settings
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Build query
$query_str = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $query_str .= " AND (action LIKE ? OR user_name LIKE ? OR ip_address LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($module_filter !== '') {
    $query_str .= " AND module_name = ?";
    $params[] = $module_filter;
}

if ($from_date !== '') {
    $query_str .= " AND DATE(created_at) >= ?";
    $params[] = $from_date;
}

if ($to_date !== '') {
    $query_str .= " AND DATE(created_at) <= ?";
    $params[] = $to_date;
}

// Count total matching logs
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM (" . $query_str . ") as t");
$count_stmt->execute($params);
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);
if ($total_pages < 1) $total_pages = 1;

// Fetch paginated matching logs
$query_str .= " ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct modules for filter
$modules = $pdo->query("SELECT DISTINCT module_name FROM audit_logs WHERE module_name IS NOT NULL AND module_name != ''")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-3">
        <div>
            <h2 class="fw-bold text-dark mb-1">📋 System Audit Trail</h2>
            <p class="text-muted mb-0">Monitor all security changes, student edits, fee updates, and administrative modifications.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="export.php?keyword=<?= urlencode($keyword) ?>&module_filter=<?= urlencode($module_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>" class="btn btn-outline-success">
                <i class="fa fa-file-csv me-1"></i> Export CSV
            </a>
            <a href="delete-old.php" class="btn btn-outline-danger" onclick="return confirm('Are you sure you want to delete all audit logs older than 1 year? This action is irreversible.');">
                <i class="fa fa-trash-alt me-1"></i> Prune Old Logs
            </a>
        </div>
    </div>

    <!-- FILTER BOARD -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body p-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search Keyword</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Search action, user, IP..." value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Filter Module</label>
                    <select name="module_filter" class="form-select">
                        <option value="">All Modules</option>
                        <?php foreach($modules as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>" <?= $module_filter === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- LOGS LIST -->
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 80px;">ID</th>
                            <th>User Profile</th>
                            <th>Role</th>
                            <th>Action Logged</th>
                            <th>Module</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                            <th class="pe-4 text-center">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach($logs as $log): ?>
                                <?php
                                $role_badge = 'bg-secondary';
                                if (strtolower($log['role_name']) === 'admin' || strtolower($log['role_name']) === 'super admin') {
                                    $role_badge = 'bg-danger';
                                } elseif (strtolower($log['role_name']) === 'teacher') {
                                    $role_badge = 'bg-primary';
                                } elseif (strtolower($log['role_name']) === 'parent') {
                                    $role_badge = 'bg-success';
                                } elseif (strtolower($log['role_name']) === 'student') {
                                    $role_badge = 'bg-info';
                                }
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?= $log['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($log['user_name'] ?: 'Guest') ?></div>
                                        <span class="text-muted small">ID: <?= (int)$log['user_id'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $role_badge ?> px-2 py-1 small">
                                            <?= htmlspecialchars($log['role_name'] ?: 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($log['action']) ?></td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($log['module_name'] ?: 'System') ?></span></td>
                                    <td><code class="text-secondary"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                    <td class="small text-muted"><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td class="pe-4 text-center">
                                        <a href="view-log.php?id=<?= $log['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-history fs-2 mb-2 d-block text-secondary"></i>
                                    No system audit logs found matching your filter criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap">
                <span class="text-muted small">Showing Page <strong><?= $page ?></strong> of <strong><?= $total_pages ?></strong> (Total matching: <?= $total_rows ?> logs)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>&module_filter=<?= urlencode($module_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>">Previous</a>
                        </li>
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>&module_filter=<?= urlencode($module_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>&module_filter=<?= urlencode($module_filter) ?>&from_date=<?= urlencode($from_date) ?>&to_date=<?= urlencode($to_date) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
