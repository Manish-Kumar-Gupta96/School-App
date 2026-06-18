<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Manage Library Books Catalog | Admin Control";
$active_menu = "library";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['add'])){
    $title  = trim($_POST['title']);
    $author = trim($_POST['author']);
    $total  = (int)$_POST['total'];

    if(empty($title) || empty($author) || $total <= 0){
        $error = "Book Title, Author, and Total Copies count are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, total_copies, available_copies)
            VALUES (?,?,?,?)
        ");
        $stmt->execute([$title, $author, $total, $total]);
        $message = "Book successfully added to library catalog!";
    }
}

$data = $pdo->query("SELECT * FROM books ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📚 Manage Library Catalog</h2>
            <p class="text-muted mb-0">Record textbook titles, authors, and tracks total/available copies indices.</p>
        </div>
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

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-book-medical me-2 text-primary"></i>Add Book to Inventory</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Book Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Fundamentals of Physics" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Author</label>
                        <input type="text" name="author" class="form-control" placeholder="e.g. Halliday & Resnick" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Total Copies</label>
                        <input type="number" name="total" class="form-control" placeholder="e.g. 5" required min="1">
                    </div>

                    <div class="col-md-12 mt-4 text-end">
                        <button type="submit" name="add" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa fa-save me-1"></i> Add Book to Catalog
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-secondary"></i>Books Catalog</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Book Title / Author</th>
                                    <th>Total Copies</th>
                                    <th class="pe-4 text-center">Available Copies</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if(count($data) > 0): ?>
                                    <?php foreach($data as $b): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <strong class="text-dark"><?= htmlspecialchars($b['title']) ?></strong>
                                                <div class="text-muted small">by <?= htmlspecialchars($b['author']) ?></div>
                                            </td>
                                            <td><?= (int)$b['total_copies'] ?> copies</td>
                                            <td class="pe-4 text-center">
                                                <span class="badge <?= $b['available_copies'] > 0 ? 'bg-success' : 'bg-danger' ?> px-3 py-1 fw-bold">
                                                    <?= (int)$b['available_copies'] ?> Available
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">No books cataloged yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
