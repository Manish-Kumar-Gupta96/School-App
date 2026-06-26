<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Fetch Issued Documents
// We need to union or conditionally fetch student vs staff name.
// For simplicity, we can fetch them separately and merge, or use left joins.
$stmt_issued = $pdo->prepare("
    SELECT 
        i.*,
        t.document_name,
        COALESCE(CONCAT_WS(' ', s.first_name, s.last_name), th.name) as name,
        COALESCE(s.admission_no, CONCAT('EMP-', th.id)) as identifier
    FROM issued_documents i
    JOIN document_templates t ON i.document_id = t.id
    LEFT JOIN students s ON i.user_type = 'student' AND i.user_id = s.id
    LEFT JOIN teachers th ON i.user_type = 'staff' AND i.user_id = th.id
    WHERE i.school_id = ?
    ORDER BY i.issue_date DESC
");
$stmt_issued->execute([CURRENT_SCHOOL_ID]);
$issuedList = $stmt_issued->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Issued Documents | VIC ERP";
$page_header = "Certificate & Document Automation";
$active_menu = "documents";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage automatic document generation and history</h5>
    <div class="d-flex gap-2">
        <a href="templates.php" class="btn btn-outline-primary">
            <i class="fa fa-file-code me-1"></i> Templates
        </a>
        <a href="generate.php" class="btn btn-outline-primary">
            <i class="fa fa-cogs me-1"></i> Generate Document
        </a>
        <a href="issued.php" class="btn btn-success">
            <i class="fa fa-history me-1"></i> Issued Documents
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">Document Issue Log</h5>
                <span class="badge bg-primary rounded-pill px-3 py-2"><?= count($issuedList) ?> Records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Document Type</th>
                                <th>Issued To</th>
                                <th>Issue Date</th>
                                <th>QR Code Ref</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($issuedList) > 0): ?>
                                <?php foreach($issuedList as $doc): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?= htmlspecialchars($doc['document_name']) ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-primary mb-0">
                                                <?= htmlspecialchars($doc['name']) ?> 
                                                <span class="badge bg-secondary-subtle text-secondary ms-1 small"><?= ucfirst($doc['user_type']) ?></span>
                                            </div>
                                            <div class="small text-muted mt-1">ID: <?= htmlspecialchars($doc['identifier']) ?></div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-calendar-check text-success me-1"></i> <?= date('d M Y, h:i A', strtotime($doc['issue_date'])) ?></div>
                                        </td>
                                        <td>
                                            <span class="font-monospace small bg-light px-2 py-1 border rounded text-muted">
                                                <i class="fa fa-qrcode me-1"></i> <?= htmlspecialchars($doc['qr_code']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <!-- Simulated Action button since we don't actually render PDF in this basic setup -->
                                            <button class="btn btn-sm btn-outline-info rounded-pill px-3" onclick="alert('In a full implementation, this would download the PDF from: \n<?= htmlspecialchars($doc['file_path']) ?>')">
                                                <i class="fa fa-download me-1"></i> View/Download
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fa fa-folder-open fs-2 mb-2 d-block"></i>
                                        No documents have been issued yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('../includes/footer.php'); ?>
