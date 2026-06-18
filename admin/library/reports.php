<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

/* ==========================
TOTAL BOOKS
========================== */
$totalBooks = $pdo->query("SELECT IFNULL(SUM(quantity), 0) as total FROM books")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
TOTAL ISSUED (LENT)
========================== */
$totalIssued = $pdo->query("SELECT COUNT(*) as total FROM book_issues WHERE status='Issued'")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
RETURNED BOOKS
========================== */
$returnedBooks = $pdo->query("SELECT COUNT(*) as total FROM book_issues WHERE status='Returned'")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
OVERDUE BOOKS
========================== */
$overdueBooks = $pdo->query("
    SELECT COUNT(*) as total 
    FROM book_issues 
    WHERE status='Issued' AND due_date < CURDATE()
")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
TOTAL FINE
========================== */
$totalFine = $pdo->query("SELECT IFNULL(SUM(fine_amount),0) AS total FROM book_issues")->fetch(PDO::FETCH_ASSOC)['total'];

/* ==========================
MOST ISSUED BOOKS
========================== */
$topBooks = $pdo->query("
    SELECT b.title, b.book_code, b.author, COUNT(bi.id) AS issue_count
    FROM book_issues bi
    LEFT JOIN books b ON bi.book_id=b.id
    GROUP BY bi.book_id
    ORDER BY issue_count DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Library Reports | VIC ERP";
$page_header = "Library Analytics Reports";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Overview of library activity and fine revenues</h5>
    <a href="books.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<!-- STATS CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <i class="fa fa-book-open fs-1 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-1"><?= $totalBooks ?></h3>
                <span class="text-uppercase small tracking-wider">Total Books Stock</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-warning text-white" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <i class="fa fa-book-reader fs-1 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-1"><?= $totalIssued ?></h3>
                <span class="text-uppercase small tracking-wider">Active Lent Books</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <i class="fa fa-undo-alt fs-1 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-1"><?= $returnedBooks ?></h3>
                <span class="text-uppercase small tracking-wider">Returned Books</span>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0 bg-danger text-white" style="border-radius: 12px;">
            <div class="card-body p-4 text-center">
                <i class="fa fa-calendar-times fs-1 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-1"><?= $overdueBooks ?></h3>
                <span class="text-uppercase small tracking-wider">Overdue Books</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- FINE COLLECTION -->
    <div class="col-md-4">
        <div class="card shadow border-0" style="border-radius: 15px; min-height: 250px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Fine Collection</h5>
            </div>
            <div class="card-body px-4 text-center d-flex flex-column justify-content-center">
                <i class="fa fa-money-bill-wave text-success mb-3" style="font-size: 3.5rem;"></i>
                <h1 class="fw-bold text-success mb-1">₹ <?= number_format($totalFine, 2) ?></h1>
                <span class="text-muted small">Cumulative revenue generated from late returns</span>
            </div>
        </div>
    </div>

    <!-- MOST ISSUED BOOKS -->
    <div class="col-md-8">
        <div class="card shadow border-0" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Most Borrowed Books (Top 10)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Book Catalog Code</th>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th class="text-center">Borrow Counts</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if(count($topBooks) > 0): ?>
                                <?php foreach($topBooks as $bk): ?>
                                    <tr>
                                        <td class="ps-4"><span class="badge bg-secondary"><?= htmlspecialchars($bk['book_code'] ?: 'N/A') ?></span></td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($bk['title'] ?: 'Unknown Book') ?></td>
                                        <td class="fw-semibold text-primary"><?= htmlspecialchars($bk['author'] ?: '-') ?></td>
                                        <td class="text-center"><span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6 fw-bold"><?= $bk['issue_count'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-chart-line fs-2 mb-2 d-block"></i>
                                        No borrow history data yet.
                                    </td>
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
