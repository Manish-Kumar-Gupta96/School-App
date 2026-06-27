<?php
require_once(__DIR__ . '/../config/database.php');
$pdo = getDBConnection();

$current_page_uri = $_SERVER['REQUEST_URI'] ?? '/';
$current_page_path = parse_url($current_page_uri, PHP_URL_PATH);
$normalized_path = trim($current_page_path, '/');
if (empty($normalized_path) || $normalized_path === 'index.php') {
    $normalized_path = '/';
} else {
    $normalized_path = '/' . $normalized_path;
}

// Fetch Global SEO Settings
$seo_settings = null;
try {
    $stmt_settings = $pdo->query("SELECT * FROM seo_settings LIMIT 1");
    $seo_settings = $stmt_settings->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback
}

$site_name = $seo_settings['site_name'] ?? 'VIC School';
$meta_title = $seo_settings['default_meta_title'] ?? 'VIC School - Inspiring Excellence';
$meta_description = $seo_settings['default_meta_description'] ?? 'VIC School is committed to providing high-quality education.';
$meta_keywords = $seo_settings['default_meta_keywords'] ?? 'school, education, vic school, erp';
$og_image = $seo_settings['default_og_image'] ?? 'assets/images/default-user.png';
$canonical_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$current_page_path";
$schema_markup = '';
$twitter_card = 'summary_large_image';

// Fetch Page-Specific SEO settings
try {
    $stmt_page = $pdo->prepare("SELECT * FROM seo_pages WHERE page_path = ?");
    $stmt_page->execute([$normalized_path]);
    $page_seo = $stmt_page->fetch(PDO::FETCH_ASSOC);
    if ($page_seo) {
        if (!empty($page_seo['meta_title'])) $meta_title = $page_seo['meta_title'];
        if (!empty($page_seo['meta_description'])) $meta_description = $page_seo['meta_description'];
        if (!empty($page_seo['og_image'])) $og_image = $page_seo['og_image'];
        if (!empty($page_seo['twitter_card'])) $twitter_card = $page_seo['twitter_card'];
        if (!empty($page_seo['canonical_url'])) $canonical_url = $page_seo['canonical_url'];
        if (!empty($page_seo['schema_markup'])) $schema_markup = $page_seo['schema_markup'];
    }
} catch (PDOException $e) {
    // Fallback
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($meta_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($meta_keywords) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($meta_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="<?= htmlspecialchars($twitter_card) ?>">
    <meta property="twitter:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="twitter:title" content="<?= htmlspecialchars($meta_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($meta_description) ?>">
    <meta property="twitter:image" content="<?= htmlspecialchars($og_image) ?>">

    <!-- Schema Markup -->
    <?php if (!empty($schema_markup)): ?>
        <?= $schema_markup ?>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/default-user.png">

    <!-- Bootstrap (Local) -->
    <link href="assets/css/libs/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (Local) -->
    <link rel="stylesheet" href="assets/css/libs/fa/all.min.css">

    <!-- AOS (Local) -->
    <link rel="stylesheet" href="assets/css/libs/aos.css">

    <!-- Swiper (Local) -->
    <link rel="stylesheet" href="assets/css/libs/swiper-bundle.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">


</head>

<body>

<!-- =========================
TOP HEADER
========================= -->

<div class="top-bar">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-6">

                <div class="top-left">

                    <span>
                        <i class="fa-solid fa-phone"></i>
                        +91 XXXXX XXXXX
                    </span>

                    <span>
                        <i class="fa-solid fa-envelope"></i>
                        info@vicschool.edu.in
                    </span>

                </div>

            </div>

            <div class="col-lg-6 text-end">

                <div class="top-right">

                    <a href="#">
                        <i class="fab fa-facebook-f"></i>
                    </a>

                    <a href="#">
                        <i class="fab fa-instagram"></i>
                    </a>

                    <a href="#">
                        <i class="fab fa-youtube"></i>
                    </a>

                    <span class="admission-open">
                        Admissions Open 2026-27
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>
