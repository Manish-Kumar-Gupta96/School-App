<?php
function safe_password_verify($password, $hash) {
    if (php_sapi_name() === 'cli-server' && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $cmd = 'php -r "echo password_verify(\'' . addslashes($password) . '\', \'' . addslashes($hash) . '\') ? 1 : 0;"';
        return trim(shell_exec($cmd)) === '1';
    }
    return password_verify($password, $hash);
}
echo safe_password_verify('password', '$2y$10$9OnT.Dzfve3WWjFG6lImH.wExuPpFngZLo45wk432WKJyeYly.Erq') ? 'WORKS' : 'FAILS';
echo "\nok\n";
