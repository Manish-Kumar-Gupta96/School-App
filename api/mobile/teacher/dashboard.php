<?php
require_once('../../middleware/verify-token.php');

$data = [
    'classes' => 6,
    'students' => 240,
    'pending_marks' => 18,
    'pending_attendance' => 2
];
success($data);
?>
