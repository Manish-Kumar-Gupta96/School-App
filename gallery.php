<?php 
require_once('config/database.php');
include('includes/header.php'); 
include('includes/navbar.php'); 

// Fetch dynamic gallery items
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY id DESC");
$dynamic_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ==========================
PAGE BANNER
========================== -->
<section class="page-banner">
    <div class="container">
        <h1>School Gallery</h1>
        <p>
            Capturing Memories, Celebrating Achievements
        </p>
    </div>
</section>

<!-- ==========================
GALLERY FILTER
========================== -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Explore Our Moments</h2>
            <p>
                A glimpse into the vibrant life at VIC School.
            </p>
        </div>
        
        <div class="gallery-filter text-center mb-5">
            <button class="gallery-btn active" data-filter="all">
                All
            </button>
            <button class="gallery-btn" data-filter="campus">
                Campus
            </button>
            <button class="gallery-btn" data-filter="sports">
                Sports
            </button>
            <button class="gallery-btn" data-filter="events">
                Events
            </button>
            <button class="gallery-btn" data-filter="academics">
                Academics
            </button>
        </div>
        
        <div class="row g-4">
            <?php if (count($dynamic_images) > 0): ?>
                <?php foreach($dynamic_images as $img): ?>
                    <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="<?= htmlspecialchars($img['category']) ?>">
                        <div class="gallery-card">
                            <img src="<?= htmlspecialchars($img['image_path']) ?>" alt="<?= htmlspecialchars($img['title']) ?>">
                            <div class="gallery-overlay">
                                <a href="<?= htmlspecialchars($img['image_path']) ?>" target="_blank">
                                    <i class="fas fa-search-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Gallery Item 1 - Campus -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="campus">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery1.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery1.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Gallery Item 2 - Campus -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="campus">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery2.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery2.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Gallery Item 3 - Sports -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="sports">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery3.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery3.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Gallery Item 4 - Academics -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="academics">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery4.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery4.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Gallery Item 5 - Academics -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="academics">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery5.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery5.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Gallery Item 6 - Events -->
                <div class="col-lg-4 col-md-6 gallery-card-wrapper" data-category="events">
                    <div class="gallery-card">
                        <img src="assets/images/gallery/gallery6.jpg" alt="Gallery">
                        <div class="gallery-overlay">
                            <a href="assets/images/gallery/gallery6.jpg" target="_blank">
                                <i class="fas fa-search-plus"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>


<!-- ==========================
EVENT HIGHLIGHTS
========================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-title">
            <h2>Event Highlights</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-trophy mb-3 fa-2x text-primary"></i>
                    <h4>Annual Sports Day</h4>
                    <p>
                        Celebrating teamwork, fitness and sportsmanship.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-music mb-3 fa-2x text-primary"></i>
                    <h4>Annual Function</h4>
                    <p>
                        A showcase of talent, creativity and culture.
                    </p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="feature-card">
                    <i class="fas fa-flask mb-3 fa-2x text-primary"></i>
                    <h4>Science Exhibition</h4>
                    <p>
                        Innovative projects by our young minds.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
VIDEO GALLERY
========================== -->
<section class="py-5">
    <div class="container">
        <div class="section-title">
            <h2>Video Gallery</h2>
            <p>
                Watch memorable moments from VIC School.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="ratio ratio-16x9">
                    <iframe
                        src="https://www.youtube.com/embed/ScMzIvxBSi4"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ratio ratio-16x9">
                    <iframe
                        src="https://www.youtube.com/embed/ScMzIvxBSi4"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==========================
CTA
========================== -->
<section class="py-5">
    <div class="container">
        <div class="admission-banner text-center">
            <h2>
                Be Part Of Our Journey
            </h2>
            <p class="mb-4">
                Join VIC School and create lifelong memories.
            </p>
            <a href="admissions.php"
                class="primary-btn">
                Apply Now
            </a>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>
