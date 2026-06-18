<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$date  = $_GET['date'] ?? date('Y-m-d');
$class = $_GET['class'] ?? '';

$sql = "
    SELECT 
        a.*,
        s.admission_no,
        s.first_name,
        s.last_name
    FROM attendance a
    INNER JOIN students s ON a.student_id = s.id
    WHERE a.attendance_date = ?
";
$params = [$date];

if(!empty($class)){
    $sql .= " AND a.class = ? ";
    $params[] = $class;
}

$sql .= " ORDER BY s.first_name ASC ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch distinct classes from database
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Layout setup
$root_path = "../../";
$page_title = "Attendance Records | VIC ERP";
$page_header = "Daily Attendance Records";
$active_menu = "attendance";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="text-muted mb-0">Browse and Filter Attendance logs</h5>
    <a href="take-attendance.php" class="btn btn-primary">
        <i class="fa fa-plus me-1"></i> Take Attendance
    </a>
</div>

<!-- FILTER CARD -->
<div class="card shadow border-0 mb-4" style="border-radius: 12px;">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Date</label>
                <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control">
            </div>
            
            <div class="col-md-4">
                <label class="form-label fw-semibold">Class</label>
                <select name="class" class="form-select">
                    <option value="">All Classes</option>
                    <?php if (count($classes) > 0): ?>
                        <?php foreach($classes as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= ($class == $c) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback list -->
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="Class <?= $i ?>" <?= ($class == "Class $i") ? 'selected' : '' ?>>
                                Class <?= $i ?>
                            </option>
                        <?php endfor; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-grid">
                <button class="btn btn-primary">
                    <i class="fa fa-filter me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Class</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($records) > 0): ?>
                        <?php foreach($records as $row): ?>
                            <tr>
                                <td class="ps-4"><?= htmlspecialchars($row['id']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($row['admission_no']) ?></span></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['class']) ?></td>
                                <td>
                                    <?php
                                    if($row['status'] == 'Present'){
                                        echo "<span class='badge bg-success-subtle text-success border border-success-subtle px-3 py-2'>Present</span>";
                                    } elseif($row['status'] == 'Absent'){
                                        echo "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2'>Absent</span>";
                                    } elseif($row['status'] == 'Late'){
                                        echo "<span class='badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2'>Late</span>";
                                    } else {
                                        echo "<span class='badge bg-info-subtle text-info border border-info-subtle px-3 py-2'>Half Day</span>";
                                    }
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($row['remarks'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa fa-calendar-times fs-2 mb-2 d-block"></i>
                                No attendance records found for this date/class.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
