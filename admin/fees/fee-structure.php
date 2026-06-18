<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';

if(isset($_POST['save'])){
    $class_name     = trim($_POST['class_name']);
    $admission_fee  = $_POST['admission_fee'] ?: 0.00;
    $monthly_fee    = $_POST['monthly_fee'] ?: 0.00;
    $annual_fee     = $_POST['annual_fee'] ?: 0.00;
    $exam_fee       = $_POST['exam_fee'] ?: 0.00;
    $transport_fee  = $_POST['transport_fee'] ?: 0.00;

    // Check if structure for this class already exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM fee_structure WHERE class_name = ?");
    $check->execute([$class_name]);
    
    if ($check->fetchColumn() > 0) {
        $stmt = $pdo->prepare("
            UPDATE fee_structure SET
                admission_fee = ?,
                monthly_fee = ?,
                annual_fee = ?,
                exam_fee = ?,
                transport_fee = ?
            WHERE class_name = ?
        ");
        $stmt->execute([$admission_fee, $monthly_fee, $annual_fee, $exam_fee, $transport_fee, $class_name]);
        $message = "Fee Structure Updated Successfully";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO fee_structure(
                class_name,
                admission_fee,
                monthly_fee,
                annual_fee,
                exam_fee,
                transport_fee
            )
            VALUES(?,?,?,?,?,?)
        ");
        $stmt->execute([$class_name, $admission_fee, $monthly_fee, $annual_fee, $exam_fee, $transport_fee]);
        $message = "Fee Structure Saved Successfully";
    }
}

$fees = $pdo->query("
    SELECT *
    FROM fee_structure
    ORDER BY class_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch classes from student records for select autofill helper
$classes_query = $pdo->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
$classes = $classes_query->fetchAll(PDO::FETCH_COLUMN);

// Layout setup
$root_path = "../../";
$page_title = "Fee Structure | VIC ERP";
$page_header = "Fee Structure Management";
$active_menu = "fees";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<?php if($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- ADD / UPDATE FEE STRUCTURE -->
    <div class="col-xl-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-primary text-white py-3" style="border-radius: 12px 12px 0 0;">
                <h6 class="fw-bold mb-0"><i class="fa fa-sliders-h me-2"></i> Set Class Fees</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Class Name *</label>
                        <select name="class_name" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php if (count($classes) > 0): ?>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for($i=1; $i<=12; $i++): ?>
                                    <option value="Class <?= $i ?>">Class <?= $i ?></option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Admission Fee (₹)</label>
                        <input type="number" step="0.01" name="admission_fee" class="form-control" placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Monthly Tuition Fee (₹)</label>
                        <input type="number" step="0.01" name="monthly_fee" class="form-control" placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Annual charges (₹)</label>
                        <input type="number" step="0.01" name="annual_fee" class="form-control" placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Examination Fee (₹)</label>
                        <input type="number" step="0.01" name="exam_fee" class="form-control" placeholder="0.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Transport Fee (₹)</label>
                        <input type="number" step="0.01" name="transport_fee" class="form-control" placeholder="0.00">
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="save" class="btn btn-success">
                            <i class="fa fa-save me-1"></i> Save Fee Structure
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- LIST OF CURRENT STRUCTURES -->
    <div class="col-xl-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Current Fee Structures</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr class="text-start">
                                <th class="ps-4">Class</th>
                                <th>Admission</th>
                                <th>Monthly</th>
                                <th>Annual</th>
                                <th>Exam</th>
                                <th>Transport</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($fees) > 0): ?>
                                <?php foreach($fees as $fee): ?>
                                    <tr class="text-start">
                                        <td class="ps-4 fw-semibold"><?= htmlspecialchars($fee['class_name']) ?></td>
                                        <td>₹ <?= number_format($fee['admission_fee'], 2) ?></td>
                                        <td>₹ <?= number_format($fee['monthly_fee'], 2) ?></td>
                                        <td>₹ <?= number_format($fee['annual_fee'], 2) ?></td>
                                        <td>₹ <?= number_format($fee['exam_fee'], 2) ?></td>
                                        <td>₹ <?= number_format($fee['transport_fee'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fa fa-coins fs-2 mb-2 d-block"></i>
                                        No fee structures defined. Define one on the left.
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
require_once('../../admin/includes/footer.php');
?>
