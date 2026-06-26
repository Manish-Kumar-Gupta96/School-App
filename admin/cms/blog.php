<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_blog') {
        $blog_id = (int)$_POST['blog_id'];
        $title = trim($_POST['title']);
        $slug = trim($_POST['slug']);
        $content = $_POST['content'];
        $featured_image = trim($_POST['featured_image']);
        
        if (!empty($title) && !empty($slug)) {
            if ($blog_id > 0) {
                $stmt = $pdo->prepare("UPDATE blogs SET title=?, slug=?, content=?, featured_image=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $featured_image, $blog_id]);
                $_SESSION['success'] = "Blog post updated.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO blogs (title, slug, content, featured_image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $featured_image]);
                $_SESSION['success'] = "Blog post published.";
            }
        } else {
            $_SESSION['error'] = "Title and slug are required.";
        }
        header("Location: blog.php");
        exit;
    } elseif ($_POST['action'] === 'delete_blog') {
        $blog_id = (int)$_POST['blog_id'];
        $pdo->prepare("DELETE FROM blogs WHERE id=?")->execute([$blog_id]);
        $_SESSION['success'] = "Blog post deleted.";
        header("Location: blog.php");
        exit;
    }
}

$blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$edit_blog = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_blog = $stmt->fetch(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "Blog Management | VIC School ERP";
$page_header = "School Blog";
$active_menu = "cms";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<!-- TinyMCE for WYSIWYG editing -->
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#content',
    height: 400,
    plugins: 'advlist autolink lists link image charmap preview anchor pagebreak code',
    toolbar_mode: 'floating',
  });
</script>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Manage Blog Form -->
        <div class="col-md-12 col-lg-8">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="fa fa-pen-nib me-2"></i><?= $edit_blog ? 'Edit Blog Post' : 'Write New Post' ?></h5>
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
                        <input type="hidden" name="action" value="save_blog">
                        <input type="hidden" name="blog_id" value="<?= $edit_blog['id'] ?? 0 ?>">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Post Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($edit_blog['title'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">URL Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" id="slug" class="form-control" value="<?= htmlspecialchars($edit_blog['slug'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Featured Image URL</label>
                            <input type="url" name="featured_image" class="form-control" value="<?= htmlspecialchars($edit_blog['featured_image'] ?? '') ?>" placeholder="https://example.com/image.jpg">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Post Content</label>
                            <textarea name="content" id="content" class="form-control"><?= htmlspecialchars($edit_blog['content'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_blog): ?>
                                <a href="blog.php" class="btn btn-secondary">Cancel Edit</a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-paper-plane me-2"></i> <?= $edit_blog ? 'Update Post' : 'Publish Post' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Blogs List -->
        <div class="col-md-12 col-lg-4">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Recent Posts</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if (count($blogs) > 0): ?>
                            <?php foreach ($blogs as $b): ?>
                                <li class="list-group-item p-3">
                                    <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($b['title']) ?></div>
                                    <div class="text-muted small mb-2"><i class="fa fa-clock me-1"></i><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></div>
                                    <div class="d-flex gap-2">
                                        <a href="?edit=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i> Edit</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this post?');">
                                            <input type="hidden" name="action" value="delete_blog">
                                            <input type="hidden" name="blog_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center py-4 text-muted">No blog posts yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('title').addEventListener('input', function() {
    if (!document.getElementById('slug').value || !<?= $edit_blog ? 'true' : 'false' ?>) {
        let slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        document.getElementById('slug').value = slug;
    }
});
</script>

<?php require_once('../includes/footer.php'); ?>
