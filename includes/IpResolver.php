<?php
class IpResolver {
    /**
     * Resolves real origin IP matching dynamic forwarding conditions
     */
    public static function resolve() {
        $ip = '127.0.0.1';

        // Check standard reverse proxy header groups safely
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Can contain comma-separated multiple hops list
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($parts[0]);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Clean internal IPv6 local loopback mapping representation to standard local IP
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }
}
?>
