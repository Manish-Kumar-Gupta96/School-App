<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch Library Stats
$totalBooks = $pdo->query("SELECT IFNULL(SUM(quantity), 0) FROM library_books")->fetchColumn();
$availableBooks = $pdo->query("SELECT IFNULL(SUM(available_quantity), 0) FROM library_books")->fetchColumn();
$issuedBooks = $pdo->query("SELECT COUNT(*) FROM library_issues WHERE status = 'issued'")->fetchColumn();
$overdueBooks = $pdo->query("SELECT COUNT(*) FROM library_issues WHERE status = 'overdue' OR (status = 'issued' AND due_date < CURDATE())")->fetchColumn();
$collectedFines = $pdo->query("SELECT IFNULL(SUM(fine_amount), 0) FROM library_issues WHERE status = 'returned'")->fetchColumn();

// Recent Issues
$recentIssues = $pdo->query("
    SELECT li.*, lbc.barcode, lb.title
    FROM library_issues li
    JOIN library_book_copies lbc ON li.book_copy_id = lbc.id
    JOIN library_books lb ON lbc.book_id = lb.id
    ORDER BY li.issue_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Library Dashboard | VIC School ERP";
$page_header = "Library Management System";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<!-- GSAP -->
<script src="<?= $root_path ?>assets/js/gsap/gsap.min.js"></script>

<div class="main-dashboard">
    <h2 id="title" class="fw-bold text-dark mb-4 text-start">Smart Library Dashboard</h2>

    <div class="row g-4 mb-4">
        <!-- Total Books -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm card-hover p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid var(--primary-color) !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Total Books</h5>
                    <i class="fa fa-book text-primary fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$totalBooks ?></h3>
            </div>
        </div>
        
        <!-- Available Books -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm card-hover p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #198754 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Available</h5>
                    <i class="fa fa-book-open text-success fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$availableBooks ?></h3>
            </div>
        </div>

        <!-- Issued Books -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm card-hover p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #0dcaf0 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Issued</h5>
                    <i class="fa fa-hand-holding text-info fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$issuedBooks ?></h3>
            </div>
        </div>

        <!-- Overdue Books -->
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm card-hover p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #dc3545 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Overdue</h5>
                    <i class="fa fa-exclamation-circle text-danger fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark"><?= (int)$overdueBooks ?></h3>
            </div>
        </div>
    </div>

    <!-- Fine Collection -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4 col-md-12">
            <div class="card border-0 shadow-sm card-hover p-4 text-start stat" style="border-radius: 12px; border-left: 4px solid #ffc107 !important;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="text-muted small text-uppercase fw-bold mb-0">Fines Collected</h5>
                    <i class="fa fa-rupee-sign text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-dark">₹ <?= number_format($collectedFines, 2) ?></h3>
                <p class="text-muted small mb-0 mt-1">Total revenue from late returns</p>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 15px; height: 100%;">
                <h5 class="fw-bold text-dark text-start mb-4"><i class="fa fa-bolt text-warning me-2"></i>Quick Actions</h5>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="issue.php" class="btn btn-primary"><i class="fa fa-barcode me-2"></i> Issue Book</a>
                    <a href="return.php" class="btn btn-success"><i class="fa fa-undo me-2"></i> Return Book</a>
                    <a href="books.php" class="btn btn-info text-white"><i class="fa fa-book me-2"></i> Manage Books</a>
                    <a href="digital.php" class="btn btn-secondary"><i class="fa fa-desktop me-2"></i> Digital Library</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Issues -->
    <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4 text-start">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-history me-2 text-primary"></i>Recent Issues</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Book Title</th>
                            <th>Barcode</th>
                            <th>User Type</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th class="pe-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentIssues) > 0): ?>
                            <?php foreach($recentIssues as $issue): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($issue['title']) ?></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($issue['barcode']) ?></span></td>
                                    <td><span class="badge bg-info"><?= ucfirst($issue['user_type']) ?></span></td>
                                    <td><?= date('d M Y', strtotime($issue['issue_date'])) ?></td>
                                    <td class="text-danger fw-bold"><?= date('d M Y', strtotime($issue['due_date'])) ?></td>
                                    <td class="pe-4">
                                        <?php if($issue['status'] == 'issued'): ?>
                                            <span class="badge bg-primary">Issued</span>
                                        <?php elseif($issue['status'] == 'returned'): ?>
                                            <span class="badge bg-success">Returned</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Overdue</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No recent book issues found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    gsap.from("#title", { y: -30, opacity: 0, duration: 0.8, ease: "power2.out" });
    gsap.from(".stat", { y: 30, opacity: 0, duration: 0.8, stagger: 0.1, ease: "power2.out" });
    gsap.from(".card", { y: 20, opacity: 0, duration: 0.8, delay: 0.2, stagger: 0.1, ease: "power2.out" });
});
</script>

<?php require_once('../includes/footer.php'); ?>
