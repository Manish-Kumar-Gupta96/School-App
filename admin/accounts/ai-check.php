<?php
$root_path = "../../";
require_once($root_path . 'config/database.php');
require_once($root_path . 'includes/auth.php');

// Ensure role permission checks
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . $root_path . "login.php");
    exit;
}

$api_url = "http://127.0.0.1:5000";
$health_status = 'OFFLINE';
$ping_time = '-';

// Measure API Ping Time
$start_time = microtime(true);
try {
    $ch = curl_init("$api_url/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 1);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false) {
        $health_status = 'ONLINE';
        $ping_time = round((microtime(true) - $start_time) * 1000, 2) . ' ms';
    }
} catch (Exception $e) {
    // Silent
}

$page_title = "AI Engine Status Check | VIC ERP";
$page_header = "AI Microservices & ML Diagnoses Check";
$active_menu = "accounts";

require_once($root_path . 'admin/includes/header.php');
require_once($root_path . 'admin/includes/topbar.php');
?>

<div class="main-dashboard p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-dark mb-0">AI Engine Diagnoses Dashboard</h2>
        <a href="ledgers.php" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Ledger Book
        </a>
    </div>

    <!-- Health Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid <?= $health_status === 'ONLINE' ? '#198754' : '#dc3545' ?> !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Flask AI Endpoint Status</h6>
                <div class="d-flex align-items-center mt-2">
                    <span class="fs-3 fw-bold me-3 text-dark"><?= $health_status ?></span>
                    <span class="badge <?= $health_status === 'ONLINE' ? 'bg-success' : 'bg-danger' ?> p-2">
                        <i class="fa <?= $health_status === 'ONLINE' ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                    </span>
                </div>
                <p class="text-muted small mb-0 mt-2">Target API: <a href="<?= $api_url ?>" target="_blank" class="font-monospace text-decoration-none"><?= $api_url ?></a></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-4 text-start" style="border-radius: 12px; border-left: 4px solid #0d6efd !important;">
                <h6 class="text-muted small text-uppercase fw-bold mb-1">Connection Latency</h6>
                <h3 class="fw-bold text-primary mb-0 mt-2"><?= $ping_time ?></h3>
                <p class="text-muted small mb-0 mt-2">Response delivery speed</p>
            </div>
        </div>
    </div>

    <!-- Interactive AI Tests -->
    <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
        <h5 class="fw-bold text-dark mb-3"><i class="fa fa-stethoscope me-2 text-primary"></i>Interactive ML Models Live Verification</h5>
        <hr class="text-muted mt-0 mb-4">

        <div class="row g-4">
            <!-- Categorization Live check -->
            <div class="col-md-6">
                <div class="card p-3 border shadow-none" style="border-radius: 10px; background-color: #fafbfd;">
                    <h6 class="fw-bold text-primary mb-3"><i class="fa fa-tags me-2"></i>Auto-Categorization Test</h6>
                    <div class="mb-3">
                        <label class="form-label small text-muted fw-semibold">Enter Transaction Memo</label>
                        <input type="text" id="test_cat_memo" class="form-control form-control-sm" placeholder="e.g. Paid monthly utility electricity invoice" value="Paid monthly utility electricity invoice" style="border-radius: 6px;">
                    </div>
                    <button id="run_cat_test" class="btn btn-sm btn-primary w-100 fw-semibold" style="border-radius: 6px;">
                        <i class="fa fa-play me-1"></i> Categorize Memo
                    </button>
                    <div id="cat_result" class="mt-3 p-2 border rounded font-monospace small text-dark d-none" style="background-color: #fff;"></div>
                </div>
            </div>

            <!-- Anomaly live check -->
            <div class="col-md-6">
                <div class="card p-3 border shadow-none" style="border-radius: 10px; background-color: #fafbfd;">
                    <h6 class="fw-bold text-danger mb-3"><i class="fa fa-triangle-exclamation me-2"></i>Fraud & Anomaly Check</h6>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-semibold">Amount (₹)</label>
                            <input type="number" id="test_anom_amt" class="form-control form-control-sm" value="950000" style="border-radius: 6px;">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-semibold">Category</label>
                            <input type="text" id="test_anom_cat" class="form-control form-control-sm" value="Other" style="border-radius: 6px;">
                        </div>
                    </div>
                    <button id="run_anom_test" class="btn btn-sm btn-danger w-100 fw-semibold" style="border-radius: 6px;">
                        <i class="fa fa-shield-halved me-1"></i> Perform Anomaly Audit
                    </button>
                    <div id="anom_result" class="mt-3 p-2 border rounded font-monospace small text-dark d-none" style="background-color: #fff;"></div>
                </div>
            </div>

            <!-- Failure Risk live check -->
            <div class="col-md-6">
                <div class="card p-3 border shadow-none" style="border-radius: 10px; background-color: #fafbfd;">
                    <h6 class="fw-bold text-warning mb-3"><i class="fa fa-graduation-cap me-2 text-dark"></i>Student Failure Forecast</h6>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-semibold">Attendance Rate (%)</label>
                            <input type="number" id="test_fail_att" class="form-control form-control-sm" value="45" style="border-radius: 6px;">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small text-muted fw-semibold">Average Grade Score</label>
                            <input type="number" id="test_fail_grade" class="form-control form-control-sm" value="38" style="border-radius: 6px;">
                        </div>
                    </div>
                    <button id="run_fail_test" class="btn btn-sm btn-warning w-100 fw-semibold" style="border-radius: 6px;">
                        <i class="fa fa-chart-line me-1"></i> Predict Failure Risk
                    </button>
                    <div id="fail_result" class="mt-3 p-2 border rounded font-monospace small text-dark d-none" style="background-color: #fff;"></div>
                </div>
            </div>

            <!-- Attendance check -->
            <div class="col-md-6">
                <div class="card p-3 border shadow-none" style="border-radius: 10px; background-color: #fafbfd;">
                    <h6 class="fw-bold text-success mb-3"><i class="fa fa-calendar-check me-2"></i>Attendance Alerts Query</h6>
                    <p class="small text-muted mb-3">Scans the student register for profiles carrying attendance records falling below acceptable levels (< 75%).</p>
                    <button id="run_att_test" class="btn btn-sm btn-success w-100 fw-semibold" style="border-radius: 6px;">
                        <i class="fa fa-magnifying-glass me-1"></i> Fetch Attendance Alerts
                    </button>
                    <div id="att_result" class="mt-3 p-2 border rounded font-monospace small text-dark d-none" style="background-color: #fff; max-height: 120px; overflow-y: auto;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const apiBase = "<?= $api_url ?>";

