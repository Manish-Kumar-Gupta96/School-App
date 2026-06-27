<?php
require_once __DIR__ . '/../../includes/init.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_link'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Validation Interrupted.");
    
    $linkId = filter_input(INPUT_POST, 'link_id', FILTER_VALIDATE_INT);
    
    if ($linkId) {
        try {
            $stmt = $db->prepare("DELETE FROM school_whatsapp_links WHERE id = :id");
            $stmt->execute([':id' => $linkId]);
            
            AuditLogger::log('WHATSAPP_LINK_REVOKED', "Admin force-deleted WhatsApp access key link ID #{$linkId}");
            $message = "Alert: Selected WhatsApp Group link system se hata diya gaya hai. Ab students ko broken URL show nahi hoga.";
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); }
    }
}

$allLinks = $db->query("SELECT * FROM school_whatsapp_links ORDER BY class_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>WhatsApp Link Safety Control</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 850px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white font-weight-bold small d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fab fa-whatsapp"></i> WhatsApp Link Integrity & Security Auditor</h6>
            <span class="badge bg-white text-danger">Real-Time Threat Shield</span>
        </div>
        <div class="card-body p-0">
            <?php if(!empty($message)): ?><div class="alert alert-warning small rounded-0 py-2 mb-0"><?php echo $message; ?></div><?php endif; ?>
            
            <table class="table table-striped align-middle text-center mb-0 table-sm" style="font-size: 13px;">
                <thead class="table-secondary">
                    <tr>
                        <th>Class Scope</th>
                        <th>Section</th>
                        <th>Invite URL Buffer</th>
                        <th>Safety Status</th>
                        <th>Action Intercept</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($allLinks) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">System records me abhi koi link active nahi hai.</td></tr>
                    <?php else: ?>
                        <?php foreach ($allLinks as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['class_name']); ?></strong></td>
                            <td><span class="badge bg-dark"><?php echo $row['section_name'] ?: 'GLOBAL'; ?></span></td>
                            <td><code class="text-truncate d-block" style="max-width: 250px;"><?php echo htmlspecialchars($row['group_invite_url']); ?></code></td>
                            <td><span class="badge bg-success">BROADCASTING</span></td>
                            <td>
                                <form action="" method="POST" onsubmit="return confirm('Kya aap sach me is link ko delete karna chateh hain? Bache group join nahi kar payenge.');">
                                    <?php Csrf::injectInput(); ?>
                                    <input type="hidden" name="link_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="revoke_link" class="btn btn-outline-danger btn-sm py-0 fw-bold" style="font-size: 11px;">Revoke URL</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
