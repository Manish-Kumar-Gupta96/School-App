<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Publish School News | Admin Control";
$active_menu = "notices";
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $title       = trim($_POST['title']);
    $category    = trim($_POST['category']);
    $description = trim($_POST['description']);

    if(empty($title) || empty($description)){
        $error = "Title and Description are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO news (title, description, category, created_by)
            VALUES (?,?,?,?)
        ");
        $stmt->execute([$title, $description, $category, 1]);
        $message = "News article published successfully!";
    }
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📰 Publish School News</h2>
            <p class="text-muted mb-0">Publish official articles, highlights, or press updates on the front website page.</p>
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

    <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px; max-width: 700px;">
        <h5 class="fw-bold text-dark mb-4"><i class="fa fa-newspaper me-2 text-primary"></i>New News Article</h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-8">
                <label class="form-label fw-semibold">Article Title</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Science Fair Exhibition Winners" required>
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold">Category</label>
                <input type="text" name="category" class="form-control" placeholder="e.g. Academic, Sports" required>
            </div>

            <div class="col-md-12">
                <label class="form-label fw-semibold">Article Content</label>
                <textarea name="description" class="form-control" rows="6" placeholder="Write the details of the news article here..." required></textarea>
            </div>

            <div class="col-md-12 mt-4 text-end">
                <button type="submit" name="save" class="btn btn-primary px-5 py-2 fw-semibold">
                    <i class="fa fa-bullhorn me-1"></i> Publish News
                </button>
            </div>
        </form>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
