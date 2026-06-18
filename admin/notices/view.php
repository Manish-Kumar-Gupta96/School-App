<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location: index.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->execute([$id]);
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$notice){
    die("Notice Not Found");
}

// Layout setup
$root_path = "../../";
$page_title = "View Notice | VIC ERP";
$page_header = "Notice Details";
$active_menu = "notices";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<style>
    @media print {
        .sidebar, .topbar, .no-print, .btn, footer {
            display: none !important;
        }
        .main {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .notice-content {
            font-size: 14pt;
            line-height: 1.6;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="text-muted mb-0">Review the published notice details</h5>
    <div>
        <a href="index.php" class="btn btn-secondary me-2">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
        <a href="edit.php?id=<?= $notice['id'] ?>" class="btn btn-warning me-2">
            <i class="fa fa-edit me-1"></i> Edit
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa fa-print me-1"></i> Print Notice
        </button>
    </div>
</div>

<div class="card shadow border-0" style="border-radius: 15px;">
    <div class="card-body p-5">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-4">
            <div>
                <h2 class="fw-bold text-dark mb-2"><?= htmlspecialchars($notice['title']) ?></h2>
                <div class="text-muted mb-2">
                    <span class="me-3"><i class="fa fa-calendar-alt me-1"></i> <strong>Published Date:</strong> <?= date('d M Y', strtotime($notice['notice_date'])) ?></span>
                    <?php if ($notice['expiry_date'] && $notice['expiry_date'] !== '0000-00-00'): ?>
                        <span><i class="fa fa-calendar-times me-1"></i> <strong>Expiry Date:</strong> <?= date('d M Y', strtotime($notice['expiry_date'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php
                $for = $notice['notice_for'];
                $badge_class = 'bg-secondary';
                if ($for == 'Students') $badge_class = 'bg-info-subtle text-info border border-info-subtle';
                elseif ($for == 'Teachers') $badge_class = 'bg-primary-subtle text-primary border border-primary-subtle';
                elseif ($for == 'Parents') $badge_class = 'bg-warning-subtle text-warning border border-warning-subtle';
                else $badge_class = 'bg-success-subtle text-success border border-success-subtle';
                ?>
                <span class="badge <?= $badge_class ?> px-3 py-2 fs-6">Audience: <?= htmlspecialchars($for) ?></span>
                
                <?php if($notice['status'] == 'Published'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">Published</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fs-6">Draft</span>
                <?php endif; ?>
            </div>
        </div>

        <hr class="mb-4">

        <div class="notice-content text-dark mb-5" style="min-height: 200px;">
            <?= $notice['description'] ?>
        </div>

        <?php if(!empty($notice['attachment']) && file_exists("../../uploads/notices/" . $notice['attachment'])): ?>
            <div class="p-3 bg-light rounded d-flex align-items-center justify-content-between border no-print" style="border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fa fa-file-pdf text-danger fs-1 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-0">Attached Document</h6>
                        <span class="text-muted small">notice_attachment_<?= htmlspecialchars($notice['id']) ?></span>
                    </div>
                </div>
                <div>
                    <a href="../../uploads/notices/<?= htmlspecialchars($notice['attachment']) ?>" target="_blank" class="btn btn-outline-primary me-2">
                        <i class="fa fa-eye me-1"></i> View Attachment
                    </a>
                    <a href="../../uploads/notices/<?= htmlspecialchars($notice['attachment']) ?>" download class="btn btn-primary">
                        <i class="fa fa-download me-1"></i> Download
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
