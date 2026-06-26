<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_seo') {
        $id = (int)$_POST['id'];
        $page_slug = trim($_POST['page_slug']);
        $meta_title = trim($_POST['meta_title']);
        $meta_description = trim($_POST['meta_description']);
        $meta_keywords = trim($_POST['meta_keywords']);
        $canonical_url = trim($_POST['canonical_url']);
        
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE seo_settings SET page_slug=?, meta_title=?, meta_description=?, meta_keywords=?, canonical_url=? WHERE id=?");
            $stmt->execute([$page_slug, $meta_title, $meta_description, $meta_keywords, $canonical_url, $id]);
            $_SESSION['success'] = "SEO updated successfully.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO seo_settings (page_slug, meta_title, meta_description, meta_keywords, canonical_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$page_slug, $meta_title, $meta_description, $meta_keywords, $canonical_url]);
            $_SESSION['success'] = "SEO added successfully.";
        }
        header("Location: seo.php");
        exit;
    } elseif ($_POST['action'] === 'delete_seo') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM seo_settings WHERE id=?")->execute([$id]);
        $_SESSION['success'] = "SEO setting deleted.";
        header("Location: seo.php");
        exit;
    }
}

$seo_entries = $pdo->query("SELECT * FROM seo_settings ORDER BY page_slug ASC")->fetchAll(PDO::FETCH_ASSOC);

$edit_seo = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM seo_settings WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_seo = $stmt->fetch(PDO::FETCH_ASSOC);
}

$root_path = "../../";
$page_title = "SEO Manager | VIC School ERP";
$page_header = "Search Engine Optimization";
$active_menu = "cms";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4">
        <!-- Manage SEO Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="fa fa-search me-2"></i><?= $edit_seo ? 'Edit SEO Entry' : 'Add Custom SEO' ?></h5>
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
                        <input type="hidden" name="action" value="save_seo">
                        <input type="hidden" name="id" value="<?= $edit_seo['id'] ?? 0 ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Target Page Slug <span class="text-danger">*</span></label>
                            <input type="text" name="page_slug" class="form-control" value="<?= htmlspecialchars($edit_seo['page_slug'] ?? '') ?>" required placeholder="e.g. /admissions">
                            <small class="text-muted">Use 'global' for site-wide defaults.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="<?= htmlspecialchars($edit_seo['meta_title'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="3"><?= htmlspecialchars($edit_seo['meta_description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" value="<?= htmlspecialchars($edit_seo['meta_keywords'] ?? '') ?>" placeholder="Comma separated keywords">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Canonical URL</label>
                            <input type="url" name="canonical_url" class="form-control" value="<?= htmlspecialchars($edit_seo['canonical_url'] ?? '') ?>" placeholder="https://vicschool.com/admissions">
                        </div>

                        <div class="d-flex justify-content-between">
                            <?php if ($edit_seo): ?>
                                <a href="seo.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-success"><i class="fa fa-save me-2"></i> Save SEO</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SEO Entries List -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0" style="border-radius: 15px;">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-list me-2 text-primary"></i>Configured Routes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Target Slug</th>
                                    <th>Meta Data</th>
                                    <th class="pe-4 text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($seo_entries) > 0): ?>
                                    <?php foreach ($seo_entries as $seo): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge bg-primary fs-6"><?= htmlspecialchars($seo['page_slug']) ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($seo['meta_title'] ?: 'No Title') ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 250px;"><?= htmlspecialchars($seo['meta_description'] ?: 'No Description') ?></div>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="?edit=<?= $seo['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this SEO configuration?');">
                                                    <input type="hidden" name="action" value="delete_seo">
                                                    <input type="hidden" name="id" value="<?= $seo['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-5 text-muted">No custom SEO configurations.</td></tr>
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
