<?php
class BaseController {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Configuration: Agar user logged in nahi hai aur public page nahi hai, toh login par bhejien
        $publicPages = ['login.php', 'admissions-enquiry.php', 'public-notices.php'];
        if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), $publicPages)) {
            header('Location: /school-app/login.php');
            exit;
        }
    }

    /**
     * Enforces strict role-based access matrix boundaries
     * @param array $allowedRoles Roles that have access to this execution path
     */
    public static function enforceRole(array $allowedRoles) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRoleRaw = $_SESSION['role'] ?? $_SESSION['user_role'] ?? null;
        
        // Normalize role string to handle different DB naming conventions
        $userRole = strtolower((string)$userRoleRaw);
        if (in_array($userRole, ['super admin', 'super_admin', 'superadmin'])) {
            $userRole = 'superadmin';
        } elseif (in_array($userRole, ['school admin', 'admin'])) {
            $userRole = 'admin';
        }

        // Superadmin Bypass Logic
        if ($userRole === 'superadmin' && !in_array('student', $allowedRoles)) {
            return true;
        }

        if (!in_array($userRole, $allowedRoles)) {
            http_response_code(403);
            die("<!DOCTYPE html>
            <html lang='en'>
            <head><title>Access Forbidden</title>
            <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css'></head>
            <body class='bg-light d-flex align-items-center' style='height: 100vh;'>
                <div class='container text-center' style='max-width: 500px;'>
                    <div class='card p-5 border-0 shadow-sm'>
                        <h1 class='text-danger fw-bold display-4'>403</h1>
                        <h4 class='text-dark fw-semibold mb-3'>Suraqsha Chetawani: Access Denied</h4>
                        <p class='text-muted small'>Aapke paas is department ko access karne ke permission nahi hain. Is unauthorized entry code ko system records me block kar diya gaya hai.</p>
                        <a href='/school-app/dashboard.php' class='btn btn-dark btn-sm mt-3'>Return to Dashboard</a>
                    </div>
                </div>
            </body>
            </html>");
        }
    }

    public static function asset($path) {
        return '/school-app/assets/' . ltrim($path, '/');
    }
}
