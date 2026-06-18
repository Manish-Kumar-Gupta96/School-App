<?php
session_start();
require_once('../config/database.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $error = "Please enter your registered email address.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'ACTIVE'");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generate a simulated recovery token
                $token = bin2hex(random_bytes(16));
                $_SESSION['recovery_email'] = $email;
                $_SESSION['recovery_token'] = $token;

                // Simulate sending email notification
                $success = "A password recovery simulation link has been generated. <a href='reset-password.php?token={$token}' class='alert-link'>Click here to reset your password</a>";
            } else {
                $error = "No active user account matches this email.";
            }
        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | VIC School</title>
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
        <h3 class="fw-bold mb-1">Recover Password</h3>
        <p class="text-white-50 small mb-4">Enter your email to reset your credentials</p>

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

        <form method="POST" action="" class="text-start">
            <div class="mb-4">
                <label class="form-label small fw-semibold">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@vicschool.edu.in" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                Send Recovery Link
            </button>

            <div class="text-center small mt-3">
                <a href="login.php" class="text-white-50 text-decoration-none"><i class="fa fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
