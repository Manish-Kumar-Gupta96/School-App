<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . '/../../includes/auth.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die("Access Denied: Admin authorization required. (Current Role: " . ($_SESSION['role'] ?? 'None') . ")");
}

// Ensure page has root_path defined
if (!isset($root_path)) {
    $root_path = "../";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : "Admin Dashboard | VIC ERP" ?></title>
    <!-- Bootstrap (Local) -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/libs/bootstrap.min.css">
    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/libs/fa/all.min.css">
    <!-- Google Fonts Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $root_path ?>assets/css/dashboard.css">
</head>
<body>

<div class="sidebar">
    <h3>VIC ERP</h3>
    <a href="<?= $root_path ?>admin/dashboard.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : '' ?>">
        <i class="fa fa-gauge"></i> <span>Dashboard</span>
    </a>
    <a href="<?= $root_path ?>admin/students/index.php" class="<?= (isset($active_menu) && $active_menu == 'students') ? 'active' : '' ?>">
        <i class="fa fa-user-graduate"></i> <span>Students</span>
    </a>
    <a href="<?= $root_path ?>admin/teachers/index.php" class="<?= (isset($active_menu) && $active_menu == 'teachers') ? 'active' : '' ?>">
        <i class="fa fa-chalkboard-teacher"></i> <span>Teachers</span>
    </a>
    <a href="<?= $root_path ?>admin/teachers/allocations.php" class="<?= (isset($active_menu) && $active_menu == 'allocations') ? 'active' : '' ?>">
        <i class="fa fa-map-signs"></i> <span>Class Allocations</span>
    </a>
    <a href="<?= $root_path ?>admin/admissions/index.php" class="<?= (isset($active_menu) && $active_menu == 'admissions') ? 'active' : '' ?>">
        <i class="fa fa-user-plus"></i> <span>Admissions</span>
    </a>
    <a href="<?= $root_path ?>admin/crm/leads.php" class="<?= (isset($active_menu) && $active_menu == 'crm') ? 'active' : '' ?>">
        <i class="fa fa-address-book"></i> <span>CRM Leads</span>
    </a>
    <a href="<?= $root_path ?>admin/attendance/attendance-list.php" class="<?= (isset($active_menu) && $active_menu == 'attendance') ? 'active' : '' ?>">
        <i class="fa fa-calendar-check"></i> <span>Attendance</span>
    </a>
    <a href="<?= $root_path ?>admin/library/books.php" class="<?= (isset($active_menu) && $active_menu == 'library') ? 'active' : '' ?>">
        <i class="fa fa-book"></i> <span>Library</span>
    </a>
    <a href="<?= $root_path ?>admin/transport/routes.php" class="<?= (isset($active_menu) && $active_menu == 'transport') ? 'active' : '' ?>">
        <i class="fa fa-bus"></i> <span>Transport</span>
    </a>
    <a href="<?= $root_path ?>admin/hostel/room-types.php" class="<?= (isset($active_menu) && $active_menu == 'hostel') ? 'active' : '' ?>">
        <i class="fa fa-hotel"></i> <span>Hostel</span>
    </a>
    <a href="<?= $root_path ?>admin/inventory/categories.php" class="<?= (isset($active_menu) && $active_menu == 'inventory') ? 'active' : '' ?>">
        <i class="fa fa-boxes-stacked"></i> <span>Inventory</span>
    </a>
    <a href="<?= $root_path ?>admin/hr/departments.php" class="<?= (isset($active_menu) && $active_menu == 'hr') ? 'active' : '' ?>">
        <i class="fa fa-users-cog"></i> <span>HR Management</span>
    </a>
    <a href="<?= $root_path ?>admin/parents/parent-list.php" class="<?= (isset($active_menu) && $active_menu == 'parents') ? 'active' : '' ?>">
        <i class="fa fa-user-friends"></i> <span>Parent Portal</span>
    </a>
    <a href="<?= $root_path ?>admin/gallery.php" class="<?= (isset($active_menu) && $active_menu == 'gallery') ? 'active' : '' ?>">
        <i class="fa fa-image"></i> <span>Gallery</span>
    </a>
    <a href="<?= $root_path ?>admin/notices/index.php" class="<?= (isset($active_menu) && $active_menu == 'notices') ? 'active' : '' ?>">
        <i class="fa fa-bullhorn"></i> <span>Notice Board</span>
    </a>
    <a href="<?= $root_path ?>admin/events/index.php" class="<?= (isset($active_menu) && $active_menu == 'events') ? 'active' : '' ?>">
        <i class="fa fa-calendar"></i> <span>Events</span>
    </a>
    <a href="<?= $root_path ?>admin/contact/messages.php" class="<?= (isset($active_menu) && $active_menu == 'contacts') ? 'active' : '' ?>">
        <i class="fa fa-envelope"></i> <span>Contacts</span>
    </a>
    <a href="<?= $root_path ?>admin/fees/fee-list.php" class="<?= (isset($active_menu) && $active_menu == 'fees') ? 'active' : '' ?>">
        <i class="fa fa-money-bill"></i> <span>Fees</span>
    </a>
    <a href="<?= $root_path ?>admin/exams/create-exam.php" class="<?= (isset($active_menu) && $active_menu == 'results') ? 'active' : '' ?>">
        <i class="fa fa-file-lines"></i> <span>Results</span>
    </a>
    <a href="<?= $root_path ?>admin/online-classes/class-list.php" class="<?= (isset($active_menu) && $active_menu == 'online-classes') ? 'active' : '' ?>">
        <i class="fa fa-video"></i> <span>Online Classes</span>
    </a>
    <a href="<?= $root_path ?>admin/ai-dashboard.php" class="<?= (isset($active_menu) && $active_menu == 'ai-dashboard') ? 'active' : '' ?>">
        <i class="fa fa-robot"></i> <span>AI Dashboard</span>
    </a>
    <a href="<?= $root_path ?>admin/seo.php" class="<?= (isset($active_menu) && $active_menu == 'seo') ? 'active' : '' ?>">
        <i class="fa fa-search"></i> <span>SEO Settings</span>
    </a>
    <a href="<?= $root_path ?>admin/blogs.php" class="<?= (isset($active_menu) && $active_menu == 'blogs') ? 'active' : '' ?>">
        <i class="fa fa-pen-nib"></i> <span>Blog Manager</span>
    </a>
    <a href="<?= $root_path ?>admin/accounts/ledgers.php" class="<?= (isset($active_menu) && $active_menu == 'accounts') ? 'active' : '' ?>">
        <i class="fa fa-wallet"></i> <span>Accounts Ledger</span>
    </a>
    <a href="<?= $root_path ?>admin/career/applications.php" class="<?= (isset($active_menu) && $active_menu == 'careers') ? 'active' : '' ?>">
        <i class="fa fa-briefcase"></i> <span>Job Vacancies</span>
    </a>
    <a href="<?= $root_path ?>admin/roles.php" class="<?= (isset($active_menu) && $active_menu == 'roles') ? 'active' : '' ?>">
        <i class="fa fa-user-shield"></i> <span>Roles & Security</span>
    </a>
    <a href="<?= $root_path ?>admin/audit/logs.php" class="<?= (isset($active_menu) && $active_menu == 'audit') ? 'active' : '' ?>">
        <i class="fa fa-history"></i> <span>Audit Trail</span>
    </a>
    <a href="<?= $root_path ?>admin/notifications/history.php" class="<?= (isset($active_menu) && $active_menu == 'notifications') ? 'active' : '' ?>">
        <i class="fa fa-bell"></i> <span>Notifications</span>
    </a>
    <a href="<?= $root_path ?>admin/helpdesk/tickets.php" class="<?= (isset($active_menu) && $active_menu == 'helpdesk') ? 'active' : '' ?>">
        <i class="fa fa-ticket-alt"></i> <span>Helpdesk</span>
    </a>
    <a href="<?= $root_path ?>logout.php">
        <i class="fa fa-sign-out-alt"></i> <span>Logout</span>
    </a>
</div>

<div class="main">
