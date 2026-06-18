<?php
require_once('../../config/database.php');
$root_path = "../../";
$page_title = "Contact Messages - Inbox | Admin Control";
$active_menu = "contacts";
require_once('../includes/header.php');
require_once('../includes/topbar.php');

// Fetch contact messages
$data = $pdo->query("SELECT * FROM contact_messages ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Handle replying status toggling
if (isset($_GET['mark_replied'])) {
    $msg_id = (int)$_GET['mark_replied'];
    $stmt = $pdo->prepare("UPDATE contact_messages SET status='REPLIED' WHERE id=?");
    $stmt->execute([$msg_id]);
    header("Location: messages.php");
    exit;
}
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">📩 Contact Enquiries Inbox</h2>
            <p class="text-muted mb-0">Review visitor and parent questions or feedback sent through the website contact form.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-white border-0 py-3 ps-4">
            <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-envelope-open me-2 text-secondary"></i>Contact Inquiries</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light text-start">
                        <tr>
                            <th class="ps-4">Visitor Info</th>
                            <th>Inquiry Message</th>
                            <th>Status</th>
                            <th class="pe-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-start">
                        <?php if(count($data) > 0): ?>
                            <?php foreach($data as $m): ?>
                                <?php
                                $badge = 'danger';
                                if ($m['status'] == 'REPLIED') $badge = 'success';
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong class="text-dark"><?= htmlspecialchars($m['name']) ?></strong>
                                        <div class="text-muted small">Email: <?= htmlspecialchars($m['email']) ?></div>
                                        <div class="text-muted small">Phone: <?= htmlspecialchars($m['phone'] ?: '-') ?></div>
                                    </td>
                                    <td>
                                        <p class="mb-1 text-secondary small" style="max-width: 350px; white-space: normal; line-height: 1.5;"><?= nl2br(htmlspecialchars($m['message'])) ?></p>
                                        <span class="text-muted small" style="font-size: 0.75rem;"><i class="fa fa-clock me-1"></i> Received: <?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badge ?>-subtle text-<?= $badge ?> border border-<?= $badge ?>-subtle px-2 py-1">
                                            <?= htmlspecialchars($m['status']) ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-center">
                                        <?php if($m['status'] == 'NEW'): ?>
                                            <a href="?mark_replied=<?= $m['id'] ?>" class="btn btn-outline-success btn-sm px-3">
                                                <i class="fa fa-check"></i> Mark Replied
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fa fa-check-circle me-1 text-success"></i> Handled</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No messages found in your inbox.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
