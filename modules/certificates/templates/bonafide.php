<?php
// modules/certificates/templates/bonafide.php
$student_name = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$class_name = htmlspecialchars($student['class_name'] . ' ' . $student['section_name']);
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Times New Roman', serif; margin: 40px; }
        .cert-container { border: 10px solid #2c3e50; padding: 50px; text-align: center; }
        .header { font-size: 36px; font-weight: bold; color: #2c3e50; margin-bottom: 40px; text-transform: uppercase; }
        .content { font-size: 20px; line-height: 2; margin-bottom: 50px; }
        .footer { display: table; width: 100%; margin-top: 80px; }
        .date { display: table-cell; text-align: left; font-weight: bold; }
        .signature { display: table-cell; text-align: right; font-weight: bold; }
        .line { border-bottom: 1px solid #000; padding: 0 10px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="cert-container">
        <div class="header">Bonafide Certificate</div>
        
        <div class="content">
            This is to certify that <strong><?= $student_name ?></strong><br>
            is a bonafide student of this institution studying in<br>
            Class <strong><?= $class_name ?></strong><br>
            for the academic session 2026-2027.
            <br><br>
            To the best of our knowledge and belief, the student bears a good moral character.
        </div>
        
        <div class="footer">
            <div class="date">Date: <?= date('d-m-Y') ?></div>
            <div class="signature">
                <br><br>
                _______________________<br>
                Principal Signature
            </div>
        </div>
    </div>
</body>
</html>
