<?php

$host = "localhost";
$dbname = "vic_school";
$username = "root";
$password = "";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // ==========================
    // SAAS SCHOOL DOMAIN DETECTION
    // ==========================
    $http_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $domain = explode(':', $http_host)[0];

    // Look up school
    $stmt_school = $pdo->prepare("SELECT * FROM schools WHERE domain = ?");
    $stmt_school->execute([$domain]);
    $current_school = $stmt_school->fetch(PDO::FETCH_ASSOC);

    if (!$current_school) {
        // Fallback to first school
        $current_school = $pdo->query("SELECT * FROM schools LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    }

    define('CURRENT_SCHOOL_ID', $current_school ? (int)$current_school['id'] : 1);
    define('CURRENT_SCHOOL_NAME', $current_school ? $current_school['school_name'] : 'VIC School');

} catch(PDOException $e){
    die("Database Connection Failed : " . $e->getMessage());
}
?>
