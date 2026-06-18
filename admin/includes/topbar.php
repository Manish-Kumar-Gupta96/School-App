<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
?>
<div class="topbar">
    <h4><?= isset($page_header) ? htmlspecialchars($page_header) : 'Dashboard' ?></h4>
    <div class="topbar-icons">
        <span class="me-3 text-muted"><i class="fa fa-calendar me-1"></i> <?= date('d M Y') ?></span>
        <i class="fa fa-bell me-3"></i>
        <div class="d-flex align-items-center">
            <i class="fa fa-user-circle me-2 text-primary fs-4"></i>
            <span class="fw-semibold"><?= htmlspecialchars($username) ?></span>
        </div>
    </div>
</div>
