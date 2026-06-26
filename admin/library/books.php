<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_book') {
        $title = trim($_POST['title']);
        $category_id = (int)$_POST['category_id'];
        $isbn = trim($_POST['isbn']);
        $author = trim($_POST['author']);
        $publisher = trim($_POST['publisher']);
        $edition = trim($_POST['edition']);
        $quantity = (int)$_POST['quantity'];
        $rack_no = trim($_POST['rack_no']);
        
        if (!empty($title) && $category_id > 0 && $quantity > 0) {
            try {
                $pdo->beginTransaction();
                
                // Insert into library_books
                $stmt = $pdo->prepare("INSERT INTO library_books (category_id, isbn, title, author, publisher, edition, quantity, available_quantity, rack_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$category_id, $isbn, $title, $author, $publisher, $edition, $quantity, $quantity, $rack_no]);
                $book_id = $pdo->lastInsertId();
                
                // Insert copies
                $copy_stmt = $pdo->prepare("INSERT INTO library_book_copies (book_id, barcode) VALUES (?, ?)");
                for ($i = 1; $i <= $quantity; $i++) {
                    $barcode = strtoupper(substr(md5($book_id . time() . $i), 0, 8)); // Generate a random barcode
                    $copy_stmt->execute([$book_id, $barcode]);
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Book and $quantity copies added successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to add book: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "Please fill all required fields correctly.";
        }
        header("Location: books.php");
        exit;
    }
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM library_categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch books
$books = $pdo->query("
    SELECT b.*, c.category_name 
    FROM library_books b 
    LEFT JOIN library_categories c ON b.category_id = c.id 
    ORDER BY b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Library Books | VIC School ERP";
$page_header = "Manage Library Books";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <!-- Add Book Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white" data-bs-toggle="collapse" data-bs-target="#addBookForm" style="cursor:pointer;">
            <h5 class="mb-0 fw-bold"><i class="fa fa-plus me-2 text-primary"></i>Add New Book <span class="float-end"><i class="fa fa-chevron-down"></i></span></h5>
        </div>
        <div id="addBookForm" class="collapse">
            <div class="card-body">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="add_book">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Book Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ISBN</label>
                            <input type="text" name="isbn" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Author</label>
                            <input type="text" name="author" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Publisher</label>
                            <input type="text" name="publisher" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Edition</label>
                            <input type="text" name="edition" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Rack Number</label>
                            <input type="text" name="rack_no" class="form-control">
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="fa fa-save me-2"></i> Save Book & Generate Copies</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Book List Section -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-success"></i>Book Inventory</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search by title, author...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="booksTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Title & ISBN</th>
                            <th>Category</th>
                            <th>Author & Publisher</th>
                            <th>Rack No</th>
                            <th>Status</th>
                            <th class="pe-4 text-center">Available/Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($books) > 0): ?>
                            <?php foreach ($books as $b): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['title']) ?></div>
                                        <div class="text-muted small">ISBN: <?= htmlspecialchars($b['isbn'] ?: 'N/A') ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($b['category_name']) ?></span></td>
                                    <td>
                                        <div class="text-dark"><?= htmlspecialchars($b['author'] ?: 'Unknown') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($b['publisher']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($b['rack_no']) ?></td>
                                    <td>
                                        <?php if($b['status'] == 'available'): ?>
                                            <span class="badge bg-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <span class="fw-bold text-primary fs-5"><?= $b['available_quantity'] ?></span> / <span class="text-muted"><?= $b['quantity'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">No books found in the library.</td></tr>
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
    let rows = document.querySelectorAll('#booksTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require_once('../includes/footer.php'); ?>
