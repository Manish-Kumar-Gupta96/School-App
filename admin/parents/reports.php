<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
LOAD ANALYTIC COUNTS
========================== */
$totalParents = $pdo->query("SELECT COUNT(*) FROM parents")->fetchColumn();
$totalMappedLinks = $pdo->query("SELECT COUNT(*) FROM parent_students")->fetchColumn();
$totalNotifications = $pdo->query("SELECT COUNT(*) FROM parent_notifications")->fetchColumn();

// Notification counts by type
$notifTypes = $pdo->query("
    SELECT type, COUNT(*) as count 
    FROM parent_notifications 
    GROUP BY type
")->fetchAll(PDO::FETCH_ASSOC);

// Map parents directory
$parentDirectory = $pdo->query("
    SELECT p.*, GROUP_CONCAT(CONCAT(s.first_name, ' ', s.last_name, ' (', s.class, ')') SEPARATOR ', ') AS linked_kids
    FROM parents p
    LEFT JOIN parent_students ps ON p.id = ps.parent_id
    LEFT JOIN students s ON ps.student_id = s.id
    GROUP BY p.id
    ORDER BY p.id DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Parent Portal Analytics | VIC ERP";
$page_header = "Parent Portal";
$active_menu = "parents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Overview of registered family profiles, student links, and communications</h5>
    <div class="d-flex gap-2">
        <a href="parent-list.php" class="btn btn-outline-primary">
            <i class="fa fa-user-friends me-1"></i> Parent List
        </a>
        <a href="student-progress.php" class="btn btn-outline-primary">
            <i class="fa fa-chart-line me-1"></i> Student Progress
        </a>
        <a href="fee-status.php" class="btn btn-outline-primary">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fee Status
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="notifications.php" class="btn btn-outline-primary">
            <i class="fa fa-bullhorn me-1"></i> Notifications
        </a>
        <a href="reports.php" class="btn btn-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<!-- ANALYTICS METRIC CARDS -->
<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid var(--primary-color) !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary-subtle text-primary p-3 rounded-3 me-3">
                        <i class="fa fa-users fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Parent Profiles</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalParents ?></h4>
                        <span class="text-muted small text-start d-block">Registered guardians</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success-subtle text-success p-3 rounded-3 me-3">
                        <i class="fa fa-link fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Mapped Student Links</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalMappedLinks ?> Mapped</h4>
                        <span class="text-muted small text-start d-block">Active student mappings</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
            <div class="card-body py-4 ps-4">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning-subtle text-warning p-3 rounded-3 me-3">
                        <i class="fa fa-bullhorn fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1 text-uppercase fw-bold text-start" style="font-size: 0.75rem; letter-spacing: 1px;">Broadcast Alerts</h6>
                        <h4 class="mb-0 fw-bold text-dark text-start"><?= (int)$totalNotifications ?> Sent</h4>
                        <span class="text-muted small text-start d-block">Notices & SMS alerts</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- BROADCAST TYPE DISTRIBUTIONS -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-chart-pie me-2 text-primary"></i>Alert Types Breakdown</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Notice Category</th>
                                <th class="pe-4 text-center">Alerts Broadcast</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($notifTypes) > 0): ?>
                                <?php foreach($notifTypes as $type): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= htmlspecialchars($type['type']) ?></td>
                                        <td class="pe-4 text-center fw-bold text-primary"><?= (int)$type['count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">No alert categories tracked yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- FAMILY CONNECTIONS LEDGER -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark text-start"><i class="fa fa-user-friends me-2 text-primary"></i>Active Mapped Connections</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Parent / Guardian</th>
                                <th>Contact No</th>
                                <th>Linked Student (Class)</th>
                                <th class="pe-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($parentDirectory) > 0): ?>
                                <?php foreach($parentDirectory as $row): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($row['parent_name']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($row['mobile'] ?: '-') ?></td>
                                        <td>
                                            <?php if($row['linked_kids']): ?>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                    <?= htmlspecialchars($row['linked_kids']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small italic">No linked kids</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <span class="badge bg-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?>-subtle text-<?= $row['status'] == 'Active' ? 'success' : 'danger' ?> border px-2 py-1 small">
                                                <?= htmlspecialchars($row['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">No family mappings registered yet.</td>
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
