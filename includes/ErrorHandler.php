<?php
class ErrorHandler {
    public static function handleException(Throwable $exception) {
        $logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . 'error_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        
        // Detailed stack trace for internal debugging
        $logMessage = "[{$timestamp}] Uncaught Exception: " . $exception->getMessage() . 
                      " in " . $exception->getFile() . " on line " . $exception->getLine() . PHP_EOL .
                      "Stack Trace: " . $exception->getTraceAsString() . PHP_EOL . str_repeat('-', 50) . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Friendly output to end-users without leaks
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode(['status' => false, 'message' => 'An internal server error occurred. Please contact admin.']);
        exit;
    }

    public static function handleError($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    }
}

// Global register calls - Add this to index.php or initial config loading file
set_exception_handler(['ErrorHandler', 'handleException']);
set_error_handler(['ErrorHandler', 'handleError']);
?>
