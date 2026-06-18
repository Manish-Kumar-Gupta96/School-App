<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Helpdesk Reports & Analytics | VIC School";
$active_menu = "helpdesk";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

// Fetch counts by status
$open_count = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'OPEN'")->fetchColumn();
$progress_count = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'IN_PROGRESS'")->fetchColumn();
$resolved_count = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'RESOLVED'")->fetchColumn();
$closed_count = $pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'CLOSED'")->fetchColumn();

// Fetch counts by priority
$priorities = $pdo->query("
    SELECT priority, COUNT(*) as count 
    FROM tickets 
    GROUP BY priority
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch counts by category
$categories = $pdo->query("
    SELECT tc.category_name, COUNT(t.id) as count 
    FROM ticket_categories tc
    LEFT JOIN tickets t ON tc.id = t.category_id
    GROUP BY tc.id
")->fetchAll(PDO::FETCH_ASSOC);

// Overdue tickets (overdue is defined as tickets where due_date < NOW() and status is not RESOLVED or CLOSED)
$overdue_count = $pdo->query("
    SELECT COUNT(*) 
    FROM tickets 
    WHERE due_date < NOW() AND status NOT IN ('RESOLVED', 'CLOSED')
")->fetchColumn();
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">📊 Helpdesk Performance & SLA Reports</h2>
            <p class="text-muted mb-0">Overview of ticket completion status, overdue SLAs, and category workloads.</p>
        </div>
        <a href="tickets.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Helpdesk
        </a>
    </div>

    <!-- STATS CARD ROW -->
    <div class="row g-4 mb-4">
        <!-- Open -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted small text-uppercase fw-bold mb-0">Open Tickets</h6>
                    <i class="fa fa-folder-open text-danger fs-4"></i>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= (int)$open_count ?></h3>
                <span class="text-muted small">Awaiting initial action</span>
            </div>
        </div>

        <!-- In Progress -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted small text-uppercase fw-bold mb-0">In Progress</h6>
                    <i class="fa fa-spinner text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= (int)$progress_count ?></h3>
                <span class="text-muted small">Actively being resolved</span>
            </div>
        </div>

        <!-- Resolved -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted small text-uppercase fw-bold mb-0">Resolved</h6>
                    <i class="fa fa-check-circle text-success fs-4"></i>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= (int)$resolved_count ?></h3>
                <span class="text-muted small">Fixed but awaiting close</span>
            </div>
        </div>

        <!-- SLA Overdue -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 12px; border-left: 4px solid #fd7e14 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-muted small text-uppercase fw-bold mb-0">SLA Overdue</h6>
                    <i class="fa fa-exclamation-triangle text-orange fs-4" style="color: #fd7e14;"></i>
                </div>
                <h3 class="fw-bold mb-1 text-dark" style="color: #fd7e14;"><?= (int)$overdue_count ?></h3>
                <span class="text-muted small text-danger">Missed target due date</span>
            </div>
        </div>
    </div>

    <!-- CHARTS / MATRIX ROW -->
    <div class="row g-4">
        <!-- Categories Workload -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; height: 100%;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-boxes me-2 text-primary"></i>Category Workload Breakdown</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Ticket Category</th>
                                    <th class="pe-4 text-center">Active Requests</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php foreach($categories as $cat): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($cat['category_name']) ?></td>
                                        <td class="pe-4 text-center fw-bold text-primary"><?= (int)$cat['count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Priority Breakdown -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; height: 100%;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list-ol me-2 text-primary"></i>Priorities Matrix</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Priority Code</th>
                                    <th class="pe-4 text-center">Ticket Count</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php foreach($priorities as $pri): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark text-capitalize"><?= htmlspecialchars($pri['priority']) ?></td>
                                        <td class="pe-4 text-center fw-bold text-danger"><?= (int)$pri['count'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
