<?php 
require_once('config/database.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $msgContent = sanitize($_POST['message'] ?? '');
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($submitted_token)) {
        $error = "CSRF token validation failed. Please try again.";
    } elseif (!empty($name) && !empty($email) && !empty($msgContent)) {
        try {
            // Log to contact_messages for backwards compatibility
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, message, status) VALUES (?, ?, ?, ?, 'NEW')");
            $stmt->execute([$name, $email, $phone, $msgContent]);

            // Save to CRM Leads
            $stmt_crm = $pdo->prepare("INSERT INTO crm_leads (school_id, name, email, phone, class_applied, message, source, status) VALUES (?, ?, ?, ?, ?, ?, 'Website', 'New Lead')");
            $stmt_crm->execute([CURRENT_SCHOOL_ID, $name, $email, $phone, null, $msgContent]);

            $message = "Your message has been sent successfully. We will get back to you soon!";
        } catch (Exception $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch dynamic contact info
$settings = $pdo->query("SELECT * FROM contact_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    $settings = [
        'phone' => '+91 XXXXX XXXXX',
        'email' => 'info@vicschool.edu.in',
        'address' => 'VIC School Campus, Your City, State, India',
        'google_map' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14008.272210874558!2d77.20653696879899!3d28.627689100000016!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390cfd37e2815555%3A0x2db44d57c4cf0f95!2sCentral%20Secretariat%2C%20New%20Delhi%2C%20Delhi!5e0!3m2!1sen!2sin!4v1716500000000!5m2!1sen!2sin',
        'facebook' => '#',
        'instagram' => '#',
        'youtube' => '#'
    ];
}

include('includes/header.php'); 
include('includes/navbar.php'); 
?>

<!-- ==========================
PAGE BANNER
========================== -->
<section class="page-banner">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We'd Love To Hear From You</p>
    </div>
</section>

<!-- ==========================
CONTACT INFO
========================== -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Get In Touch</h2>
            <p>Reach out to us for admissions, inquiries or support.</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-location-dot mb-3 fa-2x text-primary"></i>
                    <h4>Address</h4>
                    <p><?= nl2br(htmlspecialchars($settings['address'])) ?></p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-phone mb-3 fa-2x text-primary"></i>
                    <h4>Phone</h4>
                    <p><?= htmlspecialchars($settings['phone']) ?></p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-envelope mb-3 fa-2x text-primary"></i>
                    <h4>Email</h4>
                    <p><?= htmlspecialchars($settings['email']) ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
CONTACT FORM
========================== -->
<section class="py-5 bg-light text-start">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="contact-form-box p-4 bg-white rounded shadow-sm">
                    <h3 class="mb-4 fw-bold">Send Us A Message</h3>
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle me-1"></i> <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= csrf() ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="email" name="email" class="form-control" placeholder="Email Address" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <input type="tel" name="phone" class="form-control" placeholder="Phone Number">
                            </div>
                            <div class="col-12 mb-3">
                                <textarea class="form-control" rows="5" name="message" placeholder="Your Message" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary px-4 py-2 border-0">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="contact-info-box p-4 bg-white rounded shadow-sm h-100">
                    <h3 class="fw-bold mb-3">School Information</h3>
                    <hr>
                    <p>
                        <strong>Office Hours:</strong><br>
                        Monday - Saturday<br>
                        8:00 AM - 4:00 PM
                    </p>
                    <p>
                        <strong>Admissions Office:</strong><br>
                        Monday - Friday<br>
                        9:00 AM - 3:00 PM
                    </p>
                    <p>
                        <strong>Support:</strong><br>
                        <?= htmlspecialchars($settings['email']) ?>
                    </p>
                    <div class="social-links mt-4">
                        <a href="<?= htmlspecialchars($settings['facebook'] ?: '#') ?>" target="_blank" class="btn btn-outline-primary btn-sm me-1"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?= htmlspecialchars($settings['instagram'] ?: '#') ?>" target="_blank" class="btn btn-outline-danger btn-sm me-1"><i class="fab fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars($settings['youtube'] ?: '#') ?>" target="_blank" class="btn btn-outline-danger btn-sm"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
GOOGLE MAP
========================= -->
<?php if(!empty($settings['google_map'])): ?>
<section class="mt-4">
    <?php if(strpos($settings['google_map'], '<iframe') !== false): ?>
        <?= $settings['google_map'] ?>
    <?php else: ?>
        <iframe src="<?= htmlspecialchars($settings['google_map']) ?>" width="100%" height="450" style="border:0;" loading="lazy" allowfullscreen=""></iframe>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php include('includes/footer.php'); ?>
