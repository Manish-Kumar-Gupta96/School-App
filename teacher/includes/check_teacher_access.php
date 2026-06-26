<?php
// teacher/includes/check_teacher_access.php

/**
 * Validates if a teacher is explicitly mapped to the requested class, section, and subject.
 * 
 * @param PDO $pdo The database connection instance
 * @param int $teacher_id The ID of the teacher
 * @param int $class_id The requested Class ID
 * @param int $section_id The requested Section ID
 * @param int $subject_id The requested Subject ID
 * @param int $schoolId The multi-tenant school ID
 * @return bool True if authorized, False otherwise
 */
function teacherHasAccess($pdo, $teacher_id, $class_id, $section_id, $subject_id, $schoolId = 1) {
    $stmt = $pdo->prepare("
        SELECT id 
        FROM teacher_class_subjects 
        WHERE teacher_id = ? 
          AND class_id = ? 
          AND section_id = ? 
          AND subject_id = ?
          AND school_id = ?
    ");
    $stmt->execute([$teacher_id, $class_id, $section_id, $subject_id, $schoolId]);
    return $stmt->rowCount() > 0;
}
