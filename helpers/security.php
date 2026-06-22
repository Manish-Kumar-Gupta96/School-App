<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    /**
     * Escape HTML output for XSS protection
     */
    function e($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf')) {
    /**
     * Generate or fetch CSRF token
     */
    function csrf() {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verify submitted CSRF token
     */
    function verify_csrf($token) {
        if (empty($_SESSION['csrf']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf'], $token);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize inputs recursively or directly by trimming and stripping HTML tags
     */
    function sanitize($value) {
        if (is_array($value)) {
            return array_map('sanitize', $value);
        }
        return trim(strip_tags($value ?? ''));
    }
}

if (!function_exists('canTeacherCreateClass')) {
    /**
     * Validate if a teacher is assigned to a specific class, section, and subject.
     */
    function canTeacherCreateClass(
        PDO $pdo,
        int $teacherId,
        int $classId,
        int $sectionId,
        int $subjectId
    ): bool {
        $stmt = $pdo->prepare("
            SELECT id
            FROM teacher_assignments
            WHERE teacher_id = ?
            AND class_id = ?
            AND section_id = ?
            AND subject_id = ?
            LIMIT 1
        ");
        $stmt->execute([
            $teacherId,
            $classId,
            $sectionId,
            $subjectId
        ]);
        return $stmt->fetch() !== false;
    }
}
