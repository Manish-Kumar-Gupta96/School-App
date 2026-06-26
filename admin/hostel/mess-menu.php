<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Add Mess Menu Item
if (isset($_POST['save_menu'])) {
    $menu_date    = $_POST['menu_date'];
    $meal_type    = trim($_POST['meal_type']);
    $menu_details = trim($_POST['menu_details']);

    if (empty($menu_date) || empty($meal_type) || empty($menu_details)) {
        $error = "Date, Meal Type, and Menu Details are required.";
    } else {
        // Check if meal type already exists for this date
        $check = $pdo->prepare("SELECT id FROM mess_menu WHERE school_id = ? AND menu_date = ? AND meal_type = ?");
        $check->execute([CURRENT_SCHOOL_ID, $menu_date, $meal_type]);

        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                UPDATE mess_menu 
                SET menu_details = ? 
                WHERE school_id = ? AND menu_date = ? AND meal_type = ?
            ");
            $stmt->execute([$menu_details, CURRENT_SCHOOL_ID, $menu_date, $meal_type]);
            $message = "Menu item updated successfully!";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO mess_menu (school_id, menu_date, meal_type, menu_details)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([CURRENT_SCHOOL_ID, $menu_date, $meal_type, $menu_details]);
            $message = "Menu item added successfully!";
        }
        $date = $menu_date;
    }
}

// Fetch menus for selected date
$stmt_menus = $pdo->prepare("
    SELECT * FROM mess_menu 
    WHERE school_id = ? AND menu_date = ?
    ORDER BY FIELD(LOWER(meal_type), 'breakfast', 'lunch', 'snacks', 'dinner', 'other')
");
$stmt_menus->execute([CURRENT_SCHOOL_ID, $date]);
$menus = $stmt_menus->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent menu logs (last 7 days)
$stmt_rec = $pdo->prepare("
    SELECT DISTINCT menu_date 
    FROM mess_menu 
    WHERE school_id = ?
    ORDER BY menu_date DESC 
    LIMIT 7
");
$stmt_rec->execute([CURRENT_SCHOOL_ID]);
$recentDates = $stmt_rec->fetchAll(PDO::FETCH_COLUMN);

// Layout variables
$root_path = "../../";
$page_title = "Mess Menu Management | VIC ERP";
$page_header = "Hostel Management";
$active_menu = "hostel";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Publish and schedule weekly dining menus</h5>
    <div class="d-flex gap-2">
        <a href="hostels.php" class="btn btn-outline-primary">
            <i class="fa fa-hotel me-1"></i> Hostels
        </a>
        <a href="rooms.php" class="btn btn-outline-primary">
            <i class="fa fa-door-open me-1"></i> Rooms
        </a>
        <a href="beds.php" class="btn btn-outline-primary">
            <i class="fa fa-bed me-1"></i> Beds
        </a>
        <a href="attendance.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-check me-1"></i> Attendance
        </a>
        <a href="visitors.php" class="btn btn-outline-danger">
            <i class="fa fa-users me-1"></i> Visitors
        </a>
        <a href="mess-menu.php" class="btn btn-primary">
            <i class="fa fa-utensils me-1"></i> Mess Menu
        </a>
        <a href="fees.php" class="btn btn-outline-warning">
            <i class="fa fa-file-invoice-dollar me-1"></i> Fees
        </a>
        <a href="reports.php" class="btn btn-outline-secondary">
            <i class="fa fa-chart-bar me-1"></i> Reports
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Publish Menu Form -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Publish Mess Menu</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Menu Date <span class="text-danger">*</span></label>
                        <input type="date" name="menu_date" class="form-control" value="<?= htmlspecialchars($date) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meal Type <span class="text-danger">*</span></label>
                        <select name="meal_type" class="form-select" required>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Snacks">Snacks</option>
                            <option value="Dinner">Dinner</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Menu Details <span class="text-danger">*</span></label>
                        <textarea name="menu_details" rows="5" class="form-control" placeholder="e.g. Aloo Paratha, Curd, Butter, Tea" required></textarea>
                    </div>

                    <button type="submit" name="save_menu" class="btn btn-success w-100">
                        <i class="fa fa-paper-plane me-1"></i> Publish Menu
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Menu Preview & History -->
    <div class="col-lg-8">
        <!-- Date Selector Card -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0 text-muted">Preview Date Menu</h6>
                <form method="GET" class="d-flex align-items-center gap-2">
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        <!-- Menu Board -->
        <div class="card shadow border-0 mb-4" style="border-radius: 15px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Dining Board: <?= date('d M Y', strtotime($date)) ?></h5>
            </div>
            <div class="card-body px-4 pb-4">
                <?php if (count($menus) > 0): ?>
                    <div class="row g-3">
                        <?php foreach($menus as $m): ?>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="fa fa-utensils me-1"></i> <?= htmlspecialchars($m['meal_type']) ?>
                                        </h6>
                                        <span class="badge bg-secondary-subtle text-secondary small">Published</span>
                                    </div>
                                    <p class="text-dark mb-0 whitespace-pre-line" style="font-size: 0.95rem;">
                                        <?= htmlspecialchars($m['menu_details']) ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-bowl-food fs-2 mb-2 d-block"></i>
                        No menu items published for this date.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Logs -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h6 class="fw-bold mb-0 text-dark">Recent Menu Logs</h6>
            </div>
            <div class="card-body py-2 px-4">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <?php if (count($recentDates) > 0): ?>
                        <?php foreach($recentDates as $d): ?>
                            <a href="?date=<?= $d ?>" class="btn btn-sm btn-outline-secondary <?= $d === $date ? 'active' : '' ?>">
                                <?= date('d M Y', strtotime($d)) ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted small">No historical records found.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
