<?php
// modules/certificates/generate_certificate.php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

// Disable errors for PDF generation if needed
error_reporting(E_ALL & ~E_DEPRECATED);

$student_id = (int)($_GET['student_id'] ?? 0);
$type = $_GET['type'] ?? 'bonafide';
$schoolId = defined('CURRENT_SCHOOL_ID') ? CURRENT_SCHOOL_ID : 1;

// Ensure directories exist
$pdfDir = __DIR__ . '/pdf/';
$qrDir = __DIR__ . '/qr/';
if(!is_dir($pdfDir)) mkdir($pdfDir, 0777, true);
if(!is_dir($qrDir)) mkdir($qrDir, 0777, true);

// Fetch Student Data
$stmt = $pdo->prepare("
    SELECT s.*, c.class_name, sec.section_name 
    FROM students s
    LEFT JOIN classes c ON s.class_id = c.id
    LEFT JOIN sections sec ON s.section_id = sec.id
    WHERE s.id = ? AND s.school_id = ?
");
$stmt->execute([$student_id, $schoolId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student not found.");
}

// Generate Verification Code
$verification_code = strtoupper(bin2hex(random_bytes(5))); // e.g. A1B2C3D4E5
$verify_url = "http://" . $_SERVER['HTTP_HOST'] . "/modules/certificates/verify_certificate.php?code=" . $verification_code;

// Determine Template
$template_file = __DIR__ . "/templates/{$type}.php";
if (!file_exists($template_file)) {
    die("Template not found: {$type}");
}

// Load Template Content
ob_start();
include $template_file;
$html = ob_get_clean();

$pdf_file_name = time() . "_{$type}_{$student_id}.pdf";

// Check if Composer libraries are loaded
if (class_exists('Dompdf\Dompdf') && class_exists('Endroid\QrCode\QrCode')) {
    
    // Generate QR (assuming Endroid QR Code v4+)
    $qr = new \Endroid\QrCode\QrCode($verify_url);
    $writer = new \Endroid\QrCode\Writer\PngWriter();
    $result = $writer->write($qr);
    $qrPath = $qrDir . "qr_{$verification_code}.png";
    $result->saveToFile($qrPath);
    
    // Append QR to HTML
    $qrDataUri = $result->getDataUri();
    $html .= "<div style='text-align:center; margin-top:30px;'><img src='{$qrDataUri}' width='100'><p>Scan to Verify</p><small>Code: {$verification_code}</small></div>";

    // Generate PDF
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $pdf_output = $dompdf->output();
    file_put_contents($pdfDir . $pdf_file_name, $pdf_output);

} else {
    // Fallback if composer packages are missing: Just save the raw HTML as a .html file
    // In a real environment, you MUST run `composer require dompdf/dompdf endroid/qr-code`
    $pdf_file_name = str_replace('.pdf', '.html', $pdf_file_name);
    file_put_contents($pdfDir . $pdf_file_name, $html);
}

// Save to Database
$stmt = $pdo->prepare("
    INSERT INTO generated_certificates 
    (school_id, student_id, certificate_type, certificate_no, verification_code, pdf_file, generated_by) 
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$cert_no = uniqid('CERT-');
$stmt->execute([
    $schoolId,
    $student_id,
    $type,
    $cert_no,
    $verification_code,
    $pdf_file_name,
    $_SESSION['user_id'] ?? 1
]);

// Output Response
echo "<h3>Certificate Generated Successfully!</h3>";
echo "<p>Certificate No: {$cert_no}</p>";
echo "<p>Verification Code: {$verification_code}</p>";
if (strpos($pdf_file_name, '.pdf') !== false) {
    echo "<a href='pdf/{$pdf_file_name}' target='_blank'>Download PDF</a>";
} else {
    echo "<a href='pdf/{$pdf_file_name}' target='_blank'>View HTML (PDF library not installed)</a>";
}
