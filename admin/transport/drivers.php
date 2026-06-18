<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

if(isset($_POST['save_driver'])){
    $driver_name    = trim($_POST['driver_name']);
    $phone          = trim($_POST['phone']);
    $license_no     = trim($_POST['license_no']);
    $license_expiry = !empty($_POST['license_expiry']) ? $_POST['license_expiry'] : null;
    $address        = trim($_POST['address']);
    $status         = $_POST['status'];

    $photo = '';

    if(!empty($_FILES['photo']['name'])){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array(strtolower($ext), $allowed)) {
            $error = "Invalid photo format. Only JPG and PNG are supported.";
        } else {
            // Ensure uploads/drivers/ exists
            if (!is_dir("../../uploads/drivers/")) {
                mkdir("../../uploads/drivers/", 0777, true);
            }
            $photo = time() . "_" . rand(1000,9999) . "." . $ext;
            if(!move_uploaded_file($_FILES['photo']['tmp_name'], "../../uploads/drivers/" . $photo)) {
                $error = "Failed to upload driver photo.";
            }
        }
    }

    if(empty($driver_name)){
        $error = "Driver Name is required.";
    }

    if(empty($error)){
        $stmt = $pdo->prepare("
            INSERT INTO drivers(driver_name, phone, license_no, license_expiry, address, photo, status)
            VALUES(?,?,?,?,?,?,?)
        ");
        $stmt->execute([$driver_name, $phone, $license_no, $license_expiry, $address, $photo, $status]);
        $message = "Driver Registered Successfully!";
    }
}

$drivers = $pdo->query("
    SELECT *
    FROM drivers
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Layout setup
$root_path = "../../";
$page_title = "Driver Management | VIC ERP";
$page_header = "Transport Management";
$active_menu = "transport";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage school bus drivers and personnel licenses</h5>
    <div class="d-flex gap-2">
        <a href="routes.php" class="btn btn-outline-primary">
            <i class="fa fa-route me-1"></i> Routes
        </a>
        <a href="vehicles.php" class="btn btn-outline-primary">
            <i class="fa fa-bus me-1"></i> Vehicles
        </a>
        <a href="drivers.php" class="btn btn-primary">
            <i class="fa fa-id-card me-1"></i> Drivers
        </a>
        <a href="assign-students.php" class="btn btn-outline-success">
            <i class="fa fa-user-plus me-1"></i> Assign Students
        </a>
        <a href="transport-fees.php" class="btn btn-outline-warning">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- ADD DRIVER -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Add New Driver</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Driver Name <span class="text-danger">*</span></label>
                        <input type="text" name="driver_name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Contact</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. 9876543210">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">License Number</label>
                        <input type="text" name="license_no" class="form-control" placeholder="e.g. DL-14xxxxxxxxxxx">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">License Expiry</label>
                        <input type="date" name="license_expiry" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Driver Photo</label>
                        <input type="file" name="photo" class="form-control">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Residential Address</label>
                        <textarea name="address" rows="3" class="form-control" placeholder="Enter full address..."></textarea>
                    </div>

                    <button type="submit" name="save_driver" class="btn btn-success w-100">
                        <i class="fa fa-save me-1"></i> Save Driver
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST DRIVERS -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Photo</th>
                                <th>Driver Name</th>
                                <th>Phone</th>
                                <th>License Details</th>
                                <th>License Expiry</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($drivers) > 0): ?>
                                <?php foreach($drivers as $driver): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php if($driver['photo'] && file_exists("../../uploads/drivers/" . $driver['photo'])): ?>
                                                <img src="../../uploads/drivers/<?= htmlspecialchars($driver['photo']) ?>" width="50" height="50" style="object-fit:cover; border-radius:50%; border:2px solid var(--primary);">
                                            <?php else: ?>
                                                <img src="../../assets/images/default-user.png" width="50" height="50" style="object-fit:cover; border-radius:50%;">
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark"><?= htmlspecialchars($driver['driver_name']) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($driver['phone'] ?: '-') ?></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($driver['license_no'] ?: 'N/A') ?></span></td>
                                        <td><?= $driver['license_expiry'] ? date('d M Y', strtotime($driver['license_expiry'])) : '-' ?></td>
                                        <td>
                                            <?php if($driver['status'] == 'Active'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-users-slash fs-2 mb-2 d-block"></i>
                                        No drivers registered.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
