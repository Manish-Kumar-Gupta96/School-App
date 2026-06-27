<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/Csrf.php';

// Strict Role Enforcement - Admin and Superadmins only
BaseController::enforceRole(['admin', 'superadmin']);

$username = $_SESSION['username'] ?? 'Admin';
$currentRole = $_SESSION['user_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Portal | School Management System</title>
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/bootstrap.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/libs/fa/all.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo BaseController::asset('css/dashboard.css'); ?>">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; }
        .sidebar { min-height: 100vh; background: #2c3e50; padding-top: 20px; box-shadow: 2px 0 5px rgba(0,0,0,0.1); }
        .sidebar h4 { color: #fff; text-align: center; margin-bottom: 30px; font-weight: bold; letter-spacing: 1px; }
        .topbar { background: #fff; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .stat-card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar px-0">
            <h4>SCHOOL APP</h4>
            <div class="px-3">
                <?php renderSidebar($currentRole); ?>
            </div>
        </div>

        <div class="col-md-9 col-lg-10 px-0">
            <div class="topbar d-flex justify-content-between align-items-center">
                <h5 class="mb-0 text-muted">Dashboard Overview</h5>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i> Welcome, <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><a class="dropdown-menu-item dropdown-item" href="/school-app/admin/profile"><i class="fas fa-id-card me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-menu-item dropdown-item text-danger" href="/school-app/auth/logout"><i class="fas fa-sign-out-alt me-2"></i> Secure Logout</a></li>
                    </ul>
                </div>
            </div>

            <div class="container-fluid p-4">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card stat-card bg-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 font-weight-bold">1,240</h3>
                                    <span class="text-muted small">Total Students</span>
                                </div>
                                <div class="bg-primary text-white p-3 rounded-circle"><i class="fas fa-user-graduate fa-2x"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 font-weight-bold">85</h3>
                                    <span class="text-muted small">Active Teachers</span>
                                </div>
                                <div class="bg-success text-white p-3 rounded-circle"><i class="fas fa-chalkboard-teacher fa-2x"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card stat-card bg-white p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1 font-weight-bold">₹4.2M</h3>
                                    <span class="text-muted small">Fees Collected (Month)</span>
                                </div>
                                <div class="bg-warning text-white p-3 rounded-circle"><i class="fas IndianRupeeSign fa-2x fa-wallet"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card border-0 p-4 shadow-sm" style="border-radius:10px;">
                            <h5 class="card-title text-dark mb-4"><i class="fas fa-bolt text-warning me-2"></i> System Activity & Notifications</h5>
                            <div class="alert alert-info py-3" role="alert">
                                <strong>System Notification:</strong> Automatic backup cron completed successfully at 12:00 AM via <code>cron_runner.bat</code>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BaseController::asset('js/libs/bootstrap.bundle.min.js'); ?>"></script>
</body>
</html>
