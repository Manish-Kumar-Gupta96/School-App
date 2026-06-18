<?php
require_once('../config/database.php');
$active_menu = "attendance";
$page_title = "Child Attendance | Parent Portal";
require_once('includes/header.php');

$parent_id = $_SESSION['user_id'];

// Get child details
$stmt = $pdo->prepare("
    SELECT s.*, CONCAT(s.first_name, ' ', s.last_name) AS student_name 
    FROM students s
    JOIN parent_student_map psm ON s.id = psm.student_id
    WHERE psm.parent_id = ? AND s.school_id = ?
");
$stmt->execute([$parent_id, CURRENT_SCHOOL_ID]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$child) {
    $child = $pdo->query("SELECT *, CONCAT(first_name, ' ', last_name) AS student_name FROM students WHERE school_id = " . CURRENT_SCHOOL_ID . " LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

$attendance = [];
if($child){
    $stmt_att = $pdo->prepare("
        SELECT * FROM student_attendance
        WHERE student_id=?
        ORDER BY attendance_date DESC
    ");
    $stmt_att->execute([$child['id']]);
    $attendance = $stmt_att->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 text-start">
    <div>
        <h2 class="fw-bold text-dark mb-1">📅 Child Attendance Tracker</h2>
        <p class="text-muted mb-0">Student: <strong><?= htmlspecialchars($child['student_name'] ?? 'N/A') ?></strong> | Class: <strong><?= htmlspecialchars($child['class'] ?? 'N/A') ?></strong></p>
    </div>
</div>

<div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden; text-align: start;">
    <div class="card-header bg-white border-0 py-3 ps-4">
        <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-success"></i>Daily Presence Log</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center">
                <thead class="table-light text-start">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php if (count($attendance) > 0): ?>
                        <?php foreach($attendance as $a): ?>
                            <?php
                            $badge = 'success';
                            if ($a['status'] == 'Absent') $badge = 'danger';
                            elseif ($a['status'] == 'Leave') $badge = 'warning';
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark"><?= date('d M Y (l)', strtotime($a['attendance_date'])) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                        <?= htmlspecialchars($a['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">No attendance history logged yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('includes/footer.php'); ?>
