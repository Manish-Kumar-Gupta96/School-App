<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Blog & Content Manager | Admin Portal";
$active_menu = "blogs";
require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

// Handle Category Creation
if (isset($_POST['add_category'])) {
    $cat_name = trim($_POST['cat_name']);
    $cat_slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', trim($_POST['cat_slug'])));
    
    if (empty($cat_name) || empty($cat_slug)) {
        $error = "Category Name and Slug are required.";
    } else {
        try {
            $base_slug = $cat_slug;
            $counter = 1;
            $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM blog_categories WHERE slug = ?");
            while (true) {
                $check_stmt->execute([$cat_slug]);
                if ($check_stmt->fetchColumn() == 0) {
                    break;
                }
                $cat_slug = $base_slug . '-' . $counter;
                $counter++;
            }

            $stmt = $pdo->prepare("INSERT INTO blog_categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$cat_name, $cat_slug]);
            $message = "Category added successfully!";
        } catch (Exception $e) {
            $error = "Failed to add category: " . $e->getMessage();
        }
    }
}

// Handle Blog Posting / Editing
if (isset($_POST['save_blog'])) {
    $blog_id    = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
    $title      = trim($_POST['title']);
    $slug       = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', trim($_POST['slug'])));
    $category_id = (int)$_POST['category_id'];
    $content    = trim($_POST['content']);
    $tags       = trim($_POST['tags']);
    $meta_title = trim($_POST['meta_title']);
    $meta_desc  = trim($_POST['meta_description']);
    $status     = $_POST['status']; // 'Draft' / 'Published'
    
    if (empty($title) || empty($slug) || empty($content)) {
        $error = "Title, Slug, and Content are required fields.";
    } else {
        $image = '';
        $upload_ok = true;

        if (!empty($_FILES['image']['name'])) {
            $validation = validate_uploaded_file($_FILES['image'], ['jpg', 'jpeg', 'png', 'webp', 'gif'], 2097152, ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
            if ($validation !== true) {
                $error = $validation;
                $upload_ok = false;
            } else {
                $image = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['image']['name']);
                $dest = '../uploads/blog/';
                if (!is_dir($dest)) {
                    mkdir($dest, 0777, true);
                }
                move_uploaded_file($_FILES['image']['tmp_name'], $dest . $image);
                $image = 'uploads/blog/' . $image;
            }
        }

        if ($upload_ok) {
            try {
                if ($blog_id > 0) {
                    // Check duplicate slug and make unique
                    $base_slug = $slug;
                    $counter = 1;
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE slug = ? AND id != ?");
                    while (true) {
                        $check_stmt->execute([$slug, $blog_id]);
                        if ($check_stmt->fetchColumn() == 0) {
                            break;
                        }
                        $slug = $base_slug . '-' . $counter;
                        $counter++;
                    }

                    // Update
                    if (!empty($image)) {
                        $stmt = $pdo->prepare("
                            UPDATE blogs 
                            SET category_id = ?, title = ?, slug = ?, content = ?, tags = ?, featured_image = ?, meta_title = ?, meta_description = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$category_id, $title, $slug, $content, $tags, $image, $meta_title, $meta_desc, $status, $blog_id]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE blogs 
                            SET category_id = ?, title = ?, slug = ?, content = ?, tags = ?, meta_title = ?, meta_description = ?, status = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$category_id, $title, $slug, $content, $tags, $meta_title, $meta_desc, $status, $blog_id]);
                    }
                    $message = "Blog post updated successfully!";
                } else {
                    // Check duplicate slug and make unique
                    $base_slug = $slug;
                    $counter = 1;
                    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM blogs WHERE slug = ?");
                    while (true) {
                        $check_stmt->execute([$slug]);
                        if ($check_stmt->fetchColumn() == 0) {
                            break;
                        }
                        $slug = $base_slug . '-' . $counter;
                        $counter++;
                    }

                    // Insert
                    $stmt = $pdo->prepare("
                        INSERT INTO blogs (category_id, title, slug, content, tags, featured_image, meta_title, meta_description, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$category_id, $title, $slug, $content, $tags, $image, $meta_title, $meta_desc, $status]);
                    $message = "Blog post published successfully!";
                }
            } catch (Exception $e) {
                $error = "Failed to save blog post: " . $e->getMessage();
            }
        }
    }
}

// Handle Delete Post
if (isset($_GET['delete_blog_id'])) {
    $del_id = (int)$_GET['delete_blog_id'];
    try {
        // Fetch image to delete
        $stmt_img = $pdo->prepare("SELECT featured_image FROM blogs WHERE id = ?");
        $stmt_img->execute([$del_id]);
        $img = $stmt_img->fetchColumn();
        if ($img && file_exists('../' . $img)) {
            unlink('../' . $img);
        }
        
        $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Blog post deleted successfully.";
    } catch (Exception $e) {
        $error = "Failed to delete blog: " . $e->getMessage();
    }
}

