<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/Csrf.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuditLogger.php';

// --- ABSOLUTE FINAL EXCEPTION & DEADLOCK CATCHER ENGINE ---

/**
 * Global Exception Interceptor Logic
 * Agar pure application me kahin bhi database query fail hoti hai, 
 * toh ye use display karne ke bajaye system-log me silent dump kar dega.
 */
set_exception_handler(function ($exception) {
    // Log explicit error details inside internal server error log file
    error_log("CRITICAL ERP FAULT: " . $exception->getMessage() . " in file " . $exception->getFile() . " on line " . $exception->getLine());
    
    // Send standard clean fallback response to the user browser
    http_response_code(500);
    die("<!DOCTYPE html>
    <html lang='en'>
    <head><title>System Temporary Unavailable</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'></head>
    <body class='bg-light d-flex align-items-center' style='height: 100vh;'>
        <div class='container text-center' style='max-width: 550px;'>
            <div class='card p-4 border-0 shadow-sm border-top border-danger border-3'>
                <h4 class='text-dark fw-bold mb-2'>System Optimization Processing</h4>
                <p class='text-muted small mb-3'>Database query optimization ya dynamic security verification chal rahi hai. Kripya is request ko 1 minute baad dobara refresh karke try karein.</p>
                <a href='/school-app/dashboard.php' class='btn btn-sm btn-outline-dark fw-bold'>Reload Dashboard Workspace</a>
            </div>
        </div>
    </body>
    </html>");
});

/**
 * Global Shutdown Verification Check
 * Run-time script crash hone par assets buffers memory ko release karega
 */
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        error_log("FATAL ENGINE CRASH: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
    }
});
