<?php
session_start();
require_once('../config/database.php');

$error = '';
$success = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Validate recovery session token
if (empty($token) || !isset($_SESSION['recovery_token']) || !hash_equals($_SESSION['recovery_token'], $token)) {
    die("Invalid or expired password reset token.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $email = $_SESSION['recovery_email'];

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->execute([$hashed_pass, $email]);

            // Clean recovery session
            unset($_SESSION['recovery_token']);
            unset($_SESSION['recovery_email']);

            $success = "Password updated successfully. You can now <a href='login.php' class='alert-link'>login here</a>.";
        } catch (PDOException $e) {
            $error = "Error updating password: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | VIC School</title>
    <link href="../assets/css/libs/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/libs/fa/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            color: #fff;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .form-control {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 8px;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.12);
            border-color: #3b82f6;
            color: #fff;
            box-shadow: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-card text-center">
        <h3 class="fw-bold mb-1">Set New Password</h3>
        <p class="text-white-50 small mb-4">Enter and confirm your new secure credentials</p>

        <?php if ($error): ?>
            <div class="alert alert-danger text-start py-2 small" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success text-start py-2 small" role="alert">
                <i class="fa fa-check-circle me-2"></i> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" action="" class="text-start">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-semibold">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Update Password
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
