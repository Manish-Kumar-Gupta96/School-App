<?php

declare(strict_types=1);

/**
 * ============================================================
 * VIC School ERP Enterprise
 * Audit Logger
 * ============================================================
 */

final class AuditLogger
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * ============================================================
     * Write Log
     * ============================================================
     */
    public function log(
        ?int $userId,
        string $module,
        string $action,
        string $description,
        string $status = 'SUCCESS',
        ?int $schoolId = null,
        ?int $recordId = null
    ): bool {
        $sql = "
        INSERT INTO audit_logs
        (
            school_id,
            user_id,
            module_name,
            action_name,
            record_id,
            description,
            status,
            ip_address,
            user_agent,
            created_at
        )
        VALUES
        (
            ?,?,?,?,?,?,?,?,?,NOW()
        )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $schoolId,
            $userId,
            $module,
            $action,
            $recordId,
            $description,
            strtoupper($status),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }

    /**
     * ============================================================
     * Login Log
     * ============================================================
     */
    public function login(
        int $userId,
        string $username,
        string $role,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Authentication',
            'LOGIN',
            "User {$username} logged in ({$role})",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Logout Log
     * ============================================================
     */
    public function logout(
        int $userId,
        string $username,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Authentication',
            'LOGOUT',
            "User {$username} logged out.",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Failed Login
     * ============================================================
     */
    public function failedLogin(
        string $username,
        string $reason,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            null,
            'Authentication',
            'FAILED_LOGIN',
            "{$username} : {$reason}",
            'FAILED',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Password Changed
     * ============================================================
     */
    public function passwordChanged(
        int $userId,
        string $username,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Security',
            'PASSWORD_CHANGE',
            "{$username} changed password.",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Profile Updated
     * ============================================================
     */
    public function profileUpdated(
        int $userId,
        string $username,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Profile',
            'UPDATE',
            "{$username} updated profile.",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Student Log
     * ============================================================
     */
    public function student(
        int $userId,
        string $action,
        int $studentId,
        string $studentName,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Student',
            strtoupper($action),
            "Student : {$studentName} (ID: {$studentId})",
            'SUCCESS',
            $schoolId,
            $studentId
        );
    }

    /**
     * ============================================================
     * Teacher Log
     * ============================================================
     */
    public function teacher(
        int $userId,
        string $action,
        int $teacherId,
        string $teacherName,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Teacher',
            strtoupper($action),
            "Teacher : {$teacherName} (ID: {$teacherId})",
            'SUCCESS',
            $schoolId,
            $teacherId
        );
    }

    /**
     * ============================================================
     * Parent Log
     * ============================================================
     */
    public function parentLog(
        int $userId,
        string $action,
        int $parentId,
        string $parentName,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Parent',
            strtoupper($action),
            "Parent : {$parentName} (ID: {$parentId})",
            'SUCCESS',
            $schoolId,
            $parentId
        );
    }

    /**
     * ============================================================
     * Fee Log
     * ============================================================
     */
    public function fee(
        int $userId,
        int $receiptId,
        float $amount,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Fee',
            'COLLECT',
            "Fee Collected : ₹{$amount}",
            'SUCCESS',
            $schoolId,
            $receiptId
        );
    }

    /**
     * ============================================================
     * Attendance Log
     * ============================================================
     */
    public function attendance(
        int $userId,
        string $class,
        string $section,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Attendance',
            'MARK',
            "Attendance Marked : {$class} {$section}",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Exam Log
     * ============================================================
     */
    public function exam(
        int $userId,
        string $exam,
        string $action,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Exam',
            strtoupper($action),
            "Exam : {$exam}",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Certificate Log
     * ============================================================
     */
    public function certificate(
        int $userId,
        int $studentId,
        string $certificateType,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Certificate',
            'GENERATE',
            "{$certificateType} Generated",
            'SUCCESS',
            $schoolId,
            $studentId
        );
    }

    /**
     * ============================================================
     * Backup Log
     * ============================================================
     */
    public function backup(
        int $userId,
        string $file,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Backup',
            'CREATE',
            $file,
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Restore Log
     * ============================================================
     */
    public function restore(
        int $userId,
        string $file,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Backup',
            'RESTORE',
            $file,
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Security Event
     * ============================================================
     */
    public function security(
        ?int $userId,
        string $event,
        string $message,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'Security',
            strtoupper($event),
            $message,
            'WARNING',
            $schoolId
        );
    }

    /**
     * ============================================================
     * API Log
     * ============================================================
     */
    public function api(
        ?int $userId,
        string $endpoint,
        int $statusCode,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            'API',
            'REQUEST',
            "{$endpoint} ({$statusCode})",
            'INFO',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Export Log
     * ============================================================
     */
    public function export(
        int $userId,
        string $module,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            $module,
            'EXPORT',
            "{$module} Exported",
            'SUCCESS',
            $schoolId
        );
    }

    /**
     * ============================================================
     * Import Log
     * ============================================================
     */
    public function import(
        int $userId,
        string $module,
        ?int $schoolId = null
    ): bool {
        return $this->log(
            $userId,
            $module,
            'IMPORT',
            "{$module} Imported",
            'SUCCESS',
            $schoolId
        );
    }
}
