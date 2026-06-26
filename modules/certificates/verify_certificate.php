<?php
// modules/certificates/verify_certificate.php
require_once('../../config/database.php');

$code = $_GET['code'] ?? '';

if (empty($code)) {
    die("<h3>Invalid Verification Code</h3>");
}

try {
    $stmt = $pdo->prepare("
        SELECT gc.*, s.first_name, s.last_name, c.class_name 
        FROM generated_certificates gc
        JOIN students s ON gc.student_id = s.id
        LEFT JOIN classes c ON s.class_id = c.id
        WHERE gc.verification_code = ?
    ");
    $stmt->execute([$code]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($certificate) {
        echo "<div style='font-family: Arial, sans-serif; text-align: center; padding: 50px;'>";
        echo "<h1 style='color: green;'>&#10004; Certificate Valid</h1>";
        echo "<p>This certificate is authentic and verified by the School ERP System.</p>";
        echo "<div style='display: inline-block; text-align: left; background: #f9f9f9; padding: 20px; border-radius: 10px; border: 1px solid #ddd;'>";
        echo "<strong>Student Name:</strong> " . htmlspecialchars($certificate['first_name'] . ' ' . $certificate['last_name']) . "<br><br>";
        echo "<strong>Class:</strong> " . htmlspecialchars($certificate['class_name']) . "<br><br>";
        echo "<strong>Certificate Type:</strong> " . strtoupper(htmlspecialchars($certificate['certificate_type'])) . "<br><br>";
        echo "<strong>Certificate No:</strong> " . htmlspecialchars($certificate['certificate_no']) . "<br><br>";
        echo "<strong>Issue Date:</strong> " . date('d M Y, h:i A', strtotime($certificate['generated_at']));
        echo "</div>";
        echo "</div>";
    } else {
        echo "<div style='font-family: Arial, sans-serif; text-align: center; padding: 50px;'>";
        echo "<h1 style='color: red;'>&#10008; Certificate Invalid</h1>";
        echo "<p>No matching record found for this verification code. This document may be forged.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "System Error: " . $e->getMessage();
}
