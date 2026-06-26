<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_page') {
        $page_id = (int)$_POST['page_id'];
        $page_title = trim($_POST['page_title']);
        $slug = trim($_POST['slug']);
        $page_content = $_POST['page_content'];
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        $status = $_POST['status'];
        
        if (!empty($page_title) && !empty($slug)) {
            if ($page_id > 0) {
                $stmt = $pdo->prepare("UPDATE cms_pages SET page_title=?, slug=?, page_content=?, meta_title=?, meta_description=?, status=? WHERE id=?");
                $stmt->execute([$page_title, $slug, $page_content, $meta_title, $meta_description, $status, $page_id]);
                $_SESSION['success'] = "Page updated successfully.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO cms_pages (page_title, slug, page_content, meta_title, meta_description, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$page_title, $slug, $page_content, $meta_title, $meta_description, $status]);
                $_SESSION['success'] = "Page created successfully.";
            }
        } else {
            $_SESSION['error'] = "Title and slug are required.";
        }
        header("Location: pages.php");
        exit;
    } elseif ($_POST['action'] === 'delete_page') {
        $page_id = (int)$_POST['page_id'];
        $pdo->prepare("DELETE FROM cms_pages WHERE id=?")->execute([$page_id]);
        $_SESSION['success'] = "Page deleted.";
        header("Location: pages.php");
        exit;
    }
}

$pages = $pdo->query("SELECT * FROM cms_pages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$edit_page = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM cms_pages WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_page = $stmt->fetch(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Website Pages CMS | VIC School ERP";
$page_header = "Dynamic Web Pages";
$active_menu = "cms";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<!-- TinyMCE for WYSIWYG editing -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#page_content',
    height: 400,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code',
    toolbar_mode: 'floating',
  });
</script>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Manage Page Form -->
        <div class="col-md-12 col-lg-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-file-code me-2"></i><?= $edit_page ? 'Edit Page' : 'Create New Page' ?></h5>
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
                        <input type="hidden" name="action" value="save_page">
                        <input type="hidden" name="page_id" value="<?= $edit_page['id'] ?? 0 ?>">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Page Title <span class="text-danger">*</span></label>
                                <input type="text" name="page_title" id="page_title" class="form-control" value="<?= htmlspecialchars($edit_page['page_title'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">URL Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control" value="<?= htmlspecialchars($edit_page['slug'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Page Content</label>
                            <textarea name="page_content" id="page_content" class="form-control"><?= htmlspecialchars($edit_page['page_content'] ?? '') ?></textarea>
                        </div>
                        
                        <hr>
                        <h6 class="fw-bold text-muted mb-3"><i class="fa fa-search me-2"></i>SEO Settings</h6>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($edit_page['meta_title'] ?? '') ?>" placeholder="Defaults to page title if empty">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($edit_page['meta_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Publish Status</label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= ($edit_page['status'] ?? '') == 'draft' ? 'selected' : '' ?>>Draft (Hidden)</option>
                                <option value="published" <?= ($edit_page['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published (Live)</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_page): ?>
                                <a href="pages.php" class="btn btn-secondary">Cancel Edit</a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-save me-2"></i> Save Page</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pages List -->
        <div class="col-md-12 col-lg-4">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Existing Pages</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (count($pages) > 0): ?>
                            <?php foreach ($pages as $p): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['page_title']) ?></div>
                                        <div class="text-muted small">/<?= htmlspecialchars($p['slug']) ?></div>
                                        <?php if($p['status'] == 'published'): ?>
                                            <span class="badge bg-success mt-1">Live</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary mt-1">Draft</span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this page?');">
                                            <input type="hidden" name="action" value="delete_page">
                                            <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center py-4 text-muted">No pages created yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-generate slug from title
document.getElementById('page_title').addEventListener('input', function() {
    if (!document.getElementById('slug').value || !<?= $edit_page ? 'true' : 'false' ?>) {
        let slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        document.getElementById('slug').value = slug;
    }
});
</script>

<?php require_once('../includes/footer.php'); ?>
