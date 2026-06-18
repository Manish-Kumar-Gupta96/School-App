<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "My Support Tickets | Student Portal";
$active_menu = "helpdesk";
require_once('includes/header.php');

$student_id = $_SESSION['student_id'];

// Fetch Student Tickets
$stmt = $pdo->prepare("
    SELECT t.*, tc.category_name, a.name as assignee_name
    FROM tickets t
    LEFT JOIN ticket_categories tc ON t.category_id = tc.id
    LEFT JOIN admins a ON t.assigned_to = a.id
    WHERE t.user_id = ? AND t.user_type = 'STUDENT'
    ORDER BY t.id DESC
");
$stmt->execute([$student_id]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start" style="padding: 20px 0;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🎫 Support Helpdesk</h2>
            <p class="text-muted mb-0">Open and manage support tickets for accounts, academics, library, or report complaints.</p>
        </div>
        <a href="create-ticket.php" class="btn btn-primary">
            <i class="fa fa-plus-circle me-1"></i> Open Ticket
        </a>
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
                            <th class="pe-4 text-center">Action</th>
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
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($t['subject']) ?></td>
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
                                    <td class="text-muted"><i class="fa fa-circle-user text-muted me-1"></i> <?= htmlspecialchars($t['assignee_name'] ?: 'Unassigned') ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($t['created_at']) ?></td>
                                    <td class="pe-4 text-center">
                                        <a href="replies.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa fa-comments"></i> Chat / View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa fa-ticket-alt fs-2 mb-2 d-block text-secondary"></i>
                                    You have not opened any support tickets.
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
require_once('includes/footer.php');
?>