// Categorize Test
document.getElementById('run_cat_test').addEventListener('click', function() {
    const memo = document.getElementById('test_cat_memo').value;
    const box = document.getElementById('cat_result');
    box.classList.remove('d-none');
    box.innerHTML = 'Connecting to AI Engine...';

    fetch(`${apiBase}/categorize`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ description: memo })
    })
    .then(r => r.json())
    .then(data => {
        box.innerHTML = `<strong>Response:</strong><br>${JSON.stringify(data, null, 2)}`;
    })
    .catch(err => {
        box.innerHTML = `<strong>Error:</strong><br>Could not communicate with Flask service. Check if service is started on port 5000.`;
    });
});

// Anomaly Test
document.getElementById('run_anom_test').addEventListener('click', function() {
    const amt = parseFloat(document.getElementById('test_anom_amt').value);
    const cat = document.getElementById('test_anom_cat').value;
    const box = document.getElementById('anom_result');
    box.classList.remove('d-none');
    box.innerHTML = 'Analyzing...';

    fetch(`${apiBase}/anomaly`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: amt, category: cat, description: 'Test audit run' })
    })
    .then(r => r.json())
    .then(data => {
        box.innerHTML = `<strong>Response:</strong><br>${JSON.stringify(data, null, 2)}`;
    })
    .catch(err => {
        box.innerHTML = `<strong>Error:</strong><br>Failed to execute anomaly analysis.`;
    });
});

// Failure Test
document.getElementById('run_fail_test').addEventListener('click', function() {
    const att = parseFloat(document.getElementById('test_fail_att').value);
    const grade = parseFloat(document.getElementById('test_fail_grade').value);
    const box = document.getElementById('fail_result');
    box.classList.remove('d-none');
    box.innerHTML = 'Predicting...';

    fetch(`${apiBase}/predict-failure`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ attendance_rate: att, average_grade: grade })
    })
    .then(r => r.json())
    .then(data => {
        box.innerHTML = `<strong>Response:</strong><br>${JSON.stringify(data, null, 2)}`;
    })
    .catch(err => {
        box.innerHTML = `<strong>Error:</strong><br>Failed to predict failure risk.`;
    });
});

// Attendance Test
document.getElementById('run_att_test').addEventListener('click', function() {
    const box = document.getElementById('att_result');
    box.classList.remove('d-none');
    box.innerHTML = 'Scanning...';

    fetch(`${apiBase}/attendance-alerts`)
    .then(r => r.json())
    .then(data => {
        box.innerHTML = `<strong>Response:</strong><br>${JSON.stringify(data, null, 2)}`;
    })
    .catch(err => {
        box.innerHTML = `<strong>Error:</strong><br>Failed to fetch attendance alerts.`;
    });
});
</script>

<?php
require_once($root_path . 'admin/includes/footer.php');
?>
