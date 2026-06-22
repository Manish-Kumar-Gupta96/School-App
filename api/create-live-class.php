<?php
require_once '../config/database.php';
require_once '../helpers/security.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

$teacherId = $_SESSION['teacher_id'];

$classId   = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
$sectionId = isset($_POST['section_id']) ? (int)$_POST['section_id'] : 0;
$subjectId = isset($_POST['subject_id']) ? (int)$_POST['subject_id'] : 0;

if (!canTeacherCreateClass(
    $pdo,
    $teacherId,
    $classId,
    $sectionId,
    $subjectId
)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$provider = isset($_POST['provider']) ? strtolower(trim($_POST['provider'])) : 'zoom';
$meeting_link = isset($_POST['meeting_link']) ? trim($_POST['meeting_link']) : '';
$start_time = isset($_POST['start_time']) ? trim($_POST['start_time']) : '';
$end_time = isset($_POST['end_time']) ? trim($_POST['end_time']) : '';

// 1. Resolve Class Name, Section Name, and Subject Name for backwards compatibility
$stmt_c = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
$stmt_c->execute([$classId]);
$c_name = $stmt_c->fetchColumn() ?: '';

$stmt_s = $pdo->prepare("SELECT section_name FROM sections WHERE id = ?");
$stmt_s->execute([$sectionId]);
$s_name = $stmt_s->fetchColumn() ?: '';

$stmt_sub = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?");
$stmt_sub->execute([$subjectId]);
$sub_name = $stmt_sub->fetchColumn() ?: '';

$class_name = ($c_name !== '' && $s_name !== '') ? $c_name . "-" . $s_name : "";
$subject = $sub_name;

// 2. Set provider mapping
$platform = strtoupper($provider);
if ($platform === 'GOOGLE_MEET') {
    $platform = 'GOOGLE_MEET';
}

$meeting_id = '';
if ($provider === 'zoom') {
    if (preg_match('/\/j\/([0-9]+)/', $meeting_link, $m)) {
        $meeting_id = $m[1];
    }
} elseif ($provider === 'google_meet') {
    if (preg_match('/meet\.google\.com\/([a-z0-9\-]+)/', $meeting_link, $m)) {
        $meeting_id = $m[1];
    }
}

// 3. Insert class details
try {
    $stmt = $pdo->prepare("
        INSERT INTO online_classes (
            teacher_id,
            class_id,
            section_id,
            subject_id,
            title,
            meeting_provider,
            meeting_link,
            start_time,
            end_time,
            status,
            class_name,
            subject,
            platform,
            meeting_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', ?, ?, ?, ?)
    ");

    $stmt->execute([
        $teacherId,
        $classId,
        $sectionId,
        $subjectId,
        $title,
        $provider,
        $meeting_link,
        $start_time,
        $end_time,
        $class_name,
        $subject,
        $platform,
        $meeting_id
    ]);

    $new_class_id = $pdo->lastInsertId();

    // 4. Trigger live class scheduled notifications (which queues alerts in notification_queue)
    require_once('../helpers/notification_helper.php');
    notifyLiveClassScheduled($pdo, $new_class_id);

    echo json_encode([
        'success' => true,
        'class_id' => $new_class_id
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
