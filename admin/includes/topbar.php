<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$username = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

if (!isset($root_path)) {
    $root_path = "../";
}

if (!isset($pdo)) {
    require_once(__DIR__ . '/../../config/database.php');
}

if (!function_exists('getRelativeTime')) {
    function getRelativeTime($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$string) return 'just now';
        $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

$unread_count = 0;
$notifications = [];
if (isset($pdo)) {
    try {
        $stmt_count = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notification_recipients nr
            WHERE nr.user_type = 'ADMIN' AND nr.status = 'PENDING'
        ");
        $stmt_count->execute();
        $unread_count = (int)$stmt_count->fetchColumn();

        $stmt_list = $pdo->prepare("
            SELECT n.id, n.title, n.message, n.created_at, nr.status
            FROM notification_recipients nr
            JOIN notifications n ON nr.notification_id = n.id
            WHERE nr.user_type = 'ADMIN'
            ORDER BY n.id DESC
            LIMIT 5
        ");
        $stmt_list->execute();
        $notifications = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fail silently
    }
}
?>
<style>
    .text-truncate-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
    }
    .notification-list .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    .notification-list .dropdown-item {
        transition: background-color 0.2s;
    }
    .notification-badge {
        font-size: 0.58rem;
        padding: 0.25em 0.45em;
        top: -2px !important;
        right: -8px !important;
    }
</style>
<div class="topbar">
    <h4><?= isset($page_header) ? htmlspecialchars($page_header) : 'Dashboard' ?></h4>
    <div class="topbar-icons">
        <span class="me-3 text-muted"><i class="fa fa-calendar me-1"></i> <?= date('d M Y') ?></span>
        
        <!-- Notifications Dropdown -->
        <div class="dropdown me-3">
            <a href="#" class="text-muted position-relative d-inline-block" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                <i class="fa fa-bell fs-5"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute translate-middle badge rounded-pill bg-danger notification-badge">
                        <?= $unread_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0" aria-labelledby="notificationDropdown" style="width: 320px; border-radius: 12px; font-size: 0.9rem; margin-top: 10px;">
                <li class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;">Notifications</span>
                    <?php if ($unread_count > 0): ?>
                        <a href="<?= $root_path ?>admin/notifications/mark-read.php" class="text-decoration-none small text-primary fw-semibold">Mark all read</a>
                    <?php endif; ?>
                </li>
                <div class="notification-list" style="max-height: 280px; overflow-y: auto;">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $n): ?>
                            <li>
                                <a class="dropdown-item p-3 border-bottom d-flex align-items-start gap-2" href="<?= $root_path ?>admin/notifications/view.php?id=<?= $n['id'] ?>" style="white-space: normal;">
                                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center mt-1" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <i class="fa <?= $n['status'] === 'PENDING' ? 'fa-bell text-danger' : 'fa-circle-info text-primary' ?>" style="font-size: 0.85rem;"></i>
                                    </div>
                                    <div style="flex-grow: 1;">
                                        <div class="fw-semibold text-dark" style="line-height: 1.2;"><?= htmlspecialchars($n['title']) ?></div>
                                        <div class="text-secondary small text-truncate-2 mt-1" style="line-height: 1.3; font-size: 0.8rem;"><?= htmlspecialchars($n['message']) ?></div>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;"><i class="fa fa-clock me-1" style="font-size: 0.75rem;"></i><?= getRelativeTime($n['created_at']) ?></small>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="p-4 text-center text-muted">
                            <i class="fa fa-bell-slash fs-3 mb-2 d-block text-muted"></i>
                            No new notifications
                        </li>
                    <?php endif; ?>
                </div>
                <li class="p-2 text-center">
                    <a href="<?= $root_path ?>admin/notifications/history.php" class="text-decoration-none small text-primary fw-semibold d-block py-1">View All History</a>
                </li>
            </ul>
        </div>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="<?= $root_path ?>assets/images/default-user.png" alt="Admin Photo" class="rounded-circle border border-primary-subtle" style="width: 38px; height: 38px; object-fit: cover; margin-right: 8px;">
                <div class="d-none d-md-block text-start">
                    <span class="fw-semibold d-block" style="line-height: 1.2;"><?= htmlspecialchars($username) ?></span>
                    <small class="text-muted" style="font-size: 0.72rem;"><?= $_SESSION['role'] === 'admin' ? 'Super Admin' : 'Staff' ?></small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-0" aria-labelledby="userDropdown" style="width: 240px; border-radius: 12px; font-size: 0.9rem; margin-top: 10px;">
                <li class="p-3 border-bottom bg-light" style="border-radius: 12px 12px 0 0;">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($username) ?></div>
                    <small class="text-muted"><?= htmlspecialchars(isset($email) && !empty($email) ? $email : 'admin@vicschool.edu.in') ?></small>
                </li>
                <li>
                    <a class="dropdown-item py-2.5 px-3 d-flex align-items-center gap-2" href="<?= $root_path ?>admin/profile.php">
                        <i class="fa fa-user-gear text-primary" style="width: 20px;"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2.5 px-3 d-flex align-items-center gap-2" href="<?= $root_path ?>admin/ai-dashboard.php">
                        <i class="fa fa-robot text-success" style="width: 20px;"></i>
                        <span>AI Dashboard</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2.5 px-3 d-flex align-items-center gap-2" href="<?= $root_path ?>admin/seo.php">
                        <i class="fa fa-magnifying-glass-chart text-info" style="width: 20px;"></i>
                        <span>SEO Settings</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2.5 px-3 d-flex align-items-center gap-2" href="<?= $root_path ?>admin/roles.php">
                        <i class="fa fa-shield-halved text-warning" style="width: 20px;"></i>
                        <span>Roles & Security</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2.5 px-3 d-flex align-items-center gap-2 text-danger border-top" href="<?= $root_path ?>logout.php" style="border-radius: 0 0 12px 12px;">
                        <i class="fa fa-sign-out-alt" style="width: 20px;"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

