<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("
    SELECT *
    FROM notices
    WHERE title LIKE ?
    ORDER BY id DESC
");
$stmt->execute(["%$search%"]);
$notices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Notice Board | VIC ERP";
$page_header = "Notice Board";
$active_menu = "notices";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Manage School Notices and Announcements</h5>
    <a href="add.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Add Notice
    </a>
</div>

<!-- ALERTS -->
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-trash me-2"></i> Notice Deleted Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> Notice Saved Successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- SEARCH -->
<form method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">
            <i class="fa fa-search me-1"></i> Search
        </button>
    </div>
</form>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" width="80">ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Expiry Date</th>
                        <th>For</th>
                        <th>Status</th>
                        <th class="text-center" width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($notices) > 0): ?>
                        <?php foreach($notices as $notice): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($notice['id']) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($notice['title']) ?></td>
                                <td><?= date('d M Y', strtotime($notice['notice_date'])) ?></td>
                                <td>
                                    <?= $notice['expiry_date'] && $notice['expiry_date'] !== '0000-00-00' ? date('d M Y', strtotime($notice['expiry_date'])) : '<span class="text-muted small">No Expiry</span>' ?>
                                </td>
                                <td>
                                    <?php
                                    $for = $notice['notice_for'];
                                    $badge_class = 'bg-secondary';
                                    if ($for == 'Students') $badge_class = 'bg-info-subtle text-info border border-info-subtle';
                                    elseif ($for == 'Teachers') $badge_class = 'bg-primary-subtle text-primary border border-primary-subtle';
                                    elseif ($for == 'Parents') $badge_class = 'bg-warning-subtle text-warning border border-warning-subtle';
                                    else $badge_class = 'bg-success-subtle text-success border border-success-subtle';
                                    ?>
                                    <span class="badge <?= $badge_class ?> px-3 py-2"><?= htmlspecialchars($for) ?></span>
                                </td>
                                <td>
                                    <?php if($notice['status'] == 'Published'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Published</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?= $notice['id'] ?>" class="btn btn-outline-info btn-sm">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <a href="edit.php?id=<?= $notice['id'] ?>" class="btn btn-outline-warning btn-sm">
                                            <i class="fa fa-edit"></i> Edit
                                        </a>
                                        <a href="delete.php?id=<?= $notice['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this notice?')">
                                            <i class="fa fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa fa-bullhorn fs-2 mb-2 d-block"></i>
                                No notices found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
