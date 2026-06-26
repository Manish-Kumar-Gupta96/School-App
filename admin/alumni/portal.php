<?php
require_once('../../config/database.php');
require_once('../../includes/auth.php');

$message = '';
$error = '';

// Self-Initialize tables if they don't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alumni_jobs (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            school_id BIGINT DEFAULT 1,
            alumni_id BIGINT,
            title VARCHAR(255),
            company VARCHAR(255),
            description TEXT,
            contact_info VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS alumni_stories (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            school_id BIGINT DEFAULT 1,
            alumni_id BIGINT,
            headline VARCHAR(255),
            story TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch(PDOException $e) {
    // Already exists or handles silently
}

// Add Job Posting
if (isset($_POST['save_job'])) {
    $alumni_id    = (int)$_POST['alumni_id'];
    $title        = trim($_POST['title']);
    $company      = trim($_POST['company']);
    $description  = trim($_POST['description']);
    $contact_info = trim($_POST['contact_info']);

    if (empty($title) || empty($company) || empty($contact_info)) {
        $error = "Job Title, Company, and Contact info are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO alumni_jobs (school_id, alumni_id, title, company, description, contact_info)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $alumni_id ?: null,
            $title,
            $company,
            $description,
            $contact_info
        ]);
        $message = "Job vacancy posted on alumni board!";
    }
}

// Add Success Story
if (isset($_POST['save_story'])) {
    $alumni_id = (int)$_POST['alumni_id'];
    $headline  = trim($_POST['headline']);
    $story     = trim($_POST['story']);

    if (empty($alumni_id) || empty($headline) || empty($story)) {
        $error = "Alumni student, Headline, and Story contents are required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO alumni_stories (school_id, alumni_id, headline, story)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            CURRENT_SCHOOL_ID,
            $alumni_id,
            $headline,
            $story
        ]);
        $message = "Success story published successfully!";
    }
}

