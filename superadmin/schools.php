<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: login.php");
    exit;
}

// Add New School
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_school') {
    $school_name = trim($_POST['school_name']);
    $school_code = trim($_POST['school_code']);
    $subdomain = trim($_POST['subdomain']);
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    $plan_id = (int)$_POST['plan_id'];

    if (!empty($school_name) && !empty($school_code) && !empty($subdomain)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO schools (school_name, school_code, subdomain, email, mobile, plan_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_name, $school_code, $subdomain, $email, $mobile, $plan_id]);
            
            // Auto generate the first admin account for this tenant
            $new_school_id = $pdo->lastInsertId();
            $admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (school_id, name, email, password, role) VALUES (?, ?, ?, ?, 'Super Admin')");
            $stmt->execute([$new_school_id, 'School Admin', $email, $admin_pass]);
            
            $_SESSION['success'] = "Tenant onboarded successfully. Admin account created.";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error onboarding tenant. Subdomain or Code might already exist.";
        }
    } else {
        $_SESSION['error'] = "Required fields are missing.";
    }
    header("Location: schools.php");
    exit;
}

$schools = $pdo->query("
    SELECT s.*, p.plan_name 
    FROM schools s 
    LEFT JOIN plans p ON s.plan_id = p.id 
    ORDER BY s.created_at DESC 
")->fetchAll(PDO::FETCH_ASSOC);

$plans = $pdo->query("SELECT * FROM plans ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tenants | SchoolOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: #1e3c72; min-height: 100vh; color: #fff; padding-top: 20px; }
        .sidebar a { color: rgba(255,255,255,0.8); text-decoration: none; display: block; padding: 10px 20px; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; border-left: 4px solid #0d6efd; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-none d-md-block">
            <h4 class="text-center fw-bold mb-4">SchoolOS<br><small class="fw-normal fs-6 text-white-50">SaaS Platform</small></h4>
            <a href="dashboard.php"><i class="fa fa-chart-line me-2"></i> Dashboard</a>
            <a href="schools.php" class="active"><i class="fa fa-school me-2"></i> Manage Schools</a>
            <a href="#"><i class="fa fa-file-invoice-dollar me-2"></i> Billing & Plans</a>
            <a href="#"><i class="fa fa-ticket-alt me-2"></i> Support Tickets</a>
            <a href="#"><i class="fa fa-cogs me-2"></i> System Settings</a>
            <a href="logout.php" class="text-danger mt-5"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <!-- Topbar -->
            <div class="bg-white p-3 shadow-sm d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0 fw-bold text-dark">Tenant Directory</h5>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addSchoolModal"><i class="fa fa-plus me-2"></i>Onboard New School</button>
            </div>

            <div class="p-4 pt-0">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>School Identity</th>
                                        <th>Contact Info</th>
                                        <th>Subscription</th>
                                        <th>Status</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($schools as $s): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?= $s['id'] ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($s['school_name']) ?></div>
                                            <div class="text-muted small">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($s['school_code']) ?></span> 
                                                <?= htmlspecialchars($s['subdomain']) ?>.schoolos.com
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fa fa-envelope text-muted me-1"></i><?= htmlspecialchars($s['email']) ?></div>
                                            <div class="small"><i class="fa fa-phone text-muted me-1"></i><?= htmlspecialchars($s['mobile']) ?></div>
                                        </td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><?= htmlspecialchars($s['plan_name'] ?? 'N/A') ?></span></td>
                                        <td>
                                            <?php if($s['status'] == 'active'): ?>
                                                <span class="badge bg-success"><i class="fa fa-check-circle me-1"></i>Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger"><?= ucfirst($s['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fa fa-edit"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa fa-ban"></i></button>
                                        </td>
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

<!-- Add School Modal -->
<div class="modal fade" id="addSchoolModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold text-primary"><i class="fa fa-school me-2"></i>Onboard New Tenant</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
      <div class="modal-body">
            <input type="hidden" name="action" value="add_school">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold">School Name <span class="text-danger">*</span></label>
                    <input type="text" name="school_name" class="form-control" required placeholder="E.g. Modern Academy">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">School Code <span class="text-danger">*</span></label>
                    <input type="text" name="school_code" class="form-control" required placeholder="E.g. MA001">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Subdomain <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="subdomain" class="form-control" required placeholder="modern">
                        <span class="input-group-text">.schoolos.com</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Admin Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@modern.edu">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Admin Phone</label>
                    <input type="text" name="mobile" class="form-control" placeholder="+91 9876543210">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">Select Subscription Plan <span class="text-danger">*</span></label>
                    <select name="plan_id" class="form-select" required>
                        <?php foreach($plans as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['plan_name']) ?> (₹<?= $p['monthly_price'] ?>/mo)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa fa-check me-2"></i>Create Tenant</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
