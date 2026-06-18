<?php
require_once('../../middleware/verify-token.php');

$data = [
    'child_name' => 'Rahul Sharma',
    'attendance' => '95%',
    'fees_status' => 'PAID',
    'latest_result' => 'A Grade'
];
success($data);
?>
