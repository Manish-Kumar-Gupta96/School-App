<?php
require_once('../../middleware/verify-token.php');
require_once('../../../config/database.php');

try {
    $data = [
        'attendance' => '92%',
        'pending_fees' => '₹ 0.00',
        'homework' => 5,
        'notices' => 3
    ];
    success($data);
} catch (Exception $e) {
    error("Aggregation failed: " . $e->getMessage(), 500);
}
?>
