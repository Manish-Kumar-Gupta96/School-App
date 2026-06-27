<?php
function hasPermission($pdo, $userId, $permission)
{
    // First check if user is super_admin or admin
    $roleCheck = $pdo->prepare("
        SELECT r.role_name, u.role_id
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = ?
    ");
    $roleCheck->execute([$userId]);
    $userRoleData = $roleCheck->fetch(PDO::FETCH_ASSOC);

    if ($userRoleData) {
        $roleName = strtolower(trim($userRoleData['role_name']));
        $roleId = $userRoleData['role_id'];
        
        // Grant full access to superadmins and admins
        if (in_array($roleName, ['super_admin', 'super admin', 'admin', 'school admin']) || in_array($roleId, [1, 2, 6, 7])) {
            return true;
        }
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
