<?php
function hasPermission($pdo, $userId, $permission)
{
    // First check if user is super_admin
    $roleCheck = $pdo->prepare("
        SELECT r.role_name
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
    ");
    $roleCheck->execute([$userId]);
    $role = $roleCheck->fetchColumn();

    if ($role === 'super_admin') {
        return true;
    }

    // Check for user-specific override
    $userOverride = $pdo->prepare("
        SELECT up.allow_deny
        FROM user_permissions up
        JOIN permissions p ON p.id = up.permission_id
        WHERE up.user_id = ? AND p.permission_name = ?
        LIMIT 1
    ");
    $userOverride->execute([$userId, $permission]);
    $override = $userOverride->fetchColumn();

    if ($override === 'allow') {
        return true;
    }
    if ($override === 'deny') {
        return false;
    }

    // Fall back to role-based permission
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM users u
        JOIN role_permissions rp ON rp.role_id = u.role_id
        JOIN permissions p ON p.id = rp.permission_id
        WHERE u.id = ? AND p.permission_name = ?
    ");
    $stmt->execute([$userId, $permission]);
    
    return $stmt->fetchColumn() > 0;
}
