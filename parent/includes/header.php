<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Ensure parent is logged in
// Note: Parent logs in through root login.php as parent, setting $_SESSION['role'] = 'parent' and $_SESSION['user_id'] = parent's id.
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent'){
    header("Location: ../login.php");
    exit;
}

if (!isset($root_path)) {
    $root_path = "../";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : "Parent Portal | VIC School" ?></title>
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
            background: #2e1065; /* Deep Purple theme for parents */
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
            color: #d8b4fe;
            margin-bottom: 24px;
            padding-left: 8px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            color: #c084fc;
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
            background: #7c3aed;
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
    <h4>👨‍👩‍👧 VIC PARENT</h4>
    <hr class="text-secondary mb-4">
    
    <a href="dashboard.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i> <span>Dashboard</span>
    </a>
    <a href="attendance.php" class="<?= (isset($active_menu) && $active_menu == 'attendance') ? 'active' : '' ?>">
        <i class="fa fa-calendar-check"></i> <span>Child Attendance</span>
    </a>
    <a href="marks.php" class="<?= (isset($active_menu) && $active_menu == 'marks') ? 'active' : '' ?>">
        <i class="fa fa-chart-line"></i> <span>Marks & Report</span>
    </a>
    <a href="homework.php" class="<?= (isset($active_menu) && $active_menu == 'homework') ? 'active' : '' ?>">
        <i class="fa fa-tasks"></i> <span>Homework / Tasks</span>
    </a>
    <a href="fees.php" class="<?= (isset($active_menu) && $active_menu == 'fees') ? 'active' : '' ?>">
        <i class="fa fa-receipt"></i> <span>Fee Status</span>
    </a>
    <a href="message.php" class="<?= (isset($active_menu) && $active_menu == 'message') ? 'active' : '' ?>">
        <i class="fa fa-comments"></i> <span>Message Teacher</span>
    </a>
    <a href="notifications.php" class="<?= (isset($active_menu) && $active_menu == 'notifications') ? 'active' : '' ?>">
        <i class="fa fa-bell"></i> <span>Family Alerts</span>
    </a>
    <a href="tickets.php" class="<?= (isset($active_menu) && $active_menu == 'helpdesk') ? 'active' : '' ?>">
        <i class="fa fa-ticket-alt"></i> <span>Support Tickets</span>
    </a>
    <a href="../logout.php" class="text-danger mt-4">
        <i class="fa fa-sign-out-alt text-danger"></i> <span>Logout</span>
    </a>
</div>

<div class="main">
