<?php
if (session_status() == PHP_SESSION_NONE) {
    @session_set_cookie_params([
        'httponly' => true,
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Strict'
    ]);
}

date_default_timezone_set('Asia/Kolkata');

if (!headers_sent()) {
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
}

require_once(__DIR__ . '/../helpers/security.php');
require_once(__DIR__ . '/../helpers/upload.php');

// Function to load .env file if it exists
function loadEnv($dir) {
    $filePath = rtrim($dir, '/\\') . '/.env';
    if (!file_exists($filePath)) {
        return;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments
        if (strpos($line, '#') === 0 || empty($line)) {
            continue;
        }
        
        // Split name and value
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load environment variables from the project root
loadEnv(dirname(__DIR__));

// Set database credentials with .env values or fallback defaults
$host = getenv('DB_HOST') ?: "127.0.0.1";
$dbname = getenv('DB_NAME') ?: "vic_school";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";

try {

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        $options
    );

    // ==========================
    // SAAS SCHOOL DOMAIN DETECTION
    // ==========================
    $http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host_no_port = explode(':', $http_host)[0];
    $subdomain = explode('.', $host_no_port)[0];

    // Look up school
    $stmt_school = $pdo->prepare("SELECT * FROM schools WHERE subdomain = ?");
    $stmt_school->execute([$subdomain]);
    $current_school = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if (!$current_school) {
        // Fallback to first school
        $current_school = $pdo->query("SELECT * FROM schools LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    define('CURRENT_SCHOOL_ID', $current_school ? (int)$current_school['id'] : 1);
    define('CURRENT_SCHOOL_NAME', $current_school ? $current_school['school_name'] : 'VIC School');

} catch(PDOException $e){
    error_log("Database Connection Failed: " . $e->getMessage());
    die("A secure database connection could not be established. Please try again later.");
}
?>
