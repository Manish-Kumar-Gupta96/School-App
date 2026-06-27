<?php
require_once __DIR__ . '/../../includes/BaseController.php';
require_once __DIR__ . '/../../includes/Csrf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['admin', 'superadmin']);

$db = getDBConnection();
$message = '';
$status = true;

// Handle Notice Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['publish_notice'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) {
        die("Security Exception: CSRF handshake invalid.");
    }

    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
    $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($title) || empty($content) || empty($category)) {
        $message = "All announcement fields are strictly required.";
        $status = false;
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO school_notices (title, content, category, created_by) VALUES (:title, :content, :cat, :uid)");
            $stmt->execute([
                ':title'   => $title,
                ':content' => $content,
                ':cat'     => $category,
                ':uid'     => $_SESSION['user_id']
            ]);

            AuditLogger::log('NOTICE_PUBLISHED', "Broadcasted global announcement: {$title}");
            $message = "Announcement packet published live to the public matrix!";
        } catch (PDOException $e) {
            $message = "Storage Error: " . $e->getMessage();
            $status = false;
        }
    }
}

// Fetch recently published items
$allNotices = $db->query("SELECT * FROM school_notices ORDER BY created_at DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Global Broadcast Console</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5">
    <div class="row">
        <!-- Publisher Panel Form -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold">Compose Live Announcement</div>
                <div class="card-body">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <?php Csrf::injectInput(); ?>
                        <div class="mb-2">
                            <label class="form-label small font-weight-bold">Notice Title</label>
                            <input type="text" name="title" class="form-control form-control-sm" placeholder="e.g. Mid-Term Examination Schedule" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small font-weight-bold">Classification Category</label>
                            <select name="category" class="form-select form-select-sm" required>
                                <option value="NOTICE">Official Notice Circular</option>
                                <option value="EVENT">Institutional Event</option>
                                <option value="HOLIDAY">Holiday/Absence Alert</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small font-weight-bold">Notice Body Content Description</label>
                            <textarea name="content" class="form-control form-control-sm" rows="5" placeholder="Type official notice details explicitly here..." required></textarea>
                        </div>
                        <button type="submit" name="publish_notice" class="btn btn-danger btn-sm w-100 font-weight-bold">Broadcast Announcement Node</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Monitoring Data Array -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white font-weight-bold text-dark">Currently Active Stream Records</div>
                <div class="card-body p-0">
                    <table class="table table-striped table-sm align-middle mb-0">
                        <thead class="table-secondary">
                            <tr>
                                <th>Timestamp</th>
                                <th>Category</th>
                                <th>Headline Topic</th>
                                <th>Content Snip</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allNotices as $row): ?>
                            <tr>
                                <td class="small text-muted"><?php echo $row['created_at']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['category'] === 'NOTICE' ? 'primary' : ($row['category'] === 'EVENT' ? 'success' : 'warning text-dark'); ?>">
                                        <?php echo $row['category']; ?>
                                    </span>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                <td class="small text-muted text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($row['content']); ?></td>
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
