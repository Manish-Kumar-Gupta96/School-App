<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:fee-list.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT
        f.*,
        s.admission_no,
        s.first_name,
        s.last_name,
        s.class
    FROM fees f
    LEFT JOIN students s ON f.student_id = s.id
    WHERE f.id = ?
");

$stmt->execute([$id]);
$fee = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$fee){
    die("Receipt Not Found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee Receipt - <?= htmlspecialchars($fee['receipt_no']) ?></title>
    <!-- Use local bootstrap directly to prevent blocking/warnings -->
    <link rel="stylesheet" href="../../assets/css/libs/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/libs/fa/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            padding: 40px 0;
        }
        .receipt {
            max-width: 800px;
            margin: auto;
            border: 2px solid #212529;
            padding: 40px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-radius: 8px;
        }
        .school-name {
            text-align: center;
            font-size: 32px;
            font-weight: 800;
            color: #0F4C81;
            letter-spacing: 1px;
        }
        .school-address {
            text-align: center;
            margin-bottom: 25px;
            color: #6c757d;
            font-size: 14px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 30px;
            border-bottom: 2px dashed #dee2e6;
            padding-bottom: 10px;
            color: #495057;
        }
        .signature {
            margin-top: 80px;
            text-align: right;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt">
        <div class="school-name">VIC SCHOOL</div>
        <div class="school-address">
            <i class="fa fa-location-dot me-1"></i> Main Campus, Sector-4, School Block, New Delhi<br>
            <i class="fa fa-phone me-1"></i> +91 XXXXX XXXXX | <i class="fa fa-envelope me-1"></i> billing@vicschool.edu.in
        </div>
        
        <div class="receipt-title">FEE PAYMENT RECEIPT</div>
        
        <div class="row mb-4">
            <div class="col-6">
                <strong>Receipt No:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($fee['receipt_no']) ?></span>
            </div>
            <div class="col-6 text-end">
                <strong>Date:</strong> <?= date('d M Y', strtotime($fee['payment_date'])) ?>
            </div>
        </div>

        <div class="row mb-4 bg-light p-3 rounded">
            <div class="col-md-4 mb-2 mb-md-0">
                <strong>Admission No:</strong><br>
                <span class="badge bg-secondary fs-6 mt-1"><?= htmlspecialchars($fee['admission_no'] ?: 'N/A') ?></span>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <strong>Student Name:</strong><br>
                <span class="fw-semibold text-dark fs-6 d-block mt-1"><?= htmlspecialchars($fee['first_name'] . ' ' . $fee['last_name']) ?></span>
            </div>
            <div class="col-md-4">
                <strong>Class:</strong><br>
                <span class="text-dark fs-6 d-block mt-1"><?= htmlspecialchars($fee['class'] ?: 'N/A') ?></span>
            </div>
        </div>

        <table class="table table-bordered table-striped align-middle mt-4">
            <tbody>
                <tr>
                    <th width="35%">Fee Month</th>
                    <td class="fw-semibold"><?= htmlspecialchars($fee['fee_month']) ?></td>
                </tr>
                <tr>
                    <th>Base Tuition Fee</th>
                    <td class="fw-bold">₹ <?= number_format($fee['amount'], 2) ?></td>
                </tr>
                <tr>
                    <th>Late Fee / Fine</th>
                    <td class="text-danger">₹ <?= number_format($fee['fine'], 2) ?></td>
                </tr>
                <tr>
                    <th>Discount / Waiver</th>
                    <td class="text-success">₹ -<?= number_format($fee['discount'], 2) ?></td>
                </tr>
                <tr class="table-light">
                    <th>Paid Amount</th>
                    <td class="text-success fw-bold">₹ <?= number_format($fee['paid_amount'], 2) ?></td>
                </tr>
                <tr class="table-danger">
                    <th>Remaining Due</th>
                    <td class="text-danger fw-bold">₹ <?= number_format($fee['due_amount'], 2) ?></td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td><span class="badge bg-info text-dark px-3 py-1"><?= htmlspecialchars($fee['payment_method']) ?></span></td>
                </tr>
                <tr>
                    <th>Payment Status</th>
                    <td>
                        <?php 
                        $status_color = ($fee['status'] == 'Paid') ? 'success' : (($fee['status'] == 'Partial') ? 'warning text-dark' : 'danger');
                        ?>
                        <span class="badge bg-<?= $status_color ?> px-3 py-1"><?= strtoupper($fee['status']) ?></span>
                    </td>
                </tr>
                <?php if(!empty($fee['remarks'])): ?>
                    <tr>
                        <th>Remarks</th>
                        <td class="text-muted small"><?= nl2br(htmlspecialchars($fee['remarks'])) ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signature">
            <p class="mb-0">________________________</p>
            <span class="text-muted small">Authorized Signature</span>
        </div>
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-success px-4 me-2">
            <i class="fa fa-print me-1"></i> Print Receipt
        </button>
        <a href="fee-list.php" class="btn btn-secondary px-4">
            Back
        </a>
    </div>
</div>

</body>
</html>
