<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT *
    FROM books
    WHERE title LIKE ? OR author LIKE ? OR isbn LIKE ? OR book_code LIKE ?
    ORDER BY id DESC
");
$stmt->execute([
    "%$search%",
    "%$search%",
    "%$search%",
    "%$search%"
]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Library Catalog | VIC ERP";
$page_header = "Library Management";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Browse and manage the school library catalog</h5>
    <div class="d-flex gap-2">
        <a href="add-book.php" class="btn btn-primary">
            <i class="fa fa-plus-circle me-1"></i> Add Book
        </a>
        <a href="issue-book.php" class="btn btn-outline-primary">
            <i class="fa fa-book-reader me-1"></i> Issue Book
        </a>
        <a href="return-book.php" class="btn btn-outline-success">
            <i class="fa fa-undo-alt me-1"></i> Return Book
        </a>
        <a href="members.php" class="btn btn-outline-info">
            <i class="fa fa-address-card me-1"></i> Members
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-pie me-1"></i> Reports
        </a>
    </div>
</div>

<!-- SEARCH -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by title, author, ISBN, or book code..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">
            <i class="fa fa-search me-1"></i> Search
        </button>
    </div>
</form>

<!-- INVENTORY CARD -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Book Details</th>
                        <th>ISBN</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th class="text-center">Shelf</th>
                        <th class="text-center">Price</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Available</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($books) > 0): ?>
                        <?php foreach($books as $book): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($book['title']) ?></div>
                                    <span class="badge bg-secondary-subtle text-secondary small"><?= htmlspecialchars($book['book_code']) ?></span>
                                </td>
                                <td><span class="text-muted small"><?= htmlspecialchars($book['isbn'] ?: 'N/A') ?></span></td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($book['author'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($book['category'] ?: '-') ?></td>
                                <td class="text-center"><?= htmlspecialchars($book['shelf_no'] ?: '-') ?></td>
                                <td class="text-center fw-bold">₹ <?= number_format($book['book_price'] ?: 0.00, 2) ?></td>
                                <td class="text-center fw-semibold"><?= (int)$book['quantity'] ?></td>
                                <td class="text-center fw-semibold text-success"><?= (int)$book['available_quantity'] ?></td>
                                <td class="text-center">
                                    <?php if($book['available_quantity'] > 0): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Available</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Out Of Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fa fa-book-open fs-2 mb-2 d-block"></i>
                                No books found in library catalog.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
