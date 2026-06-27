<?php
require_once __DIR__ . '/../config/database.php';

function sendSystemMail($to, $subject, $body) {
    // If you plan to use standard composer packages later like PHPMailer, 
    // this wrapper ensures your business controllers won't change.
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: School System <no-reply@school.local>',
        'Reply-To: support@school.local',
        'X-Mailer: PHP/' . phpversion()
    ];

    // local check logic fallback for active SMTP constant routing
    if (defined('SMTP_HOST') && SMTP_HOST === '127.0.0.1') {
        // MailHog direct connection emulator block
        ini_set("SMTP", SMTP_HOST);
        ini_set("smtp_port", SMTP_PORT);
    }

    return mail($to, $subject, $body, implode("\r\n", $headers));
}
?>
