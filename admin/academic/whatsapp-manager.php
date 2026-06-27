<?php
require_once __DIR__ . '/../../includes/init.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();
$message = '';
$status = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_whatsapp_link'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Check Failed.");

    $className = filter_input(INPUT_POST, 'class_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $sectionName = filter_input(INPUT_POST, 'section_name', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $groupUrl = filter_input(INPUT_POST, 'group_invite_url', FILTER_VALIDATE_URL);

    if ($className && $groupUrl) {
        try {
            $stmt = $db->prepare("INSERT INTO school_whatsapp_links (class_name, section_name, group_invite_url) 
                                  VALUES (:class, :sec, :url) 
                                  ON DUPLICATE KEY UPDATE group_invite_url = :url");
            $stmt->execute([':class' => $className, ':sec' => $sectionName, ':url' => $groupUrl]);
            
            AuditLogger::log('WHATSAPP_LINK_UPDATED', "Updated WhatsApp link for {$className} {$sectionName}");
            $message = "Success! WhatsApp Group Invite link system me securely lock ho gaya hai.";
        } catch (PDOException $e) { $message = "Error: " . $e->getMessage(); $status = false; }
    } else { $message = "Kripya ek valid chat.whatsapp.com URL enter karein."; $status = false; }
}

$allLinks = $db->query("SELECT * FROM school_whatsapp_links ORDER BY class_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>WhatsApp Integration Center</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
<div class="container-fluid px-4 my-5" style="max-width: 1000px;">
    <h5 class="fw-bold text-dark mb-4"><i class="fab fa-whatsapp text-success"></i> School ERP Automated WhatsApp Connect Hub</h5>
    
    <?php if(!empty($message)): ?><div class="alert alert-info small py-2"><?php echo $message; ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white fw-bold small">Map WhatsApp Group Invite Links</div>
                <div class="card-body">
                    <form action="" method="POST">
                        <?php Csrf::injectInput(); ?>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Class Assignment Scope</label>
                            <select name="class_name" class="form-select form-select-sm" required>
                                <option value="Global">All School (Global Student Group)</option>
                                <option value="Class 10">Class 10 Matrix</option>
                                <option value="Class 11">Class 11 Matrix</option>
                                <option value="Class 12">Class 12 Matrix</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Section Division (Leave if Global)</label>
                            <select name="section_name" class="form-select form-select-sm">
                                <option value="">-- None (Global) --</option>
                                <option value="Sec-A">Sec-A</option>
                                <option value="Sec-B">Sec-B</option>
                                <option value="Sec-C">Sec-C</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">WhatsApp Group Invite URL</label>
                            <input type="url" name="group_invite_url" class="form-control form-control-sm" placeholder="https://chat.whatsapp.com/..." required>
                        </div>
                        <button type="submit" name="save_whatsapp_link" class="btn btn-success btn-sm w-100 fw-bold">Deploy and Broadcast Link</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold text-dark border-bottom">Active Sync Registry Links</div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped text-center mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr><th>Target Scope</th><th>Section</th><th>Invite Node Link</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach($allLinks as $row): ?>
                            <tr>
                                <td><strong><?php echo $row['class_name']; ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo $row['section_name'] ?: 'GLOBAL'; ?></span></td>
                                <td><a href="<?php echo $row['group_invite_url']; ?>" target="_blank" class="small text-truncate d-block" style="max-width: 250px;"><?php echo $row['group_invite_url']; ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
