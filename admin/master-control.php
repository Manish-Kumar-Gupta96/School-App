<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

new BaseController();
BaseController::enforceRole(['superadmin', 'admin']);

$db = getDBConnection();

// IMPERSONATION ENGINE LOGIC INJECTION: Single Click Dashboard Switcher Hook
if (isset($_GET['impersonate_user_id'])) {
    $targetId = filter_input(INPUT_GET, 'impersonate_user_id', FILTER_VALIDATE_INT);
    
    try {
        $stmt = $db->prepare("SELECT u.id, u.name, r.role_name as role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id");
        $stmt->execute([':id' => $targetId]);
        $targetUser = $stmt->fetch();

        if ($targetUser) {
            // Save current Admin session credentials before switching flags
            $_SESSION['admin_backed_id'] = $_SESSION['user_id'];
            
            // Rewrite system target context identifiers
            $_SESSION['user_id'] = $targetUser['id'];
            $_SESSION['name'] = $targetUser['name'];
            $_SESSION['role'] = $targetUser['role'];

            AuditLogger::log('IMPERSONATION_TRIGGERED', "Admin switched interface into User ID #{$targetId} [{$targetUser['name']}] dashboard view.");
            header('Location: /school-app/dashboard.php');
            exit;
        }
    } catch (PDOException $e) { die("Impersonation routing failed: " . $e->getMessage()); }
}

$allUsers = $db->query("SELECT u.id, u.name, u.email, r.role_name as role, u.password FROM users u JOIN roles r ON u.role_id = r.id ORDER BY r.role_name ASC, u.name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Master Admin Control Console</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container-fluid px-4 mt-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white fw-bold">Master Security Control: Users Passkey Mapping & Direct Access Deck</div>
        <div class="card-body p-0">
            <table class="table table-striped text-center mb-0 table-sm align-middle">
                <thead class="table-secondary">
                    <tr>
                        <th>User ID</th>
                        <th>User Principal ID</th>
                        <th>Email Scope</th>
                        <th>Role Node</th>
                        <th>Action Framework</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $user): ?>
                    <tr>
                        <td><code>#<?php echo $user['id']; ?></code></td>
                        <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><span class="badge bg-primary"><?php echo strtoupper($user['role']); ?></span></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?impersonate_user_id=<?php echo $user['id']; ?>" class="btn btn-sm btn-success py-0 fw-bold">Enter Dashboard View</a>
                            <?php else: ?>
                                <span class="text-muted small">Active Me</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
