<?php
header("Content-Type: text/plain; charset=utf-8");

require_once('config/database.php');

$robots_txt = '';
try {
    $stmt = $pdo->query("SELECT robots_txt FROM seo_settings LIMIT 1");
    $robots_txt = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignore
}

if (empty($robots_txt)) {
    // Default fallback rules
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $sitemap_url = "{$protocol}://{$host}/sitemap.xml.php";

    $robots_txt = "User-agent: *\n";
    $robots_txt .= "Disallow: /admin/\n";
    $robots_txt .= "Disallow: /student/\n";
    $robots_txt .= "Disallow: /teacher/\n";
    $robots_txt .= "Disallow: /parent/\n";
    $robots_txt .= "Disallow: /api/\n";
    $robots_txt .= "Disallow: /middleware/\n";
    $robots_txt .= "\nSitemap: {$sitemap_url}\n";
}

echo $robots_txt;
?>
