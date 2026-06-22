<?php
header("Content-Type: application/xml; charset=utf-8");

require_once('config/database.php');

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = "{$protocol}://{$host}/";

// Public pages
$pages = [
    '',
    'about.php',
    'academics.php',
    'admissions.php',
    'facilities.php',
    'gallery.php',
    'contact.php'
];

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Render static pages
foreach ($pages as $page) {
    echo '<url>';
    echo '<loc>' . $base_url . $page . '</loc>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>' . (empty($page) ? '1.0' : '0.8') . '</priority>';
    echo '</url>';
}

// Render dynamic blogs
try {
    $stmt_blogs = $pdo->query("SELECT slug, created_at FROM blogs WHERE status='Published' ORDER BY id DESC");
    while ($blog = $stmt_blogs->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = date('c', strtotime($blog['created_at']));
        echo '<url>';
        echo '<loc>' . $base_url . 'blog-single.php?slug=' . htmlspecialchars($blog['slug']) . '</loc>';
        echo '<lastmod>' . $lastmod . '</lastmod>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.7</priority>';
        echo '</url>';
    }
} catch (PDOException $e) {
    // Ignore database failures
}

// Render dynamic events
try {
    $stmt_events = $pdo->query("SELECT id FROM events ORDER BY id DESC");
    while ($ev = $stmt_events->fetch(PDO::FETCH_ASSOC)) {
        echo '<url>';
        echo '<loc>' . $base_url . 'events.php?id=' . $ev['id'] . '</loc>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.6</priority>';
        echo '</url>';
    }
} catch (PDOException $e) {
    // Ignore database failures
}

// Render dynamic news
try {
    $stmt_news = $pdo->query("SELECT id FROM news ORDER BY id DESC");
    while ($ns = $stmt_news->fetch(PDO::FETCH_ASSOC)) {
        echo '<url>';
        echo '<loc>' . $base_url . 'news.php?id=' . $ns['id'] . '</loc>';
        echo '<changefreq>weekly</changefreq>';
        echo '<priority>0.6</priority>';
        echo '</url>';
    }
} catch (PDOException $e) {
    // Ignore database failures
}

echo '</urlset>';
?>
