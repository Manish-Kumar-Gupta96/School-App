<?php
// modules/certificates/templates/tc.php
$student_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$class_name = htmlspecialchars($student['class_name'] . ' ' . $student['section_name']);
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 30px; }
        .cert-container { border: 5px double #000; padding: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 28px; text-decoration: underline; }
        .header p { margin: 5px 0; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 18px; }
        td { padding: 10px; border-bottom: 1px dotted #ccc; }
        .footer { margin-top: 60px; text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="cert-container">
        <div class="header">
            <h1>TRANSFER CERTIFICATE</h1>
            <p>Affiliated to Central Board of Secondary Education</p>
        </div>
        
        <table>
            <tr>
                <td width="40%">1. Name of Pupil</td>
                <td><strong><?= $student_name ?></strong></td>
            </tr>
            <tr>
                <td>2. Admission No</td>
                <td><strong><?= htmlspecialchars($student['admission_no'] ?? 'N/A') ?></strong></td>
            </tr>
            <tr>
                <td>3. Date of Birth</td>
                <td><strong><?= htmlspecialchars($student['date_of_birth'] ?? 'N/A') ?></strong></td>
            </tr>
            <tr>
                <td>4. Class in which studying</td>
                <td><strong><?= $class_name ?></strong></td>
            </tr>
            <tr>
                <td>5. Whether paid all school dues</td>
                <td><strong>YES</strong></td>
            </tr>
            <tr>
                <td>6. Date of Application for Certificate</td>
                <td><strong><?= date('d-m-Y') ?></strong></td>
            </tr>
            <tr>
                <td>7. Reason for leaving</td>
                <td><strong>Parent Request</strong></td>
            </tr>
        </table>
        
        <div class="footer">
            <br><br>
            _______________________<br>
            Principal Signature
        </div>
    </div>
</body>
</html>
