<?php
require_once __DIR__ . '/includes/BaseController.php';
require_once __DIR__ . '/config/database.php';

new BaseController();

// Revert logic listener handler setup code rule hook:
if (isset($_GET['revert_admin']) && isset($_SESSION['admin_backed_id'])) {
    $_SESSION['user_id'] = $_SESSION['admin_backed_id'];
    unset($_SESSION['admin_backed_id']);
    
    // Fetch profile variables context matching original database entries line row properties
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $adminOriginal = $stmt->fetch();

    $_SESSION['username'] = $adminOriginal['username'];
    $_SESSION['user_role'] = $adminOriginal['role'];
    
    header('Location: /school-app/admin/master-control.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School ERP Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<?php
// Dashboard header layout logic context configuration bar check:
if (isset($_SESSION['admin_backed_id'])) {
    echo "<div class='bg-warning p-2 text-center text-dark fw-bold small'>
            Aap abhi dusre user ka dashboard dekh rahe hain. Apne actual profile me wapas jaane ke liye yahan click karein: 
            <a href='/school-app/dashboard.php?revert_admin=1' class='btn btn-xs btn-dark ms-2 py-0 px-2 small'>Revert Back to Admin</a>
          </div>";
}
?>
<div class="container mt-5 text-center">
    <h1>Welcome to Dashboard</h1>
    <p>Logged in as <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?> (<?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Role'); ?>)</p>
</div>

<div class="container mt-4 text-center d-flex justify-content-center">
<?php
$db = getDBConnection();
$myUserId = $_SESSION['user_id'] ?? null;
$myRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;

if ($myRole === 'student' && $myUserId) {
    // 1. Fetch Student Current Mapped Class and Section Details
    $stmt = $db->prepare("SELECT class_name, section_name FROM students WHERE id = (SELECT id FROM users WHERE id = :uid) AND verification_status = 'ACTIVE' LIMIT 1");
    $stmt->execute([':uid' => $myUserId]);
    $studentInfo = $stmt->fetch();

    if ($studentInfo) {
        $myClass = $studentInfo['class_name'];
        $mySec = $studentInfo['section_name'];

        // 2. Fetch Global Group Link & Specific Class Group Link dynamically from mapping registry
        $linksStmt = $db->prepare("SELECT * FROM school_whatsapp_links WHERE (class_name = 'Global') OR (class_name = :class AND section_name = :sec)");
        $linksStmt->execute([':class' => $myClass, ':sec' => $mySec]);
        $myGroupLinks = $linksStmt->fetchAll();

        if (count($myGroupLinks) > 0) {
            echo "<div class='card border-0 shadow-sm p-3 mb-4 bg-white text-start' style='max-width: 600px; width: 100%;'>
                    <h6 class='fw-bold text-success mb-2'><i class='fab fa-whatsapp'></i> Aapke Official School WhatsApp Groups Linked</h6>
                    <div class='d-flex gap-2 flex-wrap'>";
                    foreach ($myGroupLinks as $link) {
                        $btnLabel = ($link['class_name'] === 'Global') ? "Join All School Global Group" : "Join Class Group ({$myClass}-{$mySec})";
                        echo "<a href='{$link['group_invite_url']}' target='_blank' class='btn btn-success btn-sm fw-bold'><i class='fas fa-external-link-alt'></i> {$btnLabel}</a>";
                    }
            echo "  </div>
                  </div>";
        }
    }
}

if ($myRole === 'teacher' && $myUserId) {
    // Dynamic Teacher Box Rule: Teacher ko unki saari assigned classes ke direct group connections link dikhenge
    // For this demonstration, we'll assume teacher has classes (this table unified_academic_mapping might not exist yet, we wrap it in a try block)
    try {
        $teacherStmt = $db->prepare("SELECT class_name, section_name FROM unified_academic_mapping WHERE teacher_id = :tid GROUP BY class_name, section_name");
        $teacherStmt->execute([':tid' => $myUserId]);
        $teacherClasses = $teacherStmt->fetchAll();

        if (count($teacherClasses) > 0) {
            echo "<div class='card border-0 shadow-sm p-3 mb-4 bg-white text-start' style='max-width: 600px; width: 100%;'>
                    <h6 class='fw-bold text-primary mb-2'><i class='fab fa-whatsapp'></i> My Assigned Class WhatsApp Groups (Faculty Grid)</h6>
                    <div class='d-flex flex-wrap gap-2'>";
                    foreach ($teacherClasses as $tc) {
                        // Fetch corresponding active dynamic group url nodes
                        $getLink = $db->prepare("SELECT group_invite_url FROM school_whatsapp_links WHERE class_name = :class AND section_name = :sec LIMIT 1");
                        $getLink->execute([':class' => $tc['class_name'], ':sec' => $tc['section_name']]);
                        $url = $getLink->fetchColumn();
                        
                        if ($url) {
                            echo "<a href='{$url}' target='_blank' class='btn btn-outline-success btn-sm fw-bold'>Open {$tc['class_name']}-{$tc['section_name']} Group Link</a>";
                        }
                    }
            echo "  </div>
                  </div>";
        }
    } catch (PDOException $e) {
        // Table may not exist yet, silent fallback
    }
}
?>
</div>
</body>
</html>
