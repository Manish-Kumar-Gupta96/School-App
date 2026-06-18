<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied: Admin authorization required.");
}

$keyword = trim($_GET['keyword'] ?? '');
$module_filter = trim($_GET['module_filter'] ?? '');
$from_date = trim($_GET['from_date'] ?? '');
$to_date = trim($_GET['to_date'] ?? '');

$query_str = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if ($keyword !== '') {
    $query_str .= " AND (action LIKE ? OR user_name LIKE ? OR ip_address LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($module_filter !== '') {
    $query_str .= " AND module_name = ?";
    $params[] = $module_filter;
}

if ($from_date !== '') {
    $query_str .= " AND DATE(created_at) >= ?";
    $params[] = $from_date;
}

if ($to_date !== '') {
    $query_str .= " AND DATE(created_at) <= ?";
    $params[] = $to_date;
}

$query_str .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query_str);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=audit_logs_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, [
    'Log ID',
    'User ID',
    'User Name',
    'Role',
    'Action',
    'Module Name',
    'Record ID',
    'IP Address',
    'User Agent',
    'Timestamp'
]);

foreach($logs as $row) {
    fputcsv($output, [
        $row['id'],
        $row['user_id'],
        $row['user_name'],
        $row['role_name'],
        $row['action'],
        $row['module_name'],
        $row['record_id'],
        $row['ip_address'],
        $row['user_agent'],
        $row['created_at']
    ]);
}

fclose($output);
exit;
