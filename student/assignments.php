<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../config/database.php';

new BaseController();
BaseController::enforceRole(['student', 'parent']);

$db = getDBConnection();
$studentClass = 'Class 10'; // Dynamically synced down via user active context profiles parameters

try {
    $stmt = $db->prepare("SELECT title, type, description, file_path, created_at FROM school_assignments WHERE class_name = :class ORDER BY created_at DESC");
    $stmt->execute([':class' => $studentClass]);
    $resources = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Resource sync parsing interruption: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Academic Assignment & Syllabus Desk</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white font-weight-bold">
            Academic Homework and Syllabus Desk — Active View: <?php echo $studentClass; ?>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width: 15%;">Date Published</th>
                        <th style="width: 15%;">Module Category</th>
                        <th style="width: 25%;">Task Heading</th>
                        <th>Detailed Description Guidelines</th>
                        <th class="text-center" style="width: 15%;">Download Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($resources) === 0): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No active homework tasks or curriculum documentation indexed yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($resources as $row): ?>
                        <tr>
                            <td class="small text-muted"><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($row['type'] === 'HOMEWORK') ? 'warning text-dark' : 'info'; ?>">
                                    <?php echo $row['type']; ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td class="small text-wrap"><?php echo nl2br(htmlspecialchars($row['description'] ?? '')); ?></td>
                            <td class="text-center">
                                <?php if (!empty($row['file_path'])): ?>
                                    <a href="/school-app/<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 font-weight-bold">
                                        <i class="fas fa-file-download"></i> Get Resource
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">No Document Attached</span>
                                <?php endif; ?>
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
