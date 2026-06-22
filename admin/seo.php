<?php
require_once('../config/database.php');
$root_path = "../";
$page_title = "SEO & Metadata Manager | Admin Portal";
$active_menu = "seo";
require_once('includes/header.php');
require_once('includes/topbar.php');

$message = '';
$error = '';

// Handle Global SEO Save
if (isset($_POST['save_global'])) {
    $site_name = trim($_POST['site_name']);
    $default_title = trim($_POST['default_meta_title']);
    $default_desc = trim($_POST['default_meta_description']);
    $default_keys = trim($_POST['default_meta_keywords']);
    $robots_txt = trim($_POST['robots_txt']);
    
    try {
        // Check if exists
        $check = $pdo->query("SELECT COUNT(*) FROM seo_settings")->fetchColumn();
        if ($check > 0) {
            $stmt = $pdo->prepare("
                UPDATE seo_settings 
                SET site_name = ?, default_meta_title = ?, default_meta_description = ?, default_meta_keywords = ?, robots_txt = ?
                LIMIT 1
            ");
            $stmt->execute([$site_name, $default_title, $default_desc, $default_keys, $robots_txt]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO seo_settings (site_name, default_meta_title, default_meta_description, default_meta_keywords, robots_txt)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$site_name, $default_title, $default_desc, $default_keys, $robots_txt]);
        }
        $message = "Global SEO settings updated successfully!";
    } catch (Exception $e) {
        $error = "Failed to save global SEO: " . $e->getMessage();
    }
}

// Handle Add / Edit Page SEO
if (isset($_POST['save_page'])) {
    $page_path = trim($_POST['page_path']);
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $canonical_url = trim($_POST['canonical_url']);
    $schema_markup = trim($_POST['schema_markup']);
    
    if (empty($page_path)) {
        $error = "Page path is required (e.g. /about.php)";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM seo_pages WHERE page_path = ?");
            $check->execute([$page_path]);
            $existing_id = $check->fetchColumn();
            
            if ($existing_id) {
                $stmt = $pdo->prepare("
                    UPDATE seo_pages
                    SET meta_title = ?, meta_description = ?, canonical_url = ?, schema_markup = ?
                    WHERE id = ?
                ");
                $stmt->execute([$meta_title, $meta_description, $canonical_url, $schema_markup, $existing_id]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO seo_pages (page_path, meta_title, meta_description, canonical_url, schema_markup)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$page_path, $meta_title, $meta_description, $canonical_url, $schema_markup]);
            }
            $message = "Page-specific SEO settings updated!";
        } catch (Exception $e) {
            $error = "Failed to save page SEO: " . $e->getMessage();
        }
    }
}

// Handle Delete Page SEO
if (isset($_GET['delete_page_id'])) {
    $del_id = (int)$_GET['delete_page_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM seo_pages WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Page override deleted successfully.";
    } catch (Exception $e) {
        $error = "Failed to delete: " . $e->getMessage();
    }
}

// Load Global Settings
$global_seo = $pdo->query("SELECT * FROM seo_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [
    'site_name' => 'VIC School',
    'default_meta_title' => 'VIC School - Inspiring Excellence',
    'default_meta_description' => '',
    'default_meta_keywords' => '',
    'robots_txt' => ''
];

// Load Page Configs
$pages_seo = $pdo->query("SELECT * FROM seo_pages ORDER BY page_path ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
        <div>
            <h2 class="fw-bold text-dark mb-1">🔍 SEO & Website Metadata Settings</h2>
            <p class="text-muted mb-0">Configure global metadata headers, customize page-specific canonicals/titles, and write dynamic schema markups for search optimization.</p>
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
        <!-- Global Settings Column -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-globe me-2 text-primary"></i>Global Site Config</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Site Brand Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($global_seo['site_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Browser Meta Title</label>
                        <input type="text" name="default_meta_title" class="form-control" value="<?= htmlspecialchars($global_seo['default_meta_title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Meta Description</label>
                        <textarea name="default_meta_description" class="form-control" rows="3" placeholder="Provide description for search engines..."><?= htmlspecialchars($global_seo['default_meta_description']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Meta Keywords</label>
                        <input type="text" name="default_meta_keywords" class="form-control" placeholder="comma, separated, keywords" value="<?= htmlspecialchars($global_seo['default_meta_keywords']) ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">robots.txt Directives</label>
                        <textarea name="robots_txt" class="form-control font-monospace" rows="5" placeholder="User-agent: *..."><?= htmlspecialchars($global_seo['robots_txt']) ?></textarea>
                    </div>
                    <button type="submit" name="save_global" class="btn btn-primary w-100 fw-semibold">
                        <i class="fa fa-save me-1"></i> Save Global Configurations
                    </button>
                </form>
            </div>
        </div>

        <!-- Page Specific Configuration -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 p-4 mb-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-file-signature me-2 text-success"></i>Customize Page SEO / Schema</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Page Relative Path *</label>
                        <input type="text" name="page_path" class="form-control" placeholder="e.g. /about.php or /admissions.php" required id="page_path">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Override Title</label>
                        <input type="text" name="meta_title" class="form-control" placeholder="Specific page browser title" id="meta_title">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Override Description</label>
                        <textarea name="meta_description" class="form-control" rows="3" placeholder="Specific search snippet..." id="meta_description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Canonical URL Override</label>
                        <input type="url" name="canonical_url" class="form-control" placeholder="https://example.com/page" id="canonical_url">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">JSON-LD Schema Markup</label>
                        <textarea name="schema_markup" class="form-control font-monospace" rows="4" placeholder='<script type="application/ld+json">...</script>' id="schema_markup"></textarea>
                    </div>
                    <button type="submit" name="save_page" class="btn btn-success w-100 fw-semibold">
                        <i class="fa fa-plus-circle me-1"></i> Save / Override Page Settings
                    </button>
                </form>
            </div>

            <!-- Page Config list -->
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-3"><i class="fa fa-list me-2 text-muted"></i>Current Page Overrides</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Path</th>
                                <th>Meta Title</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pages_seo) > 0): ?>
                                <?php foreach($pages_seo as $p): ?>
                                    <tr>
                                        <td class="font-monospace small"><?= htmlspecialchars($p['page_path']) ?></td>
                                        <td class="small"><?= htmlspecialchars($p['meta_title']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" onclick="editPage(<?= htmlspecialchars(json_encode($p)) ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <a href="?delete_page_id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this page override?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4 small">No specific page overrides configured yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editPage(page) {
    document.getElementById('page_path').value = page.page_path;
    document.getElementById('meta_title').value = page.meta_title;
    document.getElementById('meta_description').value = page.meta_description;
    document.getElementById('canonical_url').value = page.canonical_url;
    document.getElementById('schema_markup').value = page.schema_markup;
    document.getElementById('page_path').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php
require_once('includes/footer.php');
?>
