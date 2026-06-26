<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_resource') {
        $title = trim($_POST['title']);
        $resource_type = $_POST['resource_type'];
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        
        // Mock file upload - in a real app, use move_uploaded_file()
        $file_path = '';
        if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/library/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_name = time() . '_' . basename($_FILES['resource_file']['name']);
            $file_path = 'uploads/library/' . $file_name;
            move_uploaded_file($_FILES['resource_file']['tmp_name'], '../../' . $file_path);
        } else if (!empty($_POST['url_link'])) {
            $file_path = $_POST['url_link'];
        }
        
        if (!empty($title) && !empty($file_path)) {
            $stmt = $pdo->prepare("INSERT INTO digital_resources (title, resource_type, file_path, class_id, subject_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $resource_type, $file_path, $class_id, $subject_id, $_SESSION['user_id']]);
            $_SESSION['success'] = "Digital resource uploaded successfully.";
        } else {
            $_SESSION['error'] = "Please provide a file or link along with the title.";
        }
        header("Location: digital.php");
        exit;
    } elseif ($_POST['action'] === 'delete_resource') {
        $id = (int)$_POST['id'];
        $pdo->prepare("DELETE FROM digital_resources WHERE id = ?")->execute([$id]);
        $_SESSION['success'] = "Resource deleted.";
        header("Location: digital.php");
        exit;
    }
}

// Fetch resources
$resources = $pdo->query("
    SELECT r.*, c.class_name, s.subject_name 
    FROM digital_resources r
    LEFT JOIN classes c ON r.class_id = c.id
    LEFT JOIN subjects s ON r.subject_id = s.id
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch classes and subjects for dropdown
$classes = $pdo->query("SELECT * FROM classes")->fetchAll(PDO::FETCH_ASSOC);
$subjects = $pdo->query("SELECT * FROM subjects")->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Digital Library | VIC School ERP";
$page_header = "Digital Library Management";
$active_menu = "library";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <!-- Add Resource Section -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white" data-bs-toggle="collapse" data-bs-target="#addResourceForm" style="cursor:pointer;">
            <h5 class="mb-0 fw-bold"><i class="fa fa-cloud-upload-alt me-2 text-primary"></i>Upload Digital Resource <span class="float-end"><i class="fa fa-chevron-down"></i></span></h5>
        </div>
        <div id="addResourceForm" class="collapse">
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

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_resource">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Resource Type</label>
                            <select name="resource_type" class="form-select" required>
                                <option value="PDF">PDF Document</option>
                                <option value="E-Book">E-Book</option>
                                <option value="Video">Video Link</option>
                                <option value="Notes">Study Notes</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <select name="class_id" class="form-select">
                                <option value="0">All Classes</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <select name="subject_id" class="form-select">
                                <option value="0">All Subjects</option>
                                <?php foreach($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload File</label>
                            <input type="file" name="resource_file" class="form-control">
                            <small class="text-muted">For PDFs and documents.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">OR External URL</label>
                            <input type="url" name="url_link" class="form-control" placeholder="https://youtube.com/...">
                            <small class="text-muted">For videos or external links.</small>
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="submit" class="btn btn-primary px-4"><i class="fa fa-upload me-2"></i> Upload Resource</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Resource List Section -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fa fa-folder-open me-2 text-info"></i>Digital Resources</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="Search resources...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="resourcesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Title</th>
                            <th>Type</th>
                            <th>Class / Subject</th>
                            <th>Uploaded On</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resources) > 0): ?>
                            <?php foreach ($resources as $r): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['title']) ?></div>
                                        <a href="<?= htmlspecialchars(strpos($r['file_path'], 'http') === 0 ? $r['file_path'] : $root_path . $r['file_path']) ?>" target="_blank" class="text-primary small">View Resource <i class="fa fa-external-link-alt ms-1"></i></a>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($r['resource_type']) ?></span></td>
                                    <td>
                                        <div class="text-dark"><?= htmlspecialchars($r['class_name'] ?: 'All Classes') ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($r['subject_name'] ?: 'All Subjects') ?></div>
                                    </td>
                                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                                    <td class="pe-4 text-end">
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this resource?');">
                                            <input type="hidden" name="action" value="delete_resource">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No digital resources uploaded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#resourcesTable tbody tr');
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require_once('../includes/footer.php'); ?>
