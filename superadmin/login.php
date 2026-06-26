<?php
session_start();
require_once('../config/database.php');

if (isset($_SESSION['superadmin_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM superadmins WHERE email = ?");
    $stmt->execute([$email]);
    $superadmin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($superadmin && password_verify($password, $superadmin['password'])) {
        $_SESSION['superadmin_id'] = $superadmin['id'];
        $_SESSION['superadmin_name'] = $superadmin['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | SchoolOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { background: rgba(255,255,255,0.95); border-radius: 15px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .brand { font-weight: 800; font-size: 2rem; color: #1e3c72; text-align: center; margin-bottom: 30px; letter-spacing: -1px; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand">SchoolOS<br><span style="font-size:1rem; font-weight:400; color:#6c757d;">Platform Management</span></div>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold">Email Address</label>
            <input type="email" name="email" class="form-control form-control-lg" required placeholder="superadmin@schoolos.com">
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Password</label>
            <input type="password" name="password" class="form-control form-control-lg" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg fw-bold" style="background: #1e3c72; border:none;">Access Platform</button>
    </form>
    <div class="text-center mt-3 small text-muted">
        Default: superadmin@schoolos.com / password
    </div>
</div>

</body>
</html>
