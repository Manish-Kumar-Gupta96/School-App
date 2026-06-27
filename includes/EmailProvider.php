<?php
class EmailProvider {

    /**
     * Universal Dynamic Welcome Email Sender Pipeline
     * @param string $targetEmail Receiver email address
     * @param string $username Assigned Login Username
     * @param string $password Cleartext Temporary Password
     * @param string $role User system capacity role
     */
    public static function sendWelcomeCredentials($targetEmail, $username, $password, $role) {
        $subject = "Official Login Credentials | VIC Academy ERP Portal";
        
        $magicActivationUrl = "http://localhost/school-app/login.php?magic_user=" . urlencode($targetEmail);
        
        // Dynamic HTML Email Template Layout
        $htmlContent = "
        <html>
        <head>
            <style>
                .email-card { font-family: Arial, sans-serif; max-width: 500px; margin: auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
                .header { background-color: #212529; color: #fff; text-align: center; padding: 10px; border-radius: 6px 6px 0 0; }
                .credential-box { background-color: #f8f9fa; border-left: 4px solid #198754; padding: 12px; margin: 15px 0; font-family: monospace; }
                .btn-link { display: inline-block; padding: 8px 15px; background-color: #0d6efd; color: white !important; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 13px; }
                .footer { font-size: 11px; color: #6c757d; margin-top: 20px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='email-card'>
                <div class='header'><h3>VIC Academy ERP Systems</h3></div>
                <p>Hello <strong>{$username}</strong>,</p>
                <p>Aapka account VIC Academy School Management Portal par safaltapurvak create/approve kar diya gaya hai. Aapke access privileges ki details niche di gayi hain:</p>
                
                <div class='credential-box'>
                    <strong>System Panel Role:</strong> " . strtoupper($role) . "<br>
                    <strong>Login Username ID:</strong> {$username}<br>
                    <strong>Temporary Password:</strong> {$password}
                </div>

                <p style='color: #dc3545; font-size: 12px;'>* Suraksha ke liye, portal par pehli baar login karne ke baad 'My Profile' section mein jaakar apna password turant badal lein.</p>
                
                <p>Aap niche diye gaye button par click karke direct apna account active aur naya password set kar sakte hain:</p>
                <div style='text-align: center; margin-top: 15px;'>
                    <a href='{$magicActivationUrl}' class='btn-link' style='background-color: #198754;'>Direct Activate My Account</a>
                </div>

                <p>VIC Academy ke updates real-time paane ke liye niche diye gaye link par click karke hamara Official Global WhatsApp Group zaroori join karein:</p>
                <p><a href='https://chat.whatsapp.com/YOUR_GLOBAL_GROUP_CODE' style='color:#198754; font-weight:bold;'>Join Official School WhatsApp Group Matrix</a></p>
                
                <div style='text-align: center; margin-top: 20px;'>
                    <a href='http://localhost/school-app/login.php' class='btn-link'>Login to Dashboard Portal</a>
                </div>
                
                <div class='footer'>
                    This is an automated system generated email broadcast. Please do not reply directly to this node.
                </div>
            </div>
        </body>
        </html>";

        // Headers construction parameters for pure clean HTML email delivery
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: VIC Academy ERP <noreply@etechcomputereducation.com>" . "\r\n";

        // Track email sent
        try {
            $db = getDBConnection();
            $logStmt = $db->prepare("INSERT INTO system_email_logs (recipient_email, username_sent, assigned_role, status_flag) VALUES (:email, :uname, :role, :status)");
            $logStmt->execute([
                ':email' => $targetEmail,
                ':uname' => $username,
                ':role'  => $role,
                ':status'=> 'SENT'
            ]);
        } catch (PDOException $e) { /* Silent fallback control tracking codes */ }

        // Send email
        @mail($targetEmail, $subject, $htmlContent, $headers);
        return true;
    }
}
