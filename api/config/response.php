<?php
function success($data) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'status' => true,
        'data' => $data
    ]);
    exit;
}

function error($message, $code = 400) {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code($code);
    echo json_encode([
        'status' => false,
        'message' => $message
    ]);
    exit;
}
?>
