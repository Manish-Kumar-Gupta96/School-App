<?php
// cron/attendance_summary.php
// Expected to be run by CRON daily at midnight (e.g., 0 0 * * *)
// This rebuilds the `attendance_summary` table for the current month to ensure fast dashboard load times.

require_once(dirname(__DIR__) . '/config/database.php');

$now = new DateTime();
$month = (int)$now->format('m');
$year = (int)$now->format('Y');

echo "Running Daily Attendance Summary Aggregation for {$month}/{$year} at " . $now->format('Y-m-d H:i:s') . "\n";

try {
    $pdo->beginTransaction();

    // Aggregate attendance for the current month
    // We compute present, absent, late, halfday days
    // Percentage = (present + late + 0.5 * halfday) / total_marked * 100
    // Note: Late is counted as present for the sake of strict attendance percentage, 
    // but tracked separately as a metric. This logic can be adapted per school rules.
    $stmt_agg = $pdo->query("
        SELECT school_id, student_id,
               COUNT(id) as total_days,
               SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
               SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
               SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
               SUM(CASE WHEN status = 'halfday' THEN 1 ELSE 0 END) as halfday_days
        FROM attendance
        WHERE MONTH(attendance_date) = $month AND YEAR(attendance_date) = $year
        GROUP BY school_id, student_id
    ");

    $records = $stmt_agg->fetchAll(PDO::FETCH_ASSOC);

    // Prepare UPSERT statement
    $stmt_upsert = $pdo->prepare("
        INSERT INTO attendance_summary (school_id, student_id, month_no, year_no, present_days, absent_days, late_days, attendance_percentage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            present_days = VALUES(present_days),
            absent_days = VALUES(absent_days),
            late_days = VALUES(late_days),
            attendance_percentage = VALUES(attendance_percentage)
    ");

    $count = 0;
    foreach ($records as $r) {
        $attended = $r['present_days'] + $r['late_days'] + ($r['halfday_days'] * 0.5);
        $percentage = ($r['total_days'] > 0) ? round(($attended / $r['total_days']) * 100, 2) : 0;

        $stmt_upsert->execute([
            $r['school_id'],
            $r['student_id'],
            $month,
            $year,
            $r['present_days'],
            $r['absent_days'],
            $r['late_days'],
            $percentage
        ]);
        $count++;
    }

    $pdo->commit();
    echo "Successfully aggregated attendance for {$count} students.\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "CRITICAL ERROR: " . $e->getMessage() . "\n";
}
