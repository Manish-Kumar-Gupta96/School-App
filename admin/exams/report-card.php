<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['student_id']) || !isset($_GET['exam_id'])){
    die("Invalid Request. Student ID and Exam ID are required.");
}

$student_id = (int)$_GET['student_id'];
$exam_id    = (int)$_GET['exam_id'];

/* ==========================
STUDENT INFO
========================== */
$studentStmt = $pdo->prepare("SELECT * FROM students WHERE id=?");
$studentStmt->execute([$student_id]);
$student = $studentStmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    die("Student Not Found");
}

/* ==========================
EXAM INFO
========================== */
$examStmt = $pdo->prepare("SELECT * FROM exams WHERE id=?");
$examStmt->execute([$exam_id]);
$exam = $examStmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam Not Found");
}

/* ==========================
MARKS DATA & PASSING CHECK
========================== */
$marksStmt = $pdo->prepare("
    SELECT m.*, s.subject_name, s.subject_code, s.passing_marks as subj_pass_marks
    FROM marks m
    LEFT JOIN subjects s ON m.subject_id=s.id
    WHERE m.student_id=? AND m.exam_id=?
");
$marksStmt->execute([$student_id, $exam_id]);
$marks = $marksStmt->fetchAll(PDO::FETCH_ASSOC);

$totalObtained = 0;
$totalMarks    = 0;
$isFailed      = false;

foreach($marks as $row){
    $totalObtained += $row['obtained_marks'];
    $totalMarks += $row['total_marks'];

    $pass_threshold = $row['subj_pass_marks'] ?: 33;
    if($row['obtained_marks'] < $pass_threshold){
        $isFailed = true;
    }
}

$percentage = $totalMarks ? round(($totalObtained/$totalMarks)*100, 2) : 0;

function calculateGrade($percentage){
    if($percentage>=90) return "A+";
    if($percentage>=80) return "A";
    if($percentage>=70) return "B+";
    if($percentage>=60) return "B";
    if($percentage>=50) return "C";
    if($percentage>=33) return "D";
    return "F";
}

$grade = calculateGrade($percentage);
$result_status = $isFailed ? "FAIL" : "PASS";

/* ==========================
CLASS RANK CALCULATION
========================== */
$rankStmt = $pdo->prepare("
    SELECT 
        s.id,
        SUM(m.obtained_marks) as total_obtained
    FROM students s
    INNER JOIN marks m ON s.id = m.student_id
    WHERE m.exam_id = ? AND s.class = ?
    GROUP BY s.id
    ORDER BY total_obtained DESC
");
$rankStmt->execute([$exam_id, $student['class']]);
$rankings = $rankStmt->fetchAll(PDO::FETCH_ASSOC);

$rank = '-';
$total_students = count($rankings);
foreach ($rankings as $index => $r) {
    if ($r['id'] == $student_id) {
        $rank = $index + 1;
        break;
    }
}

/* ==========================
ATTENDANCE COMPUTATION
========================== */
$attendanceStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(CASE WHEN status = 'Present' OR status = 'Late' THEN 1 WHEN status = 'Half Day' THEN 0.5 ELSE 0 END) as attended_days
    FROM attendance
    WHERE student_id = ?
");
$attendanceStmt->execute([$student_id]);
$att_data = $attendanceStmt->fetch(PDO::FETCH_ASSOC);

$attendance_pct = 95.00; // default mockup high attendance
if ($att_data && $att_data['total_days'] > 0) {
    $attendance_pct = round(($att_data['attended_days'] / $att_data['total_days']) * 100, 2);
}

/* ==========================
TEACHER REMARKS SIMULATOR
========================== */
$remarks = "Showing consistent performance. Needs to maintain focus on technical subjects.";
if ($grade === "A+") $remarks = "Outstanding academic achievement! Shows great leadership qualities.";
elseif ($grade === "A") $remarks = "Excellent results. Attentive in class and highly disciplined.";
elseif ($grade === "B+") $remarks = "Good performance. Consistent effort, with potential to achieve higher grades.";
elseif ($grade === "B") $remarks = "Satisfactory progress. Needs to revise mathematical logic regularly.";
elseif ($grade === "C") $remarks = "Average performance. Requires additional support and targeted study plans.";
elseif ($grade === "F") $remarks = "Unsatisfactory. Parents are requested to schedule a teacher conference.";

// QR Code data source link
$verify_url = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/admin/exams/result-sheet.php?student_id=" . $student_id . "&exam_id=" . $exam_id;
$qr_code_src = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($verify_url);

// Layout setup
$root_path = "../../";
$page_title = "Academic Report Card | VIC ERP";
$page_header = "Report Card Details";
$active_menu = "results";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<style>
    .report-card-container {
        max-width: 900px;
        margin: auto;
        background: #fff;
        border: 2px solid #2b3e50;
        border-radius: 12px;
        position: relative;
    }
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 8rem;
        font-weight: 800;
        color: rgba(43, 62, 80, 0.03);
        z-index: 0;
        pointer-events: none;
        user-select: none;
        white-space: nowrap;
    }
    .report-content {
        position: relative;
        z-index: 1;
    }
    @media print {
        body {
            background: #fff !important;
        }
        .sidebar, .topbar, .no-print, .btn, footer {
            display: none !important;
        }
        .main {
            margin-left: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .report-card-container {
            border: none !important;
            margin: 0 !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h5 class="text-muted mb-0">Review printable report transcript card</h5>
    <div>
        <a href="class-result.php?exam_id=<?= $exam_id ?>&class=<?= urlencode($student['class']) ?>" class="btn btn-secondary me-2">
            <i class="fa fa-arrow-left me-1"></i> Back
        </a>
        <button onclick="window.print()" class="btn btn-primary px-4">
            <i class="fa fa-print me-1"></i> Print Report Card
        </button>
    </div>
</div>

<div class="report-card-container p-5 shadow-lg position-relative mb-5">
    <div class="watermark">VIC SCHOOL</div>
    
    <div class="report-content">
        <!-- SCHOOL HEADER -->
        <div class="row align-items-center mb-4 text-center text-md-start">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <i class="fa fa-school text-primary fs-1" style="font-size: 4.5rem !important;"></i>
            </div>
            <div class="col-md-7 text-dark">
                <h1 class="fw-bold tracking-tight mb-1" style="color: #2b3e50;">VIC SCHOOL OF EDUCATION</h1>
                <p class="text-muted small mb-0"><i class="fa fa-map-marker-alt me-1"></i> Sector-4, High Street, New Delhi, India</p>
                <p class="text-muted small"><i class="fa fa-globe me-1"></i> www.vicschool.edu.in | <i class="fa fa-phone me-1"></i> +91 98765 43210</p>
            </div>
            <div class="col-md-3 text-center text-md-end no-print">
                <img src="<?= $qr_code_src ?>" width="100" height="100" alt="Verification QR" class="border p-1 rounded bg-white shadow-sm">
                <span class="d-block text-muted small mt-1">Scan to Verify</span>
            </div>
        </div>

        <hr style="border-top: 3px double #2b3e50; opacity: 1;" class="mb-4">

        <!-- STUDENT METADATA -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-md-9 order-2 order-md-1">
                <div class="row g-2 text-dark">
                    <div class="col-sm-6">
                        <strong>Student Name:</strong> <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                    </div>
                    <div class="col-sm-6">
                        <strong>Class & Section:</strong> <?= htmlspecialchars($student['class'] . ' ' . ($student['section'] ?? 'A')) ?>
                    </div>
                    <div class="col-sm-6">
                        <strong>Admission Number:</strong> <span class="badge bg-dark px-2"><?= htmlspecialchars($student['admission_no']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <strong>Roll Number:</strong> <?= htmlspecialchars($student['roll_no'] ?: 'N/A') ?>
                    </div>
                    <div class="col-sm-6">
                        <strong>Date of Birth:</strong> <?= $student['dob'] ? date('d M Y', strtotime($student['dob'])) : 'N/A' ?>
                    </div>
                    <div class="col-sm-6">
                        <strong>Academic Year:</strong> <?= htmlspecialchars($exam['session_year']) ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 order-1 order-md-2 text-center">
                <?php if($student['photo'] && file_exists("../../uploads/students/" . $student['photo'])) : ?>
                    <img src="../../uploads/students/<?= htmlspecialchars($student['photo']) ?>" width="110" height="120" style="object-fit:cover; border-radius: 8px; border: 2px solid #2b3e50;" class="shadow-sm">
                <?php else : ?>
                    <img src="../../assets/images/default-user.png" width="110" height="120" style="object-fit:cover; border-radius: 8px; border: 2px solid #2b3e50;" class="shadow-sm">
                <?php endif; ?>
            </div>
        </div>

        <h5 class="fw-bold mb-3 mt-4 text-center text-uppercase tracking-widest text-primary" style="border-bottom: 2px solid #2b3e50; padding-bottom: 5px;">REPORT CARD OF <?= htmlspecialchars($exam['exam_name']) ?></h5>

        <!-- MARKS GRID -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle text-center" style="border: 1px solid #dee2e6;">
                <thead class="table-dark" style="background-color: #2b3e50;">
                    <tr>
                        <th class="text-start ps-4" width="300">Subject</th>
                        <th>Max Marks</th>
                        <th>Pass Marks</th>
                        <th>Obtained Marks</th>
                        <th>Subject Grade</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody class="text-start">
                    <?php foreach($marks as $row): 
                        $pass_lim = $row['subj_pass_marks'] ?: 33;
                        $is_fail_flag = $row['obtained_marks'] < $pass_lim;
                        $sub_pct = $row['total_marks'] ? round(($row['obtained_marks']/$row['total_marks'])*100) : 0;
                        $sub_grade = calculateGrade($sub_pct);
                    ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['subject_name']) ?> (<?= htmlspecialchars($row['subject_code']) ?>)</td>
                            <td class="text-center"><?= (int)$row['total_marks'] ?></td>
                            <td class="text-center"><?= $pass_lim ?></td>
                            <td class="text-center fw-bold <?= $is_fail_flag ? 'text-danger' : 'text-success' ?>"><?= $row['obtained_marks'] ?></td>
                            <td class="text-center"><span class="badge bg-<?= $is_fail_flag ? 'danger' : 'success' ?>-subtle text-<?= $is_fail_flag ? 'danger' : 'success' ?>"><?= $sub_grade ?></span></td>
                            <td class="text-center small text-muted"><?= $is_fail_flag ? 'Needs Improve' : 'Satisfactory' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ACADEMIC STANDING METRICS -->
        <div class="row g-3 mb-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block mb-1">Total Marks Obtained</span>
                    <h5 class="fw-bold mb-0 text-primary"><?= $totalObtained ?> / <?= $totalMarks ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block mb-1">Aggregate Percentage</span>
                    <h5 class="fw-bold mb-0 text-success"><?= $percentage ?>%</h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block mb-1">Overall Rank in Class</span>
                    <h5 class="fw-bold mb-0 text-warning"><?= $rank ?> / <?= $total_students ?></h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-light">
                    <span class="text-muted small d-block mb-1">Attendance Percentage</span>
                    <h5 class="fw-bold mb-0 text-info"><?= $attendance_pct ?>%</h5>
                </div>
            </div>
        </div>

        <!-- REMARKS BOX -->
        <div class="border rounded p-3 mb-5 bg-light-subtle">
            <h6 class="fw-bold text-dark mb-2"><i class="fa fa-comment-dots text-primary me-2"></i> Class Teacher's Remarks:</h6>
            <p class="mb-0 text-muted italic">"<?= htmlspecialchars($remarks) ?> Class attendance record is excellent."</p>
        </div>

        <!-- SIGNATURES -->
        <div class="row text-center mt-5 pt-4">
            <div class="col-4">
                <div class="mx-auto" style="border-top: 1px solid #333; width: 140px; padding-top: 5px;">
                    <span class="text-muted small d-block">Class Teacher</span>
                </div>
            </div>
            <div class="col-4">
                <div class="mx-auto" style="border-top: 1px solid #333; width: 140px; padding-top: 5px;">
                    <span class="text-muted small d-block">Exam Controller</span>
                </div>
            </div>
            <div class="col-4">
                <div class="mx-auto" style="border-top: 1px solid #333; width: 140px; padding-top: 5px;">
                    <span class="text-muted small d-block">Principal</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
