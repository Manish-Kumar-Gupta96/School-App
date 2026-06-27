<?php
require_once __DIR__ . '/../includes/BaseController.php';
require_once __DIR__ . '/../includes/Csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

new BaseController();
// Sabhi logged-in staff/teachers isko access kar sakte hain
BaseController::enforceRole(['teacher', 'accountant', 'admin']); 

$db = getDBConnection();
$message = '';
$status = true;
$myId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_resignation'])) {
    if (!Csrf::validateToken($_POST['csrf_token'] ?? '')) die("CSRF Check Failed.");

    $relievingDate = $_POST['relieving_date'] ?? '';
    $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_SPECIAL_CHARS);

    // 1-Month Notice Check Logic (30 Days Mathematical Check)
    $today = new DateTime();
    $targetDate = new DateTime($relievingDate);
    $interval = $today->diff($targetDate);
    $daysDifference = $interval->days;

    // Agar date past ki hai ya gap 30 days se kam hai
    if ($targetDate <= $today || $daysDifference < 30) {
        $message = "Error: School policy ke mutabik aapko kam se kam 1 mahine (30 days) pehle notice dena zaroori hai. Aapki select ki gayi date bohot jaldi ki hai.";
        $status = false;
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO staff_resignations (staff_id, requested_relieving_date, reason, status) 
                                  VALUES (:sid, :r_date, :reason, 'PENDING')");
            $stmt->execute([
                ':sid'    => $myId,
                ':r_date' => $relievingDate,
                ':reason' => $reason
            ]);

            AuditLogger::log('RESIGNATION_SUBMITTED', "User ID #{$myId} submitted resignation for date: {$relievingDate}");
            $message = "Success! Aapka resignation notice 1-month criteria pass karke admin ko bhej diya gaya hai.";
        } catch (PDOException $e) {
            $message = "Database Error: " . $e->getMessage();
            $status = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resignation & Notice Submission Window</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 550px;">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white fw-bold">Official Staff Notice Period Submission</div>
        <div class="card-body p-4">
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $status ? 'success' : 'danger'; ?> small py-2"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="alert alert-warning small py-1 text-dark fw-bold">⚠️ Notice Policy: Job chhodne se kam se kam 30 din pehle management ko digital notice submit karna anivary (mandatory) hai.</div>

            <form action="" method="POST">
                <?php Csrf::injectInput(); ?>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Select Your Intended Last Working Day</label>
                    <input type="date" name="relieving_date" class="form-control form-control-sm" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Reason for Resignation</label>
                    <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="Please specify your reason" required></textarea>
                </div>
                
                <button type="submit" name="submit_resignation" class="btn btn-dark btn-sm w-100 fw-bold">Submit Official Notice</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
