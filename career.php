<?php
require_once('config/database.php');
include('includes/header.php');
include('includes/navbar.php');

$jobs = $pdo->query("
    SELECT * FROM job_openings
    WHERE status='OPEN'
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-banner">
    <div class="container">
        <h1>Careers at VIC School</h1>
        <p>Join Our Teaching Faculty & Staff Team</p>
    </div>
</section>

<section class="py-5 text-start">
    <div class="container">
        <div class="section-title text-center mb-5">
            <h2>Job Openings</h2>
            <p>Explore exciting career opportunities in education and administration.</p>
        </div>

        <div class="row g-4">
            <?php if(count($jobs) > 0): ?>
                <?php foreach($jobs as $j): ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 p-4 h-100" style="border-radius: 12px;">
                            <span class="badge bg-primary-subtle text-primary mb-2 align-self-start"><?= htmlspecialchars($j['department']) ?></span>
                            <h4 class="fw-bold text-dark mb-2"><?= htmlspecialchars($j['title']) ?></h4>
                            
                            <p class="text-secondary small mb-3"><?= nl2br(htmlspecialchars($j['description'])) ?></p>
                            
                            <div class="mb-4">
                                <strong class="small text-muted">Requirements:</strong>
                                <p class="text-muted small mb-0"><?= nl2br(htmlspecialchars($j['requirements'])) ?></p>
                            </div>
                            
                            <div class="mt-auto d-flex justify-content-between align-items-center border-top pt-3">
                                <span class="fw-bold text-success">Salary: <?= htmlspecialchars($j['salary'] ?: 'As per Norms') ?></span>
                                <a href="apply.php?job_id=<?= $j['id'] ?>" class="btn btn-primary btn-sm px-4">Apply Now</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-warning py-4 border-0 shadow-sm" style="max-width: 600px; margin: auto;">
                        <i class="fa fa-info-circle me-2 fs-4 mb-2 d-block"></i> Currently there are no open vacancies. Please check back later!
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
