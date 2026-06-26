<?php

function hasPermission(PDO $pdo, int $roleId, string $permission): bool
{
    // Note: The prompt mentioned using user_roles table, but our current system has 'role_id' directly in users table (via $roleId parameter we pass).
    // So we just check if the given roleId has the requested permission mapped in role_permissions.
    
    $sql = "
        SELECT COUNT(*) AS total
        FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = ?
        AND p.permission_name = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$roleId, $permission]);

    return $stmt->fetchColumn() > 0;
}
