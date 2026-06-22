<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    /**
     * Check if a user is logged in
     */
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current logged-in user details
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        return [
            'id'          => $_SESSION['user_id'],
            'role_id'     => $_SESSION['role_id'] ?? null,
            'role'        => $_SESSION['role'] ?? null,
            'name'        => $_SESSION['name'] ?? null,
            'admin_id'    => $_SESSION['admin_id'] ?? null,
            'teacher_id'  => $_SESSION['teacher_id'] ?? null,
            'student_id'  => $_SESSION['student_id'] ?? null,
            'parent_id'   => $_SESSION['parent_id'] ?? null,
        ];
    }

    /**
     * Get active user role
     */
    public static function role() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Set session variables upon login
     */
    public static function login($user, $role_name, $role_specific_id) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['name']    = $user['name'] ?? '';
        $_SESSION['role']    = $role_name;

        if ($role_name === 'admin') {
            $_SESSION['admin_id'] = $role_specific_id;
        } elseif ($role_name === 'teacher') {
            $_SESSION['teacher_id'] = $role_specific_id;
        } elseif ($role_name === 'parent') {
            $_SESSION['parent_id'] = $role_specific_id;
        } elseif ($role_name === 'student') {
            $_SESSION['student_id'] = $role_specific_id;
        }
    }

    /**
     * Destroys current session (logout)
     */
    public static function logout() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
