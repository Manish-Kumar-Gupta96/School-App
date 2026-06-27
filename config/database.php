<?php
// Secure PDO centralized config
define('DB_HOST', 'localhost');
define('DB_NAME', 'vic_school');
define('DB_USER', 'root');
define('DB_PASS', '');

// Local fake SMTP configuration for XAMPP testing (MailHog standard configuration)
define('SMTP_HOST', '127.0.0.1');
define('SMTP_PORT', 1025); // MailHog SMTP listening port
define('SMTP_USER', '');
define('SMTP_PASS', '');

function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

// Global settings
if (!defined('CURRENT_SCHOOL_ID')) {
    define('CURRENT_SCHOOL_ID', 1);
}

class Database {
    private $host = "localhost";
    private $db_name = "vic_school";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Sourced from standard PDO parameters matching database.php attributes
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Return associative array by default
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            error_log("Connection Error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Global PDO initialization to prevent undefined variable errors across legacy files
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
