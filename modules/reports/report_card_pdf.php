<?php
// Strict error management inside file exports to avoid corrupted byte-stream output
ob_start();
require_once __DIR__ . '/../../fpdf/fpdf.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/SessionManager.php';

SessionManager::startSecureSession();

// Verify access level parameter matches standard users scope
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized session access request block.");
}

$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT);
if (!$studentId) {
    die("Invalid request parameter metric context mapping.");
}

try {
    $db = getDBConnection();
    
    // Fetch target Student Details securely
    $studentQuery = "SELECT name, roll_no, class_name FROM students WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($studentQuery);
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        die("Target student metrics context records missing.");
    }

    // Fetch Academic Grades dataset dynamically
    $marksQuery = "SELECT subject_name, marks_obtained, max_marks FROM exam_marks WHERE student_id = :id";
    $mStmt = $db->prepare($marksQuery);
    $mStmt->execute([':id' => $studentId]);
    $records = $mStmt->fetchAll();

    // Instantiate Enterprise PDF Canvas Layout Properties
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetMargins(15, 15, 15);
    
    // Draw Clean Layout Borders Group matching local asset configurations
    $pdf->Rect(5, 5, 200, 287, 'D');

    // 1. Digital Branding Block Header
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'VIC ACADEMY SENIOR SECONDARY SCHOOL', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 5, 'Affiliated to Central Board of Education | Web Portal Interface Log', 0, 1, 'C');
    $pdf->Ln(10);

    // 2. Structured Metadata Grid Integration
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, 'Student Name:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(60, 7, strtoupper($student['name']), 0, 0);
    
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, 'Roll Number:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, $student['roll_no'], 0, 1);

    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(40, 7, 'Class Designation:', 0, 0);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Cell(0, 7, $student['class_name'], 0, 1);
    $pdf->Ln(8);

    // 3. Tabular Report Metrics Compiler Block
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(44, 62, 80); // Deep Blue Hex Accent Matches Bootstrap Palette
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(90, 8, ' SUBJECT ACADEMIC TOPIC', 1, 0, 'L', true);
    $pdf->Cell(45, 8, 'MARKS OBTAINED', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'MAXIMUM SCORING', 1, 1, 'C', true);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', '', 10);

    foreach ($records as $row) {
        $pdf->Cell(90, 8, ' ' . htmlspecialchars($row['subject_name']), 1, 0, 'L');
        $pdf->Cell(45, 8, $row['marks_obtained'], 1, 0, 'C');
        $pdf->Cell(45, 8, $row['max_marks'], 1, 1, 'C');
    }

    // Clear memory buffers to prevent byte stream corruptions
    ob_end_clean();
    $pdf->Output('I', 'Report_Card_' . $student['roll_no'] . '.pdf');

} catch (Exception $e) {
    ob_end_clean();
    die("PDF Compilation Failure Context Interrupted: " . $e->getMessage());
}
?>
