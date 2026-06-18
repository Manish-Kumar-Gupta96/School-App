<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

if(!isset($_GET['id'])){
    header("Location:index.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================
GET ADMISSION RECORD
========================== */
$stmt = $pdo->prepare("
    SELECT *
    FROM admissions
    WHERE id = ?
");
$stmt->execute([$id]);
$admission = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$admission){
    header("Location:index.php");
    exit();
}

/* ==========================
CHECK ALREADY APPROVED
========================== */
if($admission['status'] == 'Approved'){
    header("Location:index.php");
    exit();
}

try {
    $pdo->beginTransaction();

    /* ==========================
    GENERATE ADMISSION NUMBER
    ========================== */
    $admission_no = '';
    while (true) {
        $candidate_no = "VIC" . date('Y') . rand(1000, 9999);
        $check = $pdo->prepare("SELECT COUNT(*) FROM students WHERE admission_no = ?");
        $check->execute([$candidate_no]);
        if ($check->fetchColumn() == 0) {
            $admission_no = $candidate_no;
            break;
        }
    }

    /* ==========================
    HANDLE PHOTO COPY
    ========================== */
    $photo = $admission['photo'];
    if (!empty($photo)) {
        $source_photo = "../../uploads/admissions/" . $photo;
        $dest_photo = "../../uploads/students/" . $photo;
        if (file_exists($source_photo)) {
            copy($source_photo, $dest_photo);
        }
    }

    /* ==========================
    INSERT INTO STUDENTS
    ========================== */
    $student = $pdo->prepare("
        INSERT INTO students(
            admission_no,
            roll_no,
            first_name,
            last_name,
            gender,
            dob,
            class,
            section,
            father_name,
            mother_name,
            phone,
            email,
            address,
            photo,
            school_id
        )
        VALUES(
            ?, '', ?, '', ?, ?, ?, 'A', ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $student->execute([
        $admission_no,
        $admission['student_name'],
        $admission['gender'],
        $admission['dob'],
        $admission['class_applied'],
        $admission['father_name'],
        $admission['mother_name'],
        $admission['phone'],
        $admission['email'],
        $admission['address'],
        $photo,
        CURRENT_SCHOOL_ID
    ]);

    $student_id = $pdo->lastInsertId();

    /* ==========================
    INSERT INTO PARENTS (SYNC)
    ========================== */
    $parent_name = !empty($admission['father_name']) ? $admission['father_name'] : 'Parent of ' . $admission['student_name'];
    $parentStmt = $pdo->prepare("
        INSERT INTO parents (parent_name, father_name, mother_name, mobile, email, address, school_id, role_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, 4)
    ");
    $parentStmt->execute([
        $parent_name,
        $admission['father_name'],
        $admission['mother_name'],
        $admission['phone'],
        $admission['email'],
        $admission['address'],
        CURRENT_SCHOOL_ID
    ]);

    $parent_id = $pdo->lastInsertId();

    /* ==========================
    LINK STUDENT TO PARENT
    ========================== */
    $linkStmt = $pdo->prepare("
        INSERT INTO parent_students (parent_id, student_id)
        VALUES (?, ?)
    ");
    $linkStmt->execute([
        $parent_id,
        $student_id
    ]);

    /* ==========================
    UPDATE ADMISSION STATUS
    ========================== */
    $update = $pdo->prepare("
        UPDATE admissions
        SET status='Approved'
        WHERE id=?
    ");
    $update->execute([$id]);

    require_once('../includes/audit-helper.php');
    addAuditLog(
        $pdo,
        $_SESSION['user_id'],
        $_SESSION['name'] ?? 'Admin',
        $_SESSION['role'] ?? 'Admin',
        'Admission Approved & Student Created: ' . $admission['student_name'] . ' (Admission No: ' . $admission_no . ')',
        'Admissions',
        $student_id
    );

    $pdo->commit();
    header("Location:index.php?approved=1");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("An error occurred during approval: " . $e->getMessage());
}
?>
