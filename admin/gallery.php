<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "Manage School Gallery | Admin Control";
$active_menu = "gallery";
require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

// Handle Image Upload
if (isset($_POST['upload_image'])) {
    $title    = trim($_POST['title']);
    $category = $_POST['category']; // 'campus', 'sports', 'events', 'academics'

    if (empty($title) || empty($category) || empty($_FILES['image']['name'])) {
        $error = "Title, Category, and Image file are required.";
    } else {
        try {
            // Validate image using global helper
            $validation = validate_uploaded_file($_FILES['image'], ['jpg', 'jpeg', 'png', 'webp', 'gif'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
            if ($validation !== true) {
                $error = $validation;
            } else {
                $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9\._-]/", "", $_FILES['image']['name']);
                $upload_dir = '../uploads/gallery/';
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $file_name)) {
                $stmt = $pdo->prepare("
                    INSERT INTO gallery (title, category, image_path)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$title, $category, 'uploads/gallery/' . $file_name]);
                
                // Log Audit
                require_once('includes/audit-helper.php');
                addAuditLog(
                    $pdo,
                    $_SESSION['user_id'],
                    $_SESSION['name'] ?? 'Admin',
                    $_SESSION['role'],
                    'Uploaded image to Gallery: ' . $title,
                    'Gallery'
                );
                
                $message = "Image uploaded and published successfully!";
            } else {
                $error = "Failed to upload image to directory.";
            }
            }
        } catch (Exception $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    }
}

// Handle Delete Image
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    try {
        // Fetch image path
        $stmt_img = $pdo->prepare("SELECT image_path, title FROM gallery WHERE id = ?");
        $stmt_img->execute([$delete_id]);
        $img = $stmt_img->fetch(PDO::FETCH_ASSOC);
        
        if ($img) {
            $full_path = '../' . $img['image_path'];
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            
            $stmt_del = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
            $stmt_del->execute([$delete_id]);
            
            // Log Audit
            require_once('includes/audit-helper.php');
            addAuditLog(
                $pdo,
                $_SESSION['user_id'],
                $_SESSION['name'] ?? 'Admin',
                $_SESSION['role'],
                'Deleted image from Gallery: ' . $img['title'],
                'Gallery',
                $delete_id
            );
            
            $message = "Image deleted successfully.";
        }
    } catch (Exception $e) {
        $error = "Failed to delete image: " . $e->getMessage();
    }
}

// Fetch all images
$images = $pdo->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🖼️ School Gallery Manager</h2>
            <p class="text-muted mb-0">Upload new campus pictures, sports meets, and student event highlights to display on the public website.</p>
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
        <!-- Upload Form -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-upload me-2 text-primary"></i>Upload New Image</h5>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Image Title / Caption</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Football match celebration" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="campus">Campus</option>
                            <option value="sports">Sports</option>
                            <option value="events">Events</option>
                            <option value="academics">Academics</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Select Photo File</label>
                        <input type="file" name="image" class="form-control" required accept="image/*">
                        <div class="form-text">Accepted format: JPG, JPEG, PNG. Max size 2MB.</div>
                    </div>

                    <button type="submit" name="upload_image" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa fa-plus-circle me-1"></i> Upload Image
                    </button>
                </form>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px; min-height: 400px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-images me-2 text-primary"></i>Current Published Photos (<?= count($images) ?>)</h5>
                
                <?php if (count($images) > 0): ?>
                    <div class="row g-3">
                        <?php foreach($images as $img): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="card h-100 border shadow-sm" style="border-radius: 10px; overflow: hidden;">
                                    <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;" alt="Gallery">
                                    <div class="card-body p-3 text-start">
                                        <h6 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($img['title']) ?></h6>
                                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 mb-3 text-capitalize"><?= htmlspecialchars($img['category']) ?></span>
                                        <div class="text-end">
                                            <a href="?delete_id=<?= $img['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this photo?');">
                                                <i class="fa fa-trash-alt me-1"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-images fs-1 d-block mb-3 text-secondary"></i>
                        No dynamic photos uploaded yet. Public gallery is using static local fallbacks.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once('includes/footer.php');
?>
