<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}

// Analytics
$total_schools = $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn();
$active_schools = $pdo->query("SELECT COUNT(*) FROM schools WHERE status='active'")->fetchColumn();

// Assuming invoices has status 'paid' for revenue. We'll sum all for now.
$total_revenue = $pdo->query("SELECT SUM(amount) FROM invoices WHERE status='paid'")->fetchColumn() ?? 0;

$recent_schools = $pdo->query("
    SELECT s.*, p.plan_name 
    FROM schools s 
    LEFT JOIN plans p ON s.plan_id = p.id 
    ORDER BY s.created_at DESC 
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaaS Dashboard | SchoolOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #1e3c72; min-height: 100vh; color: #fff; padding-top: 20px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 10px 20px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; border-left: 4px solid #0d6efd; }
        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-none d-md-block">
            <h4 class="text-center fw-bold mb-4">SchoolOS<br><small class="fw-normal fs-6 text-white-50">SaaS Platform</small></h4>
            <a href="dashboard.php" class="active"><i class="fa fa-chart-line me-2"></i> Dashboard</a>
            <a href="schools.php"><i class="fa fa-school me-2"></i> Manage Schools</a>
            <a href="#"><i class="fa fa-file-invoice-dollar me-2"></i> Billing & Plans</a>
            <a href="#"><i class="fa fa-ticket-alt me-2"></i> Support Tickets</a>
            <a href="#"><i class="fa fa-cogs me-2"></i> System Settings</a>
            <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <!-- Topbar -->
            <div class="bg-white p-3 shadow-sm d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">Platform Overview</h5>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['superadmin_name']) ?></span>
                    <img src="https://ui-avatars.com/api/?name=Admin&background=1e3c72&color=fff" class="rounded-circle" width="40">
                </div>
            </div>

            <!-- Content -->
            <div class="p-4">
                
                <!-- Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="fa fa-school"></i></div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase">Total Tenants</div>
                                    <h3 class="mb-0 fw-bold"><?= number_format($total_schools) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="fa fa-check-circle"></i></div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase">Active Schools</div>
                                    <h3 class="mb-0 fw-bold"><?= number_format($active_schools) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="fa fa-users"></i></div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase">Total Students</div>
                                    <h3 class="mb-0 fw-bold">---</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa fa-rupee-sign"></i></div>
                                <div>
                                    <div class="text-muted small fw-bold text-uppercase">Total Revenue</div>
                                    <h3 class="mb-0 fw-bold">₹<?= number_format($total_revenue) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Schools -->
                <div class="card stat-card">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-0">
                        <h6 class="mb-0 fw-bold text-dark">Recently Onboarded Schools</h6>
                        <a href="schools.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">School Name</th>
                                        <th>Code / Subdomain</th>
                                        <th>Current Plan</th>
                                        <th>Status</th>
                                        <th>Joined Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_schools as $s): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($s['school_name']) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($s['school_code']) ?></span><br>
                                            <small class="text-muted"><?= htmlspecialchars($s['subdomain']) ?>.schoolos.com</small>
                                        </td>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($s['plan_name'] ?? 'Custom') ?></span></td>
                                        <td>
                                            <?php if($s['status'] == 'active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= ucfirst($s['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>
