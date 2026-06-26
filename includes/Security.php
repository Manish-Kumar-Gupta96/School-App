<?php
/**
 * ------------------------------------------------------------------
 * VIC School ERP Enterprise v2.0
 * Security Class
 * ------------------------------------------------------------------
 * Author  : OpenAI
 * Version : 2.0
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

class Security
{
    /**
     * PDO Instance
     */
    private PDO $db;

    /**
     * Constructor
     */
    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    /* ============================================================
     * SECURITY HEADERS
     * ============================================================
     */

    public function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');

        if (!empty($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        header(
            "Content-Security-Policy:
            default-src 'self';
            img-src 'self' data: https:;
            style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
            font-src https://fonts.gstatic.com data:;
            script-src 'self' 'unsafe-inline' https:;
            connect-src 'self';"
        );
    }

    /* ============================================================
     * INPUT SANITIZE
     * ============================================================
     */

    public function sanitizeString(?string $value): string
    {
        $value = trim((string)$value);
        $value = strip_tags($value);
        $value = htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        return $value;
    }

    public function sanitizeEmail(?string $email): string
    {
        return filter_var(
            trim((string)$email),
            FILTER_SANITIZE_EMAIL
        );
    }

    public function sanitizeInt($value): int
    {
        return (int)filter_var(
            $value,
            FILTER_SANITIZE_NUMBER_INT
        );
    }

    public function sanitizeFloat($value): float
    {
        return (float)filter_var(
            $value,
            FILTER_SANITIZE_NUMBER_FLOAT,
            FILTER_FLAG_ALLOW_FRACTION
        );
    }

    public function sanitizeArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {

            if (is_array($value)) {

                $clean[$key] = $this->sanitizeArray($value);

            } else {

                $clean[$key] = $this->sanitizeString($value);
            }
        }

        return $clean;
    }

    /* ============================================================
     * OUTPUT ESCAPE
     * ============================================================
     */

    public function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    /* ============================================================
     * PASSWORD
     * ============================================================
     */

    public function hashPassword(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_DEFAULT
        );
    }

    public function verifyPassword(
        string $password,
        string $hash
    ): bool {

        return password_verify(
            $password,
            $hash
        );
    }

    /* ============================================================
     * PASSWORD POLICY
     * ============================================================
     */

    public function validatePassword(string $password): array
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

    /* ============================================================
     * RANDOM TOKEN
     * ============================================================
     */

    public function generateToken(
        int $length = 64
    ): string {

        return bin2hex(
            random_bytes(
                intval($length / 2)
            )
        );
    }

    public function generateNumericOTP(
        int $length = 6
    ): string {

        $otp = '';

        for ($i = 0; $i < $length; $i++) {

            $otp .= random_int(0, 9);

        }

        return $otp;
    }

    /* ============================================================
     * UUID
     * ============================================================
     */

    public function uuid(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf(
            '%s%s-%s-%s-%s-%s%s%s',
            str_split(bin2hex($data), 4)
        );
    }

    /* ============================================================
     * CLIENT IP ADDRESS
     * ============================================================
     */

    public function getClientIp(): string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {

            if (!empty($_SERVER[$key])) {

                $ips = explode(',', $_SERVER[$key]);

                foreach ($ips as $ip) {

                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return '0.0.0.0';
    }

    /* ============================================================
     * USER AGENT
     * ============================================================
     */

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /* ============================================================
     * OPERATING SYSTEM
     * ============================================================
     */

    public function getOperatingSystem(): string
    {
        $agent = $this->getUserAgent();

        $systems = [

            'Windows 11' => '/Windows NT 10.0/i',
            'Windows 10' => '/Windows NT 10.0/i',
            'Windows 8.1' => '/Windows NT 6.3/i',
            'Windows 8' => '/Windows NT 6.2/i',
            'Windows 7' => '/Windows NT 6.1/i',

            'Linux' => '/Linux/i',

            'Ubuntu' => '/Ubuntu/i',

            'Mac OS' => '/Macintosh|Mac OS X/i',

            'iPhone' => '/iPhone/i',

            'iPad' => '/iPad/i',

            'Android' => '/Android/i'
        ];

        foreach ($systems as $name => $regex) {

            if (preg_match($regex, $agent)) {
                return $name;
            }

        }

        return 'Unknown';
    }

    /* ============================================================
     * BROWSER
     * ============================================================
     */

    public function getBrowser(): string
    {
        $agent = $this->getUserAgent();

        $browsers = [

            'Edge' => '/Edg/i',

            'Chrome' => '/Chrome/i',

            'Firefox' => '/Firefox/i',

            'Safari' => '/Safari/i',

            'Opera' => '/Opera|OPR/i',

            'Internet Explorer' => '/MSIE|Trident/i'
        ];

        foreach ($browsers as $name => $regex) {

            if (preg_match($regex, $agent)) {

                return $name;

            }

        }

        return 'Unknown';
    }

    /* ============================================================
     * DEVICE TYPE
     * ============================================================
     */

    public function getDevice(): string
    {
        $agent = strtolower($this->getUserAgent());

        if (preg_match('/tablet|ipad/', $agent)) {
            return 'Tablet';
        }

        if (preg_match('/mobile|android|iphone/', $agent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /* ============================================================
     * SESSION FINGERPRINT
     * ============================================================
     */

    public function createFingerprint(): string
    {
        return hash(
            'sha256',
            $this->getClientIp()
            .
            $this->getUserAgent()
        );
    }

    public function verifyFingerprint(): bool
    {
        if (!isset($_SESSION['fingerprint'])) {

            $_SESSION['fingerprint']
                =
                $this->createFingerprint();

            return true;
        }

        return hash_equals(
            $_SESSION['fingerprint'],
            $this->createFingerprint()
        );
    }

    /* ============================================================
     * SESSION SECURITY
     * ============================================================
     */

    public function regenerateSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id(true);

        $_SESSION['last_regeneration'] = time();
    }

    public function autoRegenerate(
        int $seconds = 300
    ): void {

        if (
            !isset($_SESSION['last_regeneration'])
        ) {

            $_SESSION['last_regeneration']
                =
                time();

            return;
        }

        if (
            time()
            -
            $_SESSION['last_regeneration']
            >
            $seconds
        ) {

            $this->regenerateSession();

        }

    }

    /* ============================================================
     * SESSION TIMEOUT
     * ============================================================
     */

    public function checkIdleTimeout(
        int $minutes = 30
    ): bool {

        if (
            !isset($_SESSION['last_activity'])
        ) {

            $_SESSION['last_activity']
                =
                time();

            return true;
        }

        if (
            time()
            -
            $_SESSION['last_activity']
            >
            ($minutes * 60)
        ) {

            session_destroy();

            return false;
        }

        $_SESSION['last_activity']
            =
            time();

        return true;
    }

    /* ============================================================
     * SECURE COOKIE
     * ============================================================
     */

    public function setSecureCookie(
        string $name,
        string $value,
        int $expire
    ): bool {

        return setcookie(

            $name,

            $value,

            [

                'expires' => $expire,

                'path' => '/',

                'secure' => !empty($_SERVER['HTTPS']),

                'httponly' => true,

                'samesite' => 'Strict'

            ]

        );
    }

    /* ============================================================
     * AUDIT LOG
     * ============================================================
     */

    public function auditLog(
        ?int $userId,
        string $module,
        string $action,
        ?int $recordId = null,
        string $description = ''
    ): bool {

        try {

            $sql = "INSERT INTO audit_logs
            (
                user_id,
                module_name,
                action_name,
                record_id,
                description,
                ip_address,
                created_at
            )
            VALUES
            (
                ?,?,?,?,?,?,NOW()
            )";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                $userId,
                $module,
                $action,
                $recordId,
                $description,
                $this->getClientIp()
            ]);

        } catch (Throwable $e) {

            error_log($e->getMessage());

            return false;

        }

    }

    /* ============================================================
     * LOGIN LOG
     * ============================================================
     */

    public function loginLog(
        int $userId,
        string $username,
        string $role,
        string $status = 'success'
    ): bool {

        try {

            $sql = "INSERT INTO login_logs
            (
                user_id,
                username,
                role_name,
                login_time,
                ip_address,
                browser,
                platform,
                device,
                login_status
            )
            VALUES
            (
                ?,?,?,NOW(),?,?,?,?,?
            )";

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                $userId,
                $username,
                $role,
                $this->getClientIp(),
                $this->getBrowser(),
                $this->getOperatingSystem(),
                $this->getDevice(),
                $status
            ]);

        } catch (Throwable $e) {

            error_log($e->getMessage());

            return false;

        }

    }

    /* ============================================================
     * FAILED LOGIN
     * ============================================================
     */

    public function failedLogin(string $username): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO failed_logins
            (
                username,
                ip_address,
                attempt_time,
                browser
            )
            VALUES
            (
                ?,?,NOW(),?
            )"
        );

        $stmt->execute([
            $username,
            $this->getClientIp(),
            $this->getBrowser()
        ]);
    }

    /* ============================================================
     * LOGIN ATTEMPTS
     * ============================================================
     */

    public function loginAttempts(
        string $username,
        int $minutes = 15
    ): int {

        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM failed_logins
             WHERE username=?
             AND attempt_time >=
             DATE_SUB(
                NOW(),
                INTERVAL ? MINUTE
             )"
        );

        $stmt->execute([
            $username,
            $minutes
        ]);

        return (int)$stmt->fetchColumn();
    }

    /* ============================================================
     * ACCOUNT LOCK CHECK
     * ============================================================
     */

    public function isBlocked(
        string $username,
        int $maxAttempts = 5
    ): bool {

        return
        $this->loginAttempts(
            $username
        ) >= $maxAttempts;

    }

    /* ============================================================
     * PASSWORD EXPIRY
     * ============================================================
     */

    public function passwordExpired(
        string $lastChanged,
        int $days = 90
    ): bool {

        return
        strtotime($lastChanged)
        <
        strtotime("-{$days} days");

    }

    /* ============================================================
     * REMEMBER TOKEN
     * ============================================================
     */

    public function rememberToken(): string
    {
        return bin2hex(
            random_bytes(32)
        );
    }

    /* ============================================================
     * SAVE REMEMBER TOKEN
     * ============================================================
     */

    public function saveRememberToken(
        int $userId,
        string $selector,
        string $validatorHash,
        string $expires
    ): bool {

        $stmt = $this->db->prepare(
            "INSERT INTO remember_tokens
            (
                user_id,
                selector,
                validator_hash,
                expires_at
            )
            VALUES
            (
                ?,?,?,?
            )"
        );

        return $stmt->execute([
            $userId,
            $selector,
            $validatorHash,
            $expires
        ]);
    }

    /* ============================================================
     * RANDOM STRING
     * ============================================================
     */

    public function randomString(
        int $length = 20
    ): string {

        return substr(
            str_replace(
                [
                    '/',
                    '+',
                    '='
                ],
                '',
                base64_encode(
                    random_bytes(64)
                )
            ),
            0,
            $length
        );

    }

    /* ============================================================
     * SECURITY EVENT
     * ============================================================
     */

    public function logSecurityEvent(
        string $event,
        string $message
    ): void {

        error_log(
            '[' .
            date('Y-m-d H:i:s') .
            '] ' .
            $event .
            ' : ' .
            $message
        );

    }
}
