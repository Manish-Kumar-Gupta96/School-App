<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Support Helpdesk | Admin Control";
$active_menu = "helpdesk";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

$status_filter = trim($_GET['status_filter'] ?? '');
$priority_filter = trim($_GET['priority_filter'] ?? '');

// Handle status updates / assignment changes
if (isset($_POST['update_ticket'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $status = $_POST['status'];
    $assigned_to = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;

    try {
        $stmt = $pdo->prepare("
            UPDATE tickets
            SET status = ?, assigned_to = ?
            WHERE id = ?
        ");
        $stmt->execute([$status, $assigned_to, $ticket_id]);
        
        // Log Audit
        require_once('../includes/audit-helper.php');
        addAuditLog(
            $pdo,
            $_SESSION['user_id'],
            $_SESSION['name'] ?? 'Admin',
            $_SESSION['role'],
            'Ticket #' . $ticket_id . ' Properties Updated (Status: ' . $status . ')',
            'Helpdesk',
            $ticket_id
        );
    } catch (Exception $e) {
        error_log("Failed to update ticket: " . $e->getMessage());
    }
}

// Build query
$query_str = "
    SELECT t.*, tc.category_name, a.name as assignee_name
    FROM tickets t
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id
    LEFT JOIN admins a ON t.assigned_to = a.id
    WHERE 1=1
";
$params = [];

if ($status_filter !== '') {
    $query_str .= " AND t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== '') {
    $query_str .= " AND t.priority = ?";
    $params[] = $priority_filter;
}

$query_str .= " ORDER BY t.id DESC";
$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch admins for assignments list
$admins = $pdo->query("SELECT id, name FROM admins ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🎫 Support Helpdesk</h2>
            <p class="text-muted mb-0">View student, teacher, and parent support requests, manage assignees, and update resolution states.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="create-ticket.php" class="btn btn-primary"><i class="fa fa-plus-circle me-1"></i> Open Ticket</a>
            <a href="reports.php" class="btn btn-outline-primary"><i class="fa fa-chart-pie me-1"></i> Helpdesk Reports</a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
        <div class="card-body py-3 px-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-3">
                    <select name="status_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="OPEN" <?= $status_filter === 'OPEN' ? 'selected' : '' ?>>Open</option>
                        <option value="IN_PROGRESS" <?= $status_filter === 'IN_PROGRESS' ? 'selected' : '' ?>>In Progress</option>
                        <option value="RESOLVED" <?= $status_filter === 'RESOLVED' ? 'selected' : '' ?>>Resolved</option>
                        <option value="CLOSED" <?= $status_filter === 'CLOSED' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="priority_filter" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">All Priorities</option>
                        <option value="LOW" <?= $priority_filter === 'LOW' ? 'selected' : '' ?>>Low</option>
                        <option value="MEDIUM" <?= $priority_filter === 'MEDIUM' ? 'selected' : '' ?>>Medium</option>
                        <option value="HIGH" <?= $priority_filter === 'HIGH' ? 'selected' : '' ?>>High</option>
                        <option value="URGENT" <?= $priority_filter === 'URGENT' ? 'selected' : '' ?>>Urgent</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- TICKETS GRID -->
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Ticket No</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Assignee</th>
                            <th>Created Date</th>
                            <th class="pe-4 text-center">Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tickets) > 0): ?>
                            <?php foreach($tickets as $t): ?>
                                <?php
                                $priority_class = 'bg-secondary';
                                if ($t['priority'] === 'URGENT') $priority_class = 'bg-danger';
                                elseif ($t['priority'] === 'HIGH') $priority_class = 'bg-warning text-dark';
                                elseif ($t['priority'] === 'MEDIUM') $priority_class = 'bg-primary';
                                elseif ($t['priority'] === 'LOW') $priority_class = 'bg-success';

                                $status_class = 'bg-secondary';
                                if ($t['status'] === 'OPEN') $status_class = 'bg-danger-subtle text-danger border-danger-subtle';
                                elseif ($t['status'] === 'IN_PROGRESS') $status_class = 'bg-warning-subtle text-warning-emphasis border-warning-subtle';
                                elseif ($t['status'] === 'RESOLVED') $status_class = 'bg-success-subtle text-success border-success-subtle';
                                elseif ($t['status'] === 'CLOSED') $status_class = 'bg-light text-muted border-secondary-subtle';
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark font-monospace"><?= htmlspecialchars($t['ticket_no']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($t['subject']) ?></div>
                                        <span class="text-muted small">Role: <?= htmlspecialchars($t['user_type']) ?> | User ID: <?= (int)$t['user_id'] ?></span>
                                    </td>
                                    <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($t['category_name'] ?: 'General') ?></span></td>
                                    <td>
                                        <span class="badge <?= $priority_class ?> px-2 py-1 small">
                                            <?= htmlspecialchars($t['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge border <?= $status_class ?> px-2 py-1 small">
                                            <?= htmlspecialchars($t['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center gap-1 mb-0">
                                            <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                            <input type="hidden" name="status" value="<?= htmlspecialchars($t['status']) ?>">
                                            <select name="assigned_to" class="form-select form-select-sm" style="font-size: 0.8rem; width: 130px;" onchange="this.form.submit()">
                                                <option value="">Unassigned</option>
                                                <?php foreach($admins as $a): ?>
                                                    <option value="<?= $a['id'] ?>" <?= $t['assigned_to'] == $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($t['created_at']) ?></td>
                                    <td class="pe-4 text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="replies.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-comments"></i> Chat
                                            </a>
                                            <!-- Close Button Quick Action -->
                                            <?php if ($t['status'] !== 'CLOSED'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                                                    <input type="hidden" name="status" value="CLOSED">
                                                    <input type="hidden" name="assigned_to" value="<?= htmlspecialchars($t['assigned_to'] ?? '') ?>">
                                                    <button type="submit" name="update_ticket" class="btn btn-sm btn-outline-danger" title="Close Ticket">
                                                        <i class="fa fa-times-circle"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-ticket-alt fs-2 mb-2 d-block text-secondary"></i>
                                    No support tickets registered in the system.
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
