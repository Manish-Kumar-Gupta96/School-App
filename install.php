<?php
header('Content-Type: text/html; charset=utf-8');
echo "<h2>School App - XAMPP Local Installation Automator</h2>";

// 1. Check Mandatory XAMPP PHP Extensions
$requiredExtensions = ['curl', 'openssl', 'mbstring', 'pdo', 'pdo_mysql'];
$missingExts = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExts[] = $ext;
    }
}

if (!empty($missingExts)) {
    die("<p style='color:red;'>CRITICAL ERROR: Please enable the following extensions in your php.ini: " . implode(', ', $missingExts) . "</p>");
}
echo "<p style='color:green;'>✔ All required PHP extensions are active.</p>";

// 2. Auto-generate .env from .env.example
$envFile = __DIR__ . '/.env';
$envExample = __DIR__ . '/.env.example';

if (!file_exists($envFile)) {
    if (file_exists($envExample)) {
        copy($envExample, $envFile);
        echo "<p style='color:green;'>✔ .env file generated successfully from example.</p>";
    } else {
        die("<p style='color:red;'>ERROR: .env.example not found in root.</p>");
    }
} else {
    echo "<p style='color:orange;'>ℹ .env file already exists. Skipping cloning.</p>";
}

// 3. Import vic_school.sql Database Automatically
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = ''; // Default XAMPP Password
$dbName = 'vic_school';

try {
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$dbName`;");
    echo "<p style='color:green;'>✔ Database '$dbName' connected/created successfully.</p>";
    
    $sqlFile = __DIR__ . '/database/vic_school.sql';
    if (file_exists($sqlFile)) {
        $sqlContent = file_get_contents($sqlFile);
        // Execute the massive schema dump
        $pdo->exec($sqlContent);
        echo "<p style='color:green;'>✔ Database Structure & Seed Data imported successfully!</p>";
    } else {
        echo "<p style='color:orange;'>ℹ Warning: database/vic_school.sql file not found. Schema import skipped.</p>";
    }
} catch (PDOException $e) {
    die("<p style='color:red;'>Database Connection Failed: " . $e->getMessage() . "</p>");
}

echo "<h3><p style='color:blue;'>Installation finished! Please delete 'install.php' before coding.</p></h3>";
?>
