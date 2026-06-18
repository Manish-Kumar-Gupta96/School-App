<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['student_id'])){
    header("Location: login.php");
    exit;
}
// Default root path for student pages
if (!isset($root_path)) {
    $root_path = "../";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : "Student Dashboard | VIC School" ?></title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Style mappings matching dashboard.css -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/dashboard.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6fb;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #111827; /* Dark theme */
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            padding: 24px 16px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .sidebar h4 {
            font-weight: 800;
            color: #0d6efd;
            margin-bottom: 24px;
            padding-left: 8px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #9ca3af;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }
        .sidebar a.active {
            background: #0d6efd;
            color: #fff;
        }
        .sidebar a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .main {
            margin-left: 240px;
            padding: 40px;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding: 24px 8px;
            }
            .sidebar h4, .sidebar a span {
                display: none;
            }
            .sidebar a i {
                margin-right: 0;
            }
            .main {
                margin-left: 70px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h4>🎓 VIC STUDENT</h4>
    <hr class="text-secondary mb-4">
    
    <a href="dashboard.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i> <span>Dashboard</span>
    </a>
    <a href="attendance.php" class="<?= (isset($active_menu) && $active_menu == 'attendance') ? 'active' : '' ?>">
        <i class="fa fa-calendar-check"></i> <span>Attendance</span>
    </a>
    <a href="results.php" class="<?= (isset($active_menu) && $active_menu == 'results') ? 'active' : '' ?>">
        <i class="fa fa-file-signature"></i> <span>Report Card</span>
    </a>
    <a href="study-material.php" class="<?= (isset($active_menu) && $active_menu == 'material') ? 'active' : '' ?>">
        <i class="fa fa-book"></i> <span>Study Material</span>
    </a>
    <a href="timetable.php" class="<?= (isset($active_menu) && $active_menu == 'timetable') ? 'active' : '' ?>">
        <i class="fa fa-calendar-alt"></i> <span>Class Timetable</span>
    </a>
    <a href="syllabus.php" class="<?= (isset($active_menu) && $active_menu == 'syllabus') ? 'active' : '' ?>">
        <i class="fa fa-graduation-cap"></i> <span>My Syllabus</span>
    </a>
    <a href="assignments.php" class="<?= (isset($active_menu) && $active_menu == 'assignments') ? 'active' : '' ?>">
        <i class="fa fa-pen-nib"></i> <span>Assignments</span>
    </a>
    <a href="downloads.php" class="<?= (isset($active_menu) && $active_menu == 'downloads') ? 'active' : '' ?>">
        <i class="fa fa-download"></i> <span>Download Center</span>
    </a>
    <a href="online-classes.php" class="<?= (isset($active_menu) && $active_menu == 'online-classes') ? 'active' : '' ?>">
        <i class="fa fa-video"></i> <span>Online Classes</span>
    </a>
    <a href="notifications.php" class="<?= (isset($active_menu) && $active_menu == 'notifications') ? 'active' : '' ?>">
        <i class="fa fa-bell"></i> <span>Notifications</span>
    </a>
    <a href="tickets.php" class="<?= (isset($active_menu) && $active_menu == 'helpdesk') ? 'active' : '' ?>">
        <i class="fa fa-ticket-alt"></i> <span>Support Tickets</span>
    </a>
    <a href="profile.php" class="<?= (isset($active_menu) && $active_menu == 'profile') ? 'active' : '' ?>">
        <i class="fa fa-user-circle"></i> <span>My Profile</span>
    </a>
    <a href="logout.php" class="text-danger mt-4">
        <i class="fa fa-sign-out-alt text-danger"></i> <span>Logout</span>
    </a>
</div>

<div class="main">
