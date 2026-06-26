<?php
session_start();
require_once('../config/database.php');
require_once('../includes/AuthClass.php');

if (!Auth::check()) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user actually needs to change password
$stmt = $pdo->prepare("SELECT force_password_change FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$force_change = $stmt->fetchColumn();

if (!$force_change && !isset($_GET['force'])) {
    // Redirect to dashboard if they don't need to change it
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill out all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, force_password_change = 0 WHERE id = ?");
            $stmt->execute([$hash, $user_id]);
            $success = "Password changed successfully. You can now access your dashboard.";
            // Remove force flag from session if it exists
            unset($_SESSION['force_password_change']);
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | VIC School</title>
    <link href="../assets/css/libs/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container mt-5 max-width-500">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="fw-bold text-center mb-4">Change Password</h4>
                <?php if ($force_change): ?>
                    <div class="alert alert-warning small">
                        Your administrator has required that you change your password before continuing.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success small"><?= htmlspecialchars($success) ?></div>
                    <div class="text-center mt-3">
                        <a href="../login.php" class="btn btn-primary">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-muted">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
