<?php
class ApiResponse {
    public static function send($statusCode, $status, $message, $data = null) {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        
        $response = [
            'status' => (bool)$status,
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
