<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_category') {
        $category_name = trim($_POST['category_name']);
        if (!empty($category_name)) {
            $stmt = $pdo->prepare("INSERT INTO library_categories (category_name) VALUES (?)");
            $stmt->execute([$category_name]);
            $_SESSION['success'] = "Category added successfully.";
        } else {
            $_SESSION['error'] = "Category name cannot be empty.";
        }
        header("Location: categories.php");
        exit;
    } elseif ($_POST['action'] === 'delete_category') {
        $id = (int)$_POST['id'];
        // Check if category is used
        $check = $pdo->prepare("SELECT COUNT(*) FROM library_books WHERE category_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['error'] = "Cannot delete category. Books are assigned to it.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM library_categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Category deleted successfully.";
        }
        header("Location: categories.php");
        exit;
    }
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM library_books WHERE category_id = c.id) as book_count 
    FROM library_categories c 
    ORDER BY c.category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Library Categories | VIC School ERP";
$page_header = "Manage Library Categories";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row">
        <!-- Add Category Form -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-plus me-2 text-primary"></i>Add Category</h5>
                </div>
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
                        <input type="hidden" name="action" value="add_category">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control" required placeholder="e.g. Science, Mathematics">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-save me-2"></i>Save Category</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Category List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h5 class="mb-0 fw-bold"><i class="fa fa-list me-2 text-success"></i>Category List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">S.No</th>
                                    <th>Category Name</th>
                                    <th>Total Books</th>
                                    <th>Added On</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($categories) > 0): ?>
                                    <?php $i = 1; foreach ($categories as $cat): ?>
                                        <tr>
                                            <td class="ps-4"><?= $i++ ?></td>
                                            <td class="fw-bold"><?= htmlspecialchars($cat['category_name']) ?></td>
                                            <td><span class="badge bg-info"><?= $cat['book_count'] ?></span></td>
                                            <td><?= date('d M Y', strtotime($cat['created_at'])) ?></td>
                                            <td class="pe-4 text-end">
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this category?');">
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No categories found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
