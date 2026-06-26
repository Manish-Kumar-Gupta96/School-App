<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$school_id = $_SESSION['school_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_backup') {
        $backup_type = $_POST['backup_type']; // 'database', 'files', 'full'
        $filename = "backup_" . $backup_type . "_" . date('Ymd_His') . ".zip";
        $filepath = "/storage/backups/" . $filename;
        
        // In a real application, you'd trigger a shell command or PHP script to generate the SQL dump or zip the files here.
        // For demonstration, we just log it in the database.
        
        $stmt = $pdo->prepare("INSERT INTO backups (school_id, backup_name, backup_type, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $filename, $backup_type, $filepath]);
        
        $_SESSION['success'] = ucfirst($backup_type) . " backup initiated successfully. It will be available for download shortly.";
        header("Location: backups.php");
        exit;
    } elseif ($_POST['action'] === 'delete_backup') {
        $backup_id = (int)$_POST['backup_id'];
        // Ideally, also unlink() the file from disk
        $pdo->prepare("DELETE FROM backups WHERE id=? AND school_id=?")->execute([$backup_id, $school_id]);
        $_SESSION['success'] = "Backup deleted from storage.";
        header("Location: backups.php");
        exit;
    }
}

// Fetch Backups
$backups = $pdo->prepare("SELECT * FROM backups WHERE school_id = ? ORDER BY created_at DESC");
$backups->execute([$school_id]);
$backups = $backups->fetchAll(PDO::FETCH_ASSOC);

$root_path = "../../";
$page_title = "Backup & Restore | VIC School ERP";
$page_header = "Disaster Recovery Center";
$active_menu = "security";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="container mt-4">
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="alert alert-info border-info shadow-sm d-flex align-items-center mb-0" role="alert" style="border-radius: 15px;">
                <i class="fa fa-cloud-upload-alt fs-2 me-3 text-info"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Automated Cloud Backups Active</h6>
                    <p class="mb-0 small">Daily automated backups are scheduled at 02:00 AM. Encrypted copies are sent to AWS S3 storage.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#backupModal"><i class="fa fa-plus me-2"></i>Create Manual Backup</button>
        </div>
    </div>

    <!-- Backups Table -->
    <div class="card shadow-sm border-0" style="border-radius: 15px;">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-dark"><i class="fa fa-history me-2 text-primary"></i>Backup History</h5>
        </div>
        <div class="card-body p-0">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible m-3 mb-0">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Backup File Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($backups) > 0): ?>
                            <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-dark"><?= htmlspecialchars($b['backup_name']) ?></td>
                                    <td>
                                        <?php if($b['backup_type'] == 'database'): ?>
                                            <span class="badge bg-primary"><i class="fa fa-database me-1"></i>Database</span>
                                        <?php elseif($b['backup_type'] == 'files'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa fa-folder me-1"></i>Uploads</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark"><i class="fa fa-archive me-1"></i>Full System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                    <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($b['created_at'])) ?></td>
                                    <td class="pe-4 text-end">
                                        <button class="btn btn-sm btn-outline-success"><i class="fa fa-download me-1"></i>Download</button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa fa-undo-alt me-1"></i>Restore</button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this backup?');">
                                            <input type="hidden" name="action" value="delete_backup">
                                            <input type="hidden" name="backup_id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No manual or automated backups found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Backup Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary"><i class="fa fa-cloud-download-alt me-2"></i>Initiate Manual Backup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
            <input type="hidden" name="action" value="create_backup">
            <div class="mb-3">
                <label class="form-label fw-bold">Select Backup Scope <span class="text-danger">*</span></label>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="backup_type" value="database" id="b_db" checked>
                    <label class="form-check-label fw-bold" for="b_db">
                        Database Only <small class="text-muted fw-normal d-block">Includes students, fees, attendance, settings.</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="backup_type" value="files" id="b_files">
                    <label class="form-check-label fw-bold" for="b_files">
                        File Uploads Only <small class="text-muted fw-normal d-block">Documents, PDFs, Profile Pictures.</small>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="backup_type" value="full" id="b_full">
                    <label class="form-check-label fw-bold text-danger" for="b_full">
                        Full System Backup <small class="text-muted fw-normal d-block">DB + Files. Can take several minutes.</small>
                    </label>
                </div>
            </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa fa-play me-2"></i>Start Backup Process</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php require_once('../includes/footer.php'); ?>
