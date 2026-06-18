<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$slip = null;

if(isset($_GET['id'])){
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare("
        SELECT p.*, t.name AS teacher_name, t.email, t.phone AS mobile, t.photo, d.designation_name
        FROM employee_payroll p
        LEFT JOIN teachers t ON p.employee_id=t.id
        LEFT JOIN hr_designations d ON t.designation_id=d.id
        WHERE p.id=?
    ");
    $stmt->execute([$id]);
    $slip = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip - <?= htmlspecialchars($slip['teacher_name'] ?? 'Employee') ?> | VIC ERP</title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="../../assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="../../assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .salary-slip {
            max-width: 850px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        .school-header h3 {
            font-weight: 800;
            color: #0d6efd;
            letter-spacing: 0.5px;
        }
        .slip-title {
            font-weight: 700;
            color: #212529;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #0d6efd;
            display: inline-block;
            padding-bottom: 4px;
        }
        .table-salary th {
            font-weight: 600;
            color: #495057;
            background-color: #f8f9fa;
        }
        @media print {
            body {
                background-color: #fff;
            }
            .salary-slip {
                margin: 0;
                padding: 20px;
                box-shadow: none;
                border: none;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <?php if(!$slip): ?>
        <div class="alert alert-danger text-center shadow-sm border-0 my-5">
            <i class="fa fa-exclamation-triangle me-2"></i> Salary Slip Record Not Found.
        </div>
    <?php else: ?>
        <div class="no-print mb-4 d-flex justify-content-between align-items-center max-width" style="max-width: 850px; margin: auto;">
            <a href="payroll.php" class="btn btn-outline-secondary"><i class="fa fa-arrow-left me-1"></i> Back to Payroll</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print me-1"></i> Print Salary Slip</button>
        </div>

        <div class="salary-slip">
            <!-- School Header -->
            <div class="row align-items-center mb-4">
                <div class="col-md-2 text-center text-md-start mb-3 mb-md-0">
                    <img src="../../assets/logo.png" alt="VIC School Logo" style="height: 75px; max-width: 100%;" onerror="this.src='https://placehold.co/80x80?text=VIC+School'">
                </div>
                <div class="col-md-10 text-center text-md-end school-header">
                    <h3>VIC INTERNATIONAL SCHOOL</h3>
                    <p class="text-muted mb-0 small">Sector 15, Dwarka, New Delhi - 110075 | info@vicschool.edu.in</p>
                </div>
            </div>

            <div class="text-center my-4">
                <h5 class="slip-title">Salary Pay Slip</h5>
                <p class="text-muted mb-0">For the Month of <strong><?= date('F Y', strtotime($slip['payroll_month'] . '-01')) ?></strong></p>
            </div>

            <hr class="text-muted">

            <!-- Employee Info -->
            <div class="row my-4">
                <div class="col-md-6 text-start">
                    <p class="mb-2"><strong>Employee Name:</strong> <?= htmlspecialchars($slip['teacher_name']) ?></p>
                    <p class="mb-2"><strong>Designation:</strong> <?= htmlspecialchars($slip['designation_name'] ?: 'Teacher') ?></p>
                    <p class="mb-2"><strong>Mobile / Phone:</strong> <?= htmlspecialchars($slip['mobile'] ?: '-') ?></p>
                </div>
                <div class="col-md-6 text-start text-md-end">
                    <p class="mb-2"><strong>Receipt Ref:</strong> PO-<?= date('Ym', strtotime($slip['payroll_month'] . '-01')) ?>-<?= $slip['id'] ?></p>
                    <p class="mb-2"><strong>Payment Status:</strong> 
                        <span class="badge <?= $slip['payment_status'] == 'Paid' ? 'bg-success' : 'bg-warning' ?> px-3 py-1">
                            <?= htmlspecialchars($slip['payment_status']) ?>
                        </span>
                    </p>
                    <p class="mb-2"><strong>Email Address:</strong> <?= htmlspecialchars($slip['email'] ?: '-') ?></p>
                </div>
            </div>

            <!-- Salary Calculation Table -->
            <div class="table-responsive my-4">
                <table class="table table-bordered table-salary align-middle">
                    <thead>
                        <tr class="text-center">
                            <th>Earnings & Allowances</th>
                            <th>Amount (₹)</th>
                            <th>Deductions</th>
                            <th>Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td class="text-end">₹ <?= number_format($slip['basic_salary'], 2) ?></td>
                            <td>Provident Fund (PF)</td>
                            <td class="text-end">₹ 0.00</td>
                        </tr>
                        <tr>
                            <td>Dearness / House Rent Allowance</td>
                            <td class="text-end">₹ <?= number_format($slip['allowances'], 2) ?></td>
                            <td>Professional Tax / Deductions</td>
                            <td class="text-end">₹ <?= number_format($slip['deductions'], 2) ?></td>
                        </tr>
                        <tr class="table-light">
                            <td class="fw-bold">Gross Earnings</td>
                            <td class="text-end fw-bold text-success">₹ <?= number_format($slip['basic_salary'] + $slip['allowances'], 2) ?></td>
                            <td class="fw-bold">Total Deductions</td>
                            <td class="text-end fw-bold text-danger">₹ <?= number_format($slip['deductions'], 2) ?></td>
                        </tr>
                        <tr class="table-success">
                            <td colspan="2" class="fw-bold fs-5 text-start py-3">Net Payable Salary</td>
                            <td colspan="2" class="fw-bold fs-5 text-end py-3 text-success">₹ <?= number_format($slip['net_salary'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Signature Spaces -->
            <div class="row mt-5 pt-4">
                <div class="col-6 text-start">
                    <div style="border-top: 1px solid #dee2e6; width: 180px; margin-top: 50px;" class="mb-2"></div>
                    <span class="text-muted small fw-semibold">Employee Signature</span>
                </div>
                <div class="col-6 text-end">
                    <div style="border-top: 1px solid #dee2e6; width: 180px; margin-top: 50px; margin-left: auto;" class="mb-2"></div>
                    <span class="text-muted small fw-semibold">Authorized Signature</span>
                </div>
            </div>

            <div class="text-center mt-5 no-print">
                <p class="text-muted small mb-0"><i class="fa fa-info-circle me-1"></i>This is a system generated salary slip and does not require a physical stamp under standard ERP operations.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
