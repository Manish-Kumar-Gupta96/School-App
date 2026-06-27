<?php
// Public Endpoint File — No Gating Roles Required
require_once __DIR__ . '/config/database.php';
$db = getDBConnection();

try {
    // Only pull entries marked as active seamlessly
    $stmt = $db->query("SELECT title, content, category, created_at FROM school_notices WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10");
    $publicNotices = $stmt->fetchAll();
} catch (PDOException $e) {
    $publicNotices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Official Web Notice Board | VIC Academy</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, sans-serif; }
        .notice-card { border: none; border-left: 5px solid #0d6efd; border-radius: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .category-EVENT { border-left-color: #198754; }
        .category-HOLIDAY { border-left-color: #ffc107; }
    </style>
</head>
<body>
<div class="container my-5" style="max-width: 850px;">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark">VIC Academy Circulars & Notice Board</h2>
        <p class="text-muted small">Real-time updates, news, and academic event sequences</p>
        <hr class="w-25 mx-auto bg-dark">
    </div>

    <?php if (count($publicNotices) === 0): ?>
        <div class="alert alert-light text-center py-4 border">No global announcements are floating current vectors.</div>
    <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($publicNotices as $item): ?>
                <div class="card notice-card category-<?php echo $item['category']; ?> p-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge <?php 
                            echo $item['category'] === 'NOTICE' ? 'bg-primary' : ($item['category'] === 'EVENT' ? 'bg-success' : 'bg-warning text-dark'); 
                        ?> font-weight-bold small"><?php echo $item['category']; ?></span>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($item['created_at'])); ?></small>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($item['title']); ?></h5>
                    <p class="text-secondary small mb-0 style-wrap"><?php echo nl2br(htmlspecialchars($item['content'])); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