// Fetch Alumni for dropdowns
$stmt_alumni = $pdo->prepare("
    SELECT a.id, s.first_name, s.last_name, a.passout_year 
    FROM alumni a
    JOIN students s ON a.student_id = s.id
    WHERE a.school_id = ?
    ORDER BY s.first_name ASC
");
$stmt_alumni->execute([CURRENT_SCHOOL_ID]);
$alumniList = $stmt_alumni->fetchAll(PDO::FETCH_ASSOC);

// Fetch Jobs
$stmt_jobs = $pdo->prepare("
    SELECT aj.*, s.first_name, s.last_name, a.passout_year 
    FROM alumni_jobs aj
    LEFT JOIN alumni a ON aj.alumni_id = a.id
    LEFT JOIN students s ON a.student_id = s.id
    WHERE aj.school_id = ?
    ORDER BY aj.id DESC
");
$stmt_jobs->execute([CURRENT_SCHOOL_ID]);
$jobs = $stmt_jobs->fetchAll(PDO::FETCH_ASSOC);

// Fetch Stories
$stmt_stories = $pdo->prepare("
    SELECT ast.*, s.first_name, s.last_name, a.passout_year 
    FROM alumni_stories ast
    JOIN alumni a ON ast.alumni_id = a.id
    JOIN students s ON a.student_id = s.id
    WHERE ast.school_id = ?
    ORDER BY ast.id DESC
");
$stmt_stories->execute([CURRENT_SCHOOL_ID]);
$stories = $stmt_stories->fetchAll(PDO::FETCH_ASSOC);

// Layout variables
$root_path = "../../";
$page_title = "Alumni Portal | VIC ERP";
$page_header = "Alumni Management";
$active_menu = "alumni";

require_once('../includes/header.php');
require_once('../includes/topbar.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap g-2">
    <h5 class="text-muted mb-0">Manage school ex-students and networking portals</h5>
    <div class="d-flex gap-2">
        <a href="directory.php" class="btn btn-outline-primary">
            <i class="fa fa-graduation-cap me-1"></i> Alumni Directory
        </a>
        <a href="events.php" class="btn btn-outline-primary">
            <i class="fa fa-calendar-alt me-1"></i> Meet Events
        </a>
        <a href="donations.php" class="btn btn-outline-success">
            <i class="fa fa-hand-holding-dollar me-1"></i> Donations
        </a>
        <a href="portal.php" class="btn btn-info text-white">
            <i class="fa fa-share-nodes me-1"></i> Networking & Stories
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Forms Column -->
    <div class="col-lg-4 mb-4">
        <!-- Post Job Card -->
        <div class="card shadow border-0 mb-4" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Post on Job Board</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Referral Alumni</label>
                        <select name="alumni_id" class="form-select">
                            <option value="">Choose Alumni (Optional)...</option>
                            <?php foreach($alumniList as $al): ?>
                                <option value="<?= $al['id'] ?>">
                                    <?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?> (Class of <?= (int)$al['passout_year'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Intern, Analyst" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="company" class="form-control" placeholder="e.g. Infosys" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Job Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Role duties, requirements..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Contact Email / Website <span class="text-danger">*</span></label>
                        <input type="text" name="contact_info" class="form-control" placeholder="e.g. careers@company.com" required>
                    </div>

                    <button type="submit" name="save_job" class="btn btn-primary w-100">
                        <i class="fa fa-briefcase me-1"></i> Post Vacancy
                    </button>
                </form>
            </div>
        </div>

        <!-- Success Story Card -->
        <div class="card shadow border-0" style="border-radius: 12px;">
            <div class="card-header bg-white border-0 py-3 ps-4">
                <h5 class="fw-bold mb-0 text-dark">Publish Success Story</h5>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Featured Alumni <span class="text-danger">*</span></label>
                        <select name="alumni_id" class="form-select" required>
                            <option value="">Choose Alumni...</option>
                            <?php foreach($alumniList as $al): ?>
                                <option value="<?= $al['id'] ?>">
                                    <?= htmlspecialchars($al['first_name'] . ' ' . $al['last_name']) ?> (Class of <?= (int)$al['passout_year'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Headline <span class="text-danger">*</span></label>
                        <input type="text" name="headline" class="form-control" placeholder="e.g. Cleared UPSC CSE Exam" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Story Details <span class="text-danger">*</span></label>
                        <textarea name="story" rows="4" class="form-control" placeholder="Detailed story of struggle, preparation, and success..." required></textarea>
                    </div>

                    <button type="submit" name="save_story" class="btn btn-success w-100">
                        <i class="fa fa-pen-fancy me-1"></i> Publish Story
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Feed / Listing Column -->
    <div class="col-lg-8">
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-stories-tab" data-bs-toggle="pill" data-bs-target="#pills-stories" type="button" role="tab" aria-controls="pills-stories" aria-selected="true">
                    <i class="fa fa-trophy me-1"></i> Success Stories
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-jobs-tab" data-bs-toggle="pill" data-bs-target="#pills-jobs" type="button" role="tab" aria-controls="pills-jobs" aria-selected="false">
                    <i class="fa fa-briefcase me-1"></i> Job Board
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            <!-- Stories Tab -->
            <div class="tab-pane fade show active" id="pills-stories" role="tabpanel" aria-labelledby="pills-stories-tab">
                <div class="row g-3">
                    <?php if (count($stories) > 0): ?>
                        <?php foreach($stories as $s): ?>
                            <div class="col-12">
                                <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-warning-subtle text-warning border rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                                <i class="fa fa-award fs-4"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></h6>
                                                <span class="text-muted small">Class of <?= (int)$s['passout_year'] ?></span>
                                            </div>
                                        </div>
                                        <h5 class="fw-bold text-primary mb-2">"<?= htmlspecialchars($s['headline']) ?>"</h5>
                                        <p class="text-secondary mb-0 whitespace-pre-line"><?= htmlspecialchars($s['story']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fa fa-award fs-2 mb-2 d-block"></i>
                            No alumni success stories published.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Jobs Tab -->
            <div class="tab-pane fade" id="pills-jobs" role="tabpanel" aria-labelledby="pills-jobs-tab">
                <div class="row g-3">
                    <?php if (count($jobs) > 0): ?>
                        <?php foreach($jobs as $j): ?>
                            <div class="col-12">
                                <div class="card shadow-sm border-0 mb-3" style="border-radius: 12px;">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($j['title']) ?></h5>
                                                <span class="badge bg-primary-subtle text-primary mb-2"><?= htmlspecialchars($j['company']) ?></span>
                                            </div>
                                            <span class="text-muted small"><?= date('d M Y', strtotime($j['created_at'])) ?></span>
                                        </div>
                                        <p class="text-secondary whitespace-pre-line small"><?= htmlspecialchars($j['description'] ?: 'No description provided.') ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <span class="small text-muted">
                                                <i class="fa fa-user me-1"></i> Posted by: <?= $j['first_name'] ? htmlspecialchars($j['first_name'] . ' ' . $j['last_name'] . ' (Class of ' . $j['passout_year'] . ')') : 'Admin' ?>
                                            </span>
                                            <span class="fw-semibold text-primary small">
                                                <i class="fa fa-envelope me-1"></i> Apply: <?= htmlspecialchars($j['contact_info']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5 text-muted">
                            <i class="fa fa-briefcase fs-2 mb-2 d-block"></i>
                            No job listings posted on the alumni board.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('../includes/footer.php');
?>
