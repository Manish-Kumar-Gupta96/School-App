<?php
function renderSidebar($currentRole) {
    $menu = [];
    
    // Dynamic menu population based on verified system states
    if ($currentRole === 'admin' || $currentRole === 'superadmin') {
        $menu = [
            'Dashboard' => 'admin/ai-dashboard',
            'Admissions' => 'admin/admissions/index',
            'Accounts & Fees' => 'admin/accounts/ledgers',
            'HR & Payroll' => 'admin/hr/payroll',
            'Inventory System' => 'admin/inventory/stock'
        ];
    } elseif ($currentRole === 'teacher') {
        $menu = [
            'Dashboard' => 'teacher/dashboard',
            'Mark Attendance' => 'teacher/attendance/mark',
            'Assignments' => 'teacher/assignments/index',
            'Syllabus & Material' => 'teacher/syllabus'
        ];
    } elseif ($currentRole === 'student') {
        $menu = [
            'Dashboard' => 'student/dashboard',
            'My Assignments' => 'student/assignments',
            'Exams & Results' => 'student/exams/list',
            'Fee Status' => 'student/fees'
        ];
    }

    echo '<ul class="nav flex-column sidebar-nav">';
    foreach ($menu as $title => $link) {
        // Enforcing case-sensitivity safety for standard routing redirects
        $cleanLink = strtolower($link); 
        echo '<li class="nav-item">';
        echo '  <a class="nav-item nav-link text-white" href="/school-app/' . $cleanLink . '">';
        echo '    <i class="fas fa-chevron-right me-2"></i> ' . htmlspecialchars($title);
        echo '  </a>';
        echo '</li>';
    }
    echo '</ul>';
}
