<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Password Policy
 * -------------------------------------------------------------
 */

class PasswordPolicy
{
    public function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Minimum 8 characters required.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "At least one uppercase letter required.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "At least one lowercase letter required.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "At least one number required.";
        }
        if (!preg_match('/[\W]/', $password)) {
            $errors[] = "At least one special character required.";
        }

        return $errors;
    }

    public function isExpired(string $lastChanged, int $days = 90): bool
    {
        return strtotime($lastChanged) < strtotime("-{$days} days");
    }
}
