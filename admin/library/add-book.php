<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_book'])){
    $book_code = "BK" . date('Y') . rand(1000,9999);
    $isbn      = trim($_POST['isbn']);
    $title     = trim($_POST['title']);
    $author    = trim($_POST['author']);
    $publisher = trim($_POST['publisher']);
    $category  = trim($_POST['category']);
    $quantity  = (int)$_POST['quantity'];
    $shelf_no  = trim($_POST['shelf_no']);
    $price     = !empty($_POST['book_price']) ? $_POST['book_price'] : 0.00;

    if(empty($title) || $quantity <= 0){
        $error = "Book Title and valid Quantity are required fields.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO books(
                book_code,
                isbn,
                title,
                author,
                publisher,
                category,
                quantity,
                available_quantity,
                shelf_no,
                book_price
            )
            VALUES(?,?,?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([
            $book_code,
            $isbn,
            $title,
            $author,
            $publisher,
            $category,
            $quantity,
            $quantity, // available quantity initially equals total quantity
            $shelf_no,
            $price
        ]);
        $message = "Book Added Successfully! Generated Code: " . $book_code;
    }
}

// Layout setup
$root_path = "../../";
$page_title = "Add Book | VIC ERP";
$page_header = "Add New Book";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Register new book into library inventory</h5>
    <a href="books.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow border-0" style="border-radius: 15px;">
    <div class="card-body p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">ISBN Number</label>
                    <input type="text" name="isbn" class="form-control" placeholder="e.g. 978-3-16-148410-0">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Book Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Clean Code" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Author Name</label>
                    <input type="text" name="author" class="form-control" placeholder="e.g. Robert C. Martin">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Publisher</label>
                    <input type="text" name="publisher" class="form-control" placeholder="e.g. Prentice Hall">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="e.g. Software Engineering">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" value="1" min="1" class="form-control" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Book Price (₹)</label>
                    <input type="number" step="0.01" name="book_price" value="0.00" min="0" class="form-control" placeholder="0.00">
                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Shelf Number / Location</label>
                    <input type="text" name="shelf_no" class="form-control" placeholder="e.g. Shelf C-4">
                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label fw-semibold">Initial Status</label>
                    <input type="text" class="form-control bg-light" value="Available" readonly>
                </div>

                <div class="col-12">
                    <button type="submit" name="save_book" class="btn btn-success px-4">
                        <i class="fa fa-save me-1"></i> Save Book
                    </button>
                    <a href="books.php" class="btn btn-light px-4 ms-2">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