// Fetch Categories & Posts
$categories = $pdo->query("SELECT * FROM blog_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$posts = $pdo->query("
    SELECT b.*, bc.name AS category_name 
    FROM blogs b 
    LEFT JOIN blog_categories bc ON b.category_id = bc.id 
    ORDER BY b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">✍️ Blog & Content Creator Panel</h2>
            <p class="text-muted mb-0">Write articles, configure tags, build categories, and add SEO meta details to boost site keywords and search traffic.</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert" style="border-radius: 10px;">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Post Write Panel -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4" id="form-title"><i class="fa fa-pen-nib me-2 text-primary"></i>Write / Edit Blog Post</h5>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="blog_id" id="blog_id" value="0">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Article Title *</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Tips for Exam Preparation" required onkeyup="generateSlug(this.value)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Friendly URL Slug *</label>
                            <input type="text" name="slug" id="slug" class="form-control" placeholder="tips-for-exam-preparation" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="category_id" id="category_id" class="form-select">
                                <option value="0">Uncategorized</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tags (Comma Separated)</label>
                            <input type="text" name="tags" id="tags" class="form-control" placeholder="exams, study, preparation">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Article Body Content *</label>
                        <textarea name="content" id="content" class="form-control" rows="8" placeholder="Write rich text blog content here..." required></textarea>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Featured Header Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <div class="form-text">Recommended: 1200x630px. Max size 2MB.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Publication Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Draft">Save as Draft</option>
                                <option value="Published">Publish Live</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-3 mt-4 border-top pt-3"><i class="fa fa-search me-2 text-info"></i>SEO Override Details</h5>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SEO Browser Title</label>
                            <input type="text" name="meta_title" id="meta_title" class="form-control" placeholder="Specific browser tab title">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SEO Meta Description</label>
                            <textarea name="meta_description" id="meta_description" class="form-control" rows="2" placeholder="Search snippet details..."></textarea>
                        </div>
                    </div>

                    <button type="submit" name="save_blog" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save and Publish Post
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100 fw-semibold mt-2" onclick="resetForm()">
                        Clear Form
                    </button>
                </form>
            </div>

            <!-- Blog List Panel -->
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-list me-2 text-muted"></i>Current Articles (<?= count($posts) ?>)</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Featured</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($posts) > 0): ?>
                                <?php foreach($posts as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if($p['featured_image']): ?>
                                                <img src="../<?= htmlspecialchars($p['featured_image']) ?>" class="rounded" style="width: 50px; height: 35px; object-fit: cover;" alt="Blog">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded text-center small py-1" style="width: 50px;">None</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold small"><?= htmlspecialchars($p['title']) ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><?= htmlspecialchars($p['category_name'] ?: 'Uncategorized') ?></span></td>
                                        <td>
                                            <span class="badge <?= $p['status'] === 'Published' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> border px-2 py-1">
                                                <?= htmlspecialchars($p['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editPost(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="?delete_blog_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this blog post?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">No posts created yet. Write your first post!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Categories Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-tag me-2 text-success"></i>Add Category</h5>
                
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name *</label>
                        <input type="text" name="cat_name" class="form-control" placeholder="e.g. Exam Tips" required onkeyup="generateCatSlug(this.value)">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Category Slug *</label>
                        <input type="text" name="cat_slug" id="cat_slug" class="form-control" placeholder="exam-tips" required>
                    </div>
                    <button type="submit" name="add_category" class="btn btn-success w-100 fw-semibold">
                        <i class="fa fa-plus-circle me-1"></i> Add Category
                    </button>
                </form>
            </div>

            <!-- Categories List -->
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-tags me-2 text-muted"></i>Available Categories</h5>
                <ul class="list-group list-group-flush">
                    <?php if (count($categories) > 0): ?>
                        <?php foreach($categories as $cat): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($cat['name']) ?></h6>
                                    <small class="text-muted font-monospace"><?= htmlspecialchars($cat['slug']) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item text-center text-muted px-0 py-3">No categories added.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function generateSlug(text) {
    const slug = text.toLowerCase()
                     .replace(/[^a-zA-Z0-9\s\-]/g, '')
                     .replace(/\s+/g, '-');
    document.getElementById('slug').value = slug;
}

function generateCatSlug(text) {
    const slug = text.toLowerCase()
                     .replace(/[^a-zA-Z0-9\s\-]/g, '')
                     .replace(/\s+/g, '-');
    document.getElementById('cat_slug').value = slug;
}

function editPost(post) {
    document.getElementById('blog_id').value = post.id;
    document.getElementById('title').value = post.title;
    document.getElementById('slug').value = post.slug;
    document.getElementById('category_id').value = post.category_id;
    document.getElementById('tags').value = post.tags;
    document.getElementById('content').value = post.content;
    document.getElementById('status').value = post.status;
    document.getElementById('meta_title').value = post.meta_title;
    document.getElementById('meta_description').value = post.meta_description;
    
    document.getElementById('form-title').innerHTML = '<i class="fa fa-edit me-2 text-primary"></i>Edit Blog Post: ' + post.title;
    document.getElementById('form-title').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('blog_id').value = "0";
    document.getElementById('title').value = "";
    document.getElementById('slug').value = "";
    document.getElementById('category_id').value = "0";
    document.getElementById('tags').value = "";
    document.getElementById('content').value = "";
    document.getElementById('status').value = "Draft";
    document.getElementById('meta_title').value = "";
    document.getElementById('meta_description').value = "";
    document.getElementById('form-title').innerHTML = '<i class="fa fa-pen-nib me-2 text-primary"></i>Write / Edit Blog Post';
}
</script>

<?php
require_once('includes/footer.php');
?>
