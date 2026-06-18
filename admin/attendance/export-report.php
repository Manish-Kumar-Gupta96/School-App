<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$type = $_GET['type'] ?? 'csv';
$class = $_GET['class'] ?? '';
$month = $_GET['month'] ?? date('m');
$year  = $_GET['year'] ?? date('Y');

if (!$class) {
    die("Class parameter is required.");
}

// 1. Fetch data
$stmt = $pdo->prepare("SELECT id, admission_no, first_name, last_name FROM students WHERE class = ? ORDER BY first_name ASC");
$stmt->execute([$class]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$report_data = [];
foreach ($students as $student) {
    $student_id = $student['id'];
    
    $stmt_att = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
            SUM(CASE WHEN status = 'Half Day' THEN 1 ELSE 0 END) as half_day
        FROM attendance
        WHERE student_id = ? 
        AND MONTH(attendance_date) = ?
        AND YEAR(attendance_date) = ?
    ");
    $stmt_att->execute([$student_id, $month, $year]);
    $counts = $stmt_att->fetch(PDO::FETCH_ASSOC);

    $total = (int)$counts['total'];
    $present = (int)$counts['present'];
    $absent = (int)$counts['absent'];
    $late = (int)$counts['late'];
    $half_day = (int)$counts['half_day'];

    $weight_sum = $present + $late + ($half_day * 0.5);
    $percentage = $total > 0 ? round(($weight_sum / $total) * 100) : 0;

    $report_data[] = [
        'admission_no' => $student['admission_no'],
        'name' => $student['first_name'] . ' ' . $student['last_name'],
        'present' => $present,
        'absent' => $absent,
        'late' => $late,
        'half_day' => $half_day,
        'total' => $total,
        'percentage' => $percentage
    ];
}

$months_list = [
    '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
    '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
    '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'
];
$month_name = $months_list[$month];

/* ==========================
CSV EXPORT
========================== */
if ($type === 'csv') {
    $filename = "attendance_report_{$class}_{$month_name}_{$year}.csv";
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header Row
    fputcsv($output, ['Admission No', 'Student Name', 'Present Days', 'Absent Days', 'Late Days', 'Half Days', 'Total Classes', 'Attendance %']);
    
    // Data Rows
    foreach ($report_data as $row) {
        fputcsv($output, [
            $row['admission_no'],
            $row['name'],
            $row['present'],
            $row['absent'],
            $row['late'],
            $row['half_day'],
            $row['total'],
            $row['percentage'] . '%'
        ]);
    }
    fclose($output);
    exit();
}

/* ==========================
PDF/PRINT LAYOUT
========================== */
if ($type === 'pdf') {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance Report - <?= htmlspecialchars($class) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fff; padding: 20px; }
        .school-header { text-align: center; margin-bottom: 30px; }
        .school-name { font-size: 28px; font-weight: bold; }
        .report-title { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <button onclick="window.close()" class="btn btn-secondary">Close Window</button>
            <button onclick="window.print()" class="btn btn-success">Print Report</button>
        </div>

        <div class="school-header">
            <div class="school-name">VIC SCHOOL</div>
            <div class="text-muted">Monthly Student Attendance Report</div>
            <div class="report-title mt-2">Class: <?= htmlspecialchars($class) ?> | Month: <?= $month_name ?> <?= $year ?></div>
        </div>

        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-light">
                <tr>
                    <th>Admission No</th>
                    <th class="text-start">Student Name</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Late</th>
                    <th>Half Day</th>
                    <th>Total</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($report_data as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['admission_no']) ?></td>
                        <td class="text-start fw-semibold"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="text-success"><?= $row['present'] ?></td>
                        <td class="text-danger"><?= $row['absent'] ?></td>
                        <td class="text-warning"><?= $row['late'] ?></td>
                        <td class="text-info"><?= $row['half_day'] ?></td>
                        <td><?= $row['total'] ?></td>
                        <td class="fw-bold"><?= $row['percentage'] ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<?php
    exit();
}
?>
