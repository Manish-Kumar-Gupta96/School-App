<?php
session_start();
require_once('../config/database.php');

$message = '';

if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT *, CONCAT(first_name, ' ', last_name) AS student_name 
        FROM students
        WHERE email=? OR roll_no=?
    ");
    $stmt->execute([$username, $username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if($student && password_verify($password, $student['password'])){
        $_SESSION['student_id'] = $student['id'];
        $_SESSION['student_name'] = $student['student_name'];
        $_SESSION['role'] = 'student';

        // Also fetch from users table for full session integration
        $stmt_u = $pdo->prepare("SELECT id, role_id FROM users WHERE email = ?");
        $stmt_u->execute([$student['email']]);
        $user_rec = $stmt_u->fetch(PDO::FETCH_ASSOC);
        if ($user_rec) {
            $_SESSION['user_id'] = $user_rec['id'];
            $_SESSION['role_id'] = $user_rec['role_id'];
        }

        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Invalid Email / Roll No or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login | VIC ERP</title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="../assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="../assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6fb;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            max-width: 950px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            overflow: hidden;
            display: flex;
            min-height: 550px;
            border: 1px solid #e9ecef;
        }
        .login-banner {
            flex: 1.2;
            background: linear-gradient(135deg, #0d6efd, #0F4C81);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            text-align: start;
        }
        .login-banner h2 {
            font-weight: 800;
            font-size: 2.2rem;
            line-height: 1.2;
        }
        .login-form-container {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.95rem;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }
        .btn-primary {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            background-color: #0d6efd;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
        }
        @media (max-width: 768px) {
            .login-card {
                flex-direction: column;
                margin: 20px;
                min-height: auto;
            }
            .login-banner {
                padding: 40px;
                text-align: center;
            }
            .login-banner h2 {
                font-size: 1.8rem;
            }
            .login-form-container {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>

<div class="login-card">
    <!-- Left Banner -->
    <div class="login-banner">
        <div class="mb-4">
            <i class="fa fa-graduation-cap fa-3x"></i>
        </div>
        <h2>Welcome to Student Portal</h2>
        <p class="text-white-50 mt-3 mb-0">Access your attendance records, reports, and announcements with a single portal dashboard.</p>
    </div>

    <!-- Right Form -->
    <div class="login-form-container text-start">
        <h3 class="fw-bold text-dark mb-2">Student Log In</h3>
        <p class="text-muted mb-4">Enter email or roll number to authenticate</p>

        <?php if($message): ?>
            <div class="alert alert-danger alert-dismissible fade show small border-0 py-3" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">Email or Roll Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-user"></i></span>
                    <input type="text" name="username" class="form-control border-start-0" placeholder="e.g. 10 or std@example.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-key"></i></span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-primary w-100 shadow-sm">
                Sign In <i class="fa fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="../assets/js/libs/bootstrap.bundle.min.js"></script>
</body>
</html>
