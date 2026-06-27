<?php
session_start();

// Disable caching to prevent ERR_CACHE_MISS on browser back/refresh
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once('config/database.php');
require_once('includes/AuthClass.php');

// CSRF token generation
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Hacking check: Agar user pehle se logged in hai toh dashboard bhej dein
if (isset($_SESSION['user_id']) && !isset($_SESSION['awaiting_activation'])) {
    if (isset($_SESSION['role_id'])) {
        if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2) header("Location: admin/dashboard.php");
        else if ($_SESSION['role_id'] == 4) header("Location: teacher/dashboard.php");
        else if ($_SESSION['role_id'] == 7) header("Location: parent/dashboard.php");
        else if ($_SESSION['role_id'] == 8) header("Location: student/dashboard.php");
        else header("Location: index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

// Magic Link Listener Hook
if (isset($_GET['magic_user']) && !isset($_SESSION['awaiting_activation'])) {
    $magicUser = filter_input(INPUT_GET, 'magic_user', FILTER_SANITIZE_EMAIL);
    
    // Check if the user exists and is still un-activated (is_activated = 0)
    $chkMagic = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $chkMagic->execute([':email' => $magicUser]);
    $magicUserData = $chkMagic->fetch(PDO::FETCH_ASSOC);
    
    if ($magicUserData && $magicUserData['is_activated'] == 0) {
        $_SESSION['awaiting_activation'] = true;
        $_SESSION['temp_user_id'] = $magicUserData['id'];
        $_SESSION['temp_email'] = $magicUserData['email'];
        $success = "Welcome! Direct URL verify ho gayi hai. Kripya apna permanent password set karein.";
    }
}

// ----------------------------------------------------
// PROCESS 2: FIRST-TIME SIGN-UP & AUTO-LOGIN ACTION
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_activation'])) {
    if (!isset($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $error = "CSRF Token validation failed.";
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userId = $_SESSION['temp_user_id'] ?? null;

        try {
            if (!$userId) throw new Exception("Session validation check expired. Kripya login dobara try karein.");
            if (strlen($newPassword) < 6) throw new Exception("Password kam se kam 6 characters ka hona zaroori hai.");
            if ($newPassword !== $confirmPassword) throw new Exception("Dono passwords match nahi kar rahe hain.");

            $finalHashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $pdo->beginTransaction();

            $updateStmt = $pdo->prepare("UPDATE users SET password = :pass, is_activated = 1 WHERE id = :id");
            $updateStmt->execute([':pass' => $finalHashedPassword, ':id' => $userId]);

            // Auto-Login Injection Pipeline
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $role_name = '';
                $role_specific_id = null;
                $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

                if ($user['role_id'] == 1 || $user['role_id'] == 2) {
                    $role_name = 'admin';
                    $stmt_adm = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                    $stmt_adm->execute([$user['email']]);
                    $role_specific_id = $stmt_adm->fetchColumn() ?: null;
                } else if ($user['role_id'] == 4) {
                    $role_name = 'teacher';
                    $stmt_tch = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
                    $stmt_tch->execute([$user['email']]);
                    $role_specific_id = $stmt_tch->fetchColumn() ?: null;
                } else if ($user['role_id'] == 7) {
                    $role_name = 'parent';
                    $stmt_prn = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
                    $stmt_prn->execute([$user['email']]);
                    $role_specific_id = $stmt_prn->fetchColumn() ?: null;
                } else if ($user['role_id'] == 8) {
                    $role_name = 'student';
                    $stmt_std = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                    $stmt_std->execute([$user['email']]);
                    $role_specific_id = $stmt_std->fetchColumn() ?: null;
                }

                Auth::login($user, $role_name, $role_specific_id);

                // Insert login_logs
                $stmt_log = $pdo->prepare("INSERT INTO login_logs (user_id, login_time, ip_address, device_info) VALUES (?, NOW(), ?, ?)");
                $stmt_log->execute([$user['id'], $client_ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
                $_SESSION['login_log_id'] = $pdo->lastInsertId();

                // Log audit
                $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                $stmt_audit->execute([$user['id'], "Account Activated & Auto-Login Successful", $client_ip]);

                unset($_SESSION['awaiting_activation']);
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_email']);
                
                $pdo->commit();

                // Redirection based on role
                if ($user['role_id'] == 1 || $user['role_id'] == 2) header("Location: admin/dashboard.php");
                else if ($user['role_id'] == 4) header("Location: teacher/dashboard.php");
                else if ($user['role_id'] == 7) header("Location: parent/dashboard.php");
                else if ($user['role_id'] == 8) header("Location: student/dashboard.php");
                else header("Location: index.php");
                exit;
            } else {
                throw new Exception("Error loading user profile post-activation.");
            }
        } catch (Exception $e) { 
            $pdo->rollBack();
            $error = $e->getMessage(); 
        }
    }
}

// ----------------------------------------------------
// PROCESS 1: STANDARD INITIAL LOGIN ACTION INGESTION
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attempt_login'])) {
    // Validate CSRF
    if (!isset($_POST['csrf']) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $error = "CSRF Token validation failed. Please refresh the page and try again.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Please enter both email and password.";
        } else {
            try {
                $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $lockout_time = 15; // minutes
                $max_attempts = 5;

                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL ? MINUTE)");
                $stmt_check->execute([$client_ip, $lockout_time]);
                $attempts = $stmt_check->fetchColumn();

                if ($attempts >= $max_attempts) {
                    $error = "Too many failed login attempts. Please try again after 15 minutes.";
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'ACTIVE'");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password'])) {
                        
                        // Check if user is activated
                        if (isset($user['is_activated']) && $user['is_activated'] == 0) {
                            $_SESSION['awaiting_activation'] = true;
                            $_SESSION['temp_user_id'] = $user['id'];
                            $_SESSION['temp_email'] = $user['email'];
                            $success = "Welcome! Yeh aapka pehla login hai. Kripya apna permanent password set karke account active karein.";
                        } else {
                            $role_name = '';
                            $role_specific_id = null;

                            if ($user['role_id'] == 1 || $user['role_id'] == 2) {
                                $role_name = 'admin';
                                $stmt_adm = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                                $stmt_adm->execute([$user['email']]);
                                $role_specific_id = $stmt_adm->fetchColumn() ?: null;
                            } else if ($user['role_id'] == 4) {
                                $role_name = 'teacher';
                                $stmt_tch = $pdo->prepare("SELECT id FROM teachers WHERE email = ?");
                                $stmt_tch->execute([$user['email']]);
                                $role_specific_id = $stmt_tch->fetchColumn() ?: null;
                            } else if ($user['role_id'] == 7) {
                                $role_name = 'parent';
                                $stmt_prn = $pdo->prepare("SELECT id FROM parents WHERE email = ?");
                                $stmt_prn->execute([$user['email']]);
                                $role_specific_id = $stmt_prn->fetchColumn() ?: null;
                            } else if ($user['role_id'] == 8) {
                                $role_name = 'student';
                                $stmt_std = $pdo->prepare("SELECT id FROM students WHERE email = ?");
                                $stmt_std->execute([$user['email']]);
                                $role_specific_id = $stmt_std->fetchColumn() ?: null;
                            }

                            Auth::login($user, $role_name, $role_specific_id);

                            // Clear login attempts on success
                            $stmt_clear = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                            $stmt_clear->execute([$client_ip]);

                            // Insert login_logs
                            $stmt_log = $pdo->prepare("INSERT INTO login_logs (user_id, login_time, ip_address, device_info) VALUES (?, NOW(), ?, ?)");
                            $stmt_log->execute([$user['id'], $client_ip, $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);
                            $_SESSION['login_log_id'] = $pdo->lastInsertId();

                            // Log audit
                            $stmt_audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                            $stmt_audit->execute([$user['id'], "Login Successful", $client_ip]);

                            if (!empty($user['force_password_change'])) {
                                header("Location: auth/change-password.php");
                                exit;
                            }

                            // Redirection based on role
                            if ($user['role_id'] == 1 || $user['role_id'] == 2) {
                                header("Location: admin/dashboard.php");
                            } else if ($user['role_id'] == 4) {
                                header("Location: teacher/dashboard.php");
                            } else if ($user['role_id'] == 7) {
                                header("Location: parent/dashboard.php");
                            } else if ($user['role_id'] == 8) {
                                header("Location: student/dashboard.php");
                            } else {
                                header("Location: index.php");
                            }
                            exit;
                        }
                    } else {
                        // Log failed attempt
                        $stmt_fail = $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?, ?)");
                        $stmt_fail->execute([$client_ip, $email]);
                        
                        $remaining = $max_attempts - $attempts - 1;
                        if ($remaining <= 0) {
                            $error = "Too many failed login attempts. Please try again after 15 minutes.";
                        } else {
                            $error = "Invalid active email or password. $remaining attempts remaining.";
                        }
                    }
                } // End rate limit check else
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Login | VIC School</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/default-user.png">
    <!-- Bootstrap (Local) -->
    <link href="assets/css/libs/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="assets/css/libs/fa/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        .min-vh-100 {
            min-height: 100vh !important;
        }
        .max-width-500 {
            max-width: 500px;
        }
        .btn-primary {
            background-color: var(--primary, #0f4c81);
            border-color: var(--primary, #0f4c81);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--secondary, #1d4ed8);
            border-color: var(--secondary, #1d4ed8);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <section class="login-section min-vh-100 w-100">
        <div class="container-fluid p-0 h-100">
            <div class="row g-0 min-vh-100">
                <!-- Left Pane -->
                <div class="col-lg-6 d-none d-lg-block login-left">
                    <div class="login-overlay">
                        <div class="mb-4">
                            <i class="fa fa-graduation-cap fa-4x text-warning"></i>
                        </div>
                        <h1>VIC School</h1>
                        <p class="lead text-white-50 mt-3 max-width-500">Inspiring Excellence, Building Future Leaders. Access all school portals under one unified enterprise gateway.</p>
                    </div>
                </div>
                
                <!-- Right Pane -->
                <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
                    <div class="login-box my-5 w-100" style="max-width: 450px;">
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger py-2 small" role="alert">
                                <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success py-2 small" role="alert">
                                <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['awaiting_activation']) && $_SESSION['awaiting_activation'] === true): ?>
                        
                        <!-- First Time Activation Form -->
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-success">Account Activation</h3>
                            <p class="text-muted small">Please set your permanent password to continue.</p>
                        </div>

                        <form method="POST" action="" autocomplete="off">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">New Permanent Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-key"></i></span>
                                    <input type="password" name="new_password" class="form-control border-start-0" minlength="6" placeholder="Choose high security string" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-key"></i></span>
                                    <input type="password" name="confirm_password" class="form-control border-start-0" minlength="6" placeholder="Retype password string" required>
                                </div>
                            </div>

                            <div class="form-check mb-4 text-start">
                                <input class="form-check-input" type="checkbox" id="terms" required checked>
                                <label class="form-check-label small text-muted" for="terms">
                                    Main school data privacy aur system security policies ko accept karta hu.
                                </label>
                            </div>

                            <button type="submit" name="execute_activation" class="btn btn-success w-100 py-2.5 fw-semibold shadow-sm">
                                <i class="fa fa-check-circle me-2"></i> Activate & Login
                            </button>
                        </form>

                        <?php else: ?>

                        <!-- Standard Login Form -->
                        <div class="text-center mb-4">
                            <h2 class="fw-bold">Welcome Back</h2>
                            <p class="text-muted small">Please sign in to access your portal</p>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control border-start-0" placeholder="name@vicschool.edu.in" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold text-muted">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa fa-key"></i></span>
                                    <input type="password" name="password" id="password" class="form-control border-start-0" placeholder="••••••••" required>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 bg-white text-muted" onclick="togglePassword()">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" id="remember" class="form-check-input">
                                    <label for="remember" class="form-check-label text-muted small">Remember Me</label>
                                </div>
                                <a href="auth/forgot-password.php" class="text-decoration-none small">Forgot Password?</a>
                            </div>

                            <button type="submit" name="attempt_login" class="btn btn-primary w-100 py-2.5 fw-semibold shadow-sm">
                                <i class="fa fa-right-to-bracket me-2"></i> Sign In
                            </button>
                            
                            <div class="text-center mt-4">
                                <a href="index.php" class="text-muted small text-decoration-none">
                                    <i class="fa fa-arrow-left me-1"></i> Back to Homepage
                                </a>
                            </div>
                        </form>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Script to toggle password visibility -->
    <script>
    function togglePassword() {
        const pass = document.getElementById('password');
        const btnIcon = document.querySelector('.btn-outline-secondary i');
        if (pass && btnIcon) {
            if (pass.type === "password") {
                pass.type = "text";
                btnIcon.className = "fa fa-eye-slash";
            } else {
                pass.type = "password";
                btnIcon.className = "fa fa-eye";
            }
        }
    }
    </script>
</body>
</html>
