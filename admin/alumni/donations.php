<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Add Donation Log
if (isset($_POST['save_donation'])) {
    $alumni_id     = (int)$_POST['alumni_id'];
    $amount        = (float)$_POST['amount'];
    $donation_date = $_POST['donation_date'];

    if (empty($alumni_id) || $amount <= 0 || empty($donation_date)) {
        $error = "Alumni, Amount, and Date are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO alumni_donations (school_id, alumni_id, amount, donation_date)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $alumni_id,
            $amount,
            $donation_date
        ]);
        $message = "Alumni donation recorded successfully!";
    }
}

// Fetch Donation Metrics
$stmt_sum = $pdo->prepare("SELECT IFNULL(SUM(amount), 0) FROM alumni_donations WHERE school_id = ?");
$stmt_sum->execute([CURRENT_SCHOOL_ID]);
$totalAmount = $stmt_sum->fetchColumn();

$stmt_cnt = $pdo->prepare("SELECT COUNT(DISTINCT alumni_id) FROM alumni_donations WHERE school_id = ?");
$stmt_cnt->execute([CURRENT_SCHOOL_ID]);
$totalDonors = $stmt_cnt->fetchColumn();

$stmt_avg = $pdo->prepare("SELECT IFNULL(AVG(amount), 0) FROM alumni_donations WHERE school_id = ?");
$stmt_avg->execute([CURRENT_SCHOOL_ID]);
$avgDonation = $stmt_avg->fetchColumn();

// Fetch Alumni for dropdown
$stmt_alumni = $pdo->prepare("
    SELECT a.id, s.first_name, s.last_name, a.passout_year 
    FROM alumni a
    JOIN students s ON a.student_id = s.id
    WHERE a.school_id = ?
    ORDER BY s.first_name ASC
");
$stmt_alumni->execute([CURRENT_SCHOOL_ID]);
$alumniList = $stmt_alumni->fetchAll(PDO::FETCH_ASSOC);

// Fetch Donations List
$stmt_donations = $pdo->prepare("
    SELECT ad.*, s.first_name, s.last_name, a.passout_year 
    FROM alumni_donations ad
    JOIN alumni a ON ad.alumni_id = a.id
    JOIN students s ON a.student_id = s.id
    WHERE ad.school_id = ?
    ORDER BY ad.donation_date DESC, ad.id DESC
");
$stmt_donations->execute([CURRENT_SCHOOL_ID]);
$donations = $stmt_donations->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Alumni Donations | VIC ERP";
$page_header = "Alumni Management";
$active_menu = "alumni";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Record and review financial contributions and donation metrics</h5>
    <div class="d-flex gap-2">
        <a href="directory.php" class="btn btn-outline-primary">
            <i class="fa fa-graduation-cap me-1"></i> Alumni Directory
        </a>
        <a href="events.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-alt me-1"></i> Meet Events
        </a>
        <a href="donations.php" class="btn btn-success">
            <i class="fa fa-hand-holding-dollar me-1"></i> Donations
        </a>
        <a href="portal.php" class="btn btn-outline-info">
            <i class="fa fa-share-nodes me-1"></i> Networking & Stories
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

<!-- Analytics Cards -->
<div class="row g-3 mb-4 text-center">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-success text-white" style="border-radius: 12px;">
            <div class="card-body py-4">
                <i class="fa fa-money-bill-trend-up fs-2 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0">₹ <?= number_format($totalAmount, 2) ?></h3>
                <span class="text-uppercase small" style="font-size: 0.8rem; letter-spacing: 0.5px;">Total Funds Raised</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-primary text-white" style="border-radius: 12px;">
            <div class="card-body py-4">
                <i class="fa fa-people-carry-box fs-2 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0"><?= $totalDonors ?> Donors</h3>
                <span class="text-uppercase small" style="font-size: 0.8rem; letter-spacing: 0.5px;">Unique Contributors</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 bg-info text-white" style="border-radius: 12px;">
            <div class="card-body py-4">
                <i class="fa fa-calculator fs-2 mb-2 opacity-75"></i>
                <h3 class="fw-bold mb-0">₹ <?= number_format($avgDonation, 2) ?></h3>
                <span class="text-uppercase small" style="font-size: 0.8rem; letter-spacing: 0.5px;">Average Contribution</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Log Donation -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Log Contribution</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Choose Alumni Donor <span class="text-danger">*</span></label>
                        <select name="alumni_id" class="form-select" required>
                            <option value="">Select Alumni...</option>
                            <?php foreach($alumniList as $al): ?>
                                <option value="<?= $al['id'] ?>">
                                    <?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?> (Class of <?= (int)$al['passout_year'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount (₹) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="e.g. 10000" min="1" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Donation Date <span class="text-danger">*</span></label>
                        <input type="date" name="donation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <button type="submit" name="save_donation" class="btn btn-success w-100">
                        <i class="fa fa-receipt me-1"></i> Log Contribution
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Donations List -->
    <div class="col-lg-8">
        <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Contributions Ledger</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light text-start">
                            <tr>
                                <th class="ps-4">Alumni Donor</th>
                                <th>Passout Class</th>
                                <th>Contribution</th>
                                <th>Received On</th>
                            </tr>
                        </thead>
                        <tbody class="text-start">
                            <?php if (count($donations) > 0): ?>
                                <?php foreach($donations as $d): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-dark">
                                            <?= htmlspecialchars($d['first_name'] . ' ' . $d['last_name']) ?>
                                        </td>
                                        <td>Class of <?= (int)$d['passout_year'] ?></td>
                                        <td class="fw-bold text-success">₹ <?= number_format($d['amount'], 2) ?></td>
                                        <td>
                                            <span class="small text-muted"><i class="fa fa-calendar-alt me-1"></i> <?= date('d M Y', strtotime($d['donation_date'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fa fa-coins fs-2 mb-2 d-block"></i>
                                        No contributions recorded yet.
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
