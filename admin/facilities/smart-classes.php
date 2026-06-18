<?php
require_once('../../../config/database.php');
$root_path = "../../../";
$page_title = "Smart Classrooms Inventory | Admin Control";
$active_menu = "hostel"; // Fits under physical assets menu
require_once('../../includes/header.php');
require_once('../../includes/topbar.php');

$message = '';
$error = '';

if(isset($_POST['save'])){
    $room     = trim($_POST['room']);
    $features = trim($_POST['features']);
    $teacher  = trim($_POST['teacher']);

    if(empty($room) || empty($teacher)){
        $error = "Room Number and Assigned Mentor Teacher are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO smart_classes (room_no, features, assigned_teacher)
            VALUES (?,?,?)
        ");
        $stmt->execute([$room, $features, $teacher]);
        $message = "Smart Class record created successfully!";
    }
}

$data = $pdo->query("SELECT * FROM smart_classes ORDER BY room_no ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-dashboard text-start">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">🏫 Smart Classes Controller</h2>
            <p class="text-muted mb-0">Record multimedia projector rooms, assigned teachers, and features details.</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 p-4" style="border-radius: 12px;">
                <h5 class="fw-bold text-dark mb-4"><i class="fa fa-chalkboard me-2 text-primary"></i>Register Smart Class</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Room Number</label>
                        <input type="text" name="room" class="form-control" placeholder="e.g. Room 204" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Assigned Mentor Teacher</label>
                        <input type="text" name="teacher" class="form-control" placeholder="e.g. Mrs. Sunita Verma" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-semibold">Features Details</label>
                        <textarea name="features" class="form-control" rows="4" placeholder="e.g. 4K Projector, Smart Interactive Whiteboard, Surround Audio"></textarea>
                    </div>

                    <div class="col-md-12 mt-4 text-end">
                        <button type="submit" name="save" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="fa fa-save me-1"></i> Register Classroom
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-white border-0 py-3 ps-4">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fa fa-list me-2 text-secondary"></i>Registered Classrooms</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light text-start">
                                <tr>
                                    <th class="ps-4">Room No</th>
                                    <th>Mentor Teacher</th>
                                    <th class="pe-4">Features/Specs Details</th>
                                </tr>
                            </thead>
                            <tbody class="text-start">
                                <?php if(count($data) > 0): ?>
                                    <?php foreach($data as $d): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($d['room_no']) ?></td>
                                            <td><?= htmlspecialchars($d['assigned_teacher']) ?></td>
                                            <td class="pe-4 text-muted small"><?= htmlspecialchars($d['features'] ?: 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted">No smart classrooms registered yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../../includes/footer.php');
?>
