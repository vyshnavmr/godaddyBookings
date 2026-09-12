<?php

header("Content-Type: application/xml; charset=utf-8");

require_once "config/db.php";

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

/*====================================================
STATIC PAGES
====================================================*/

$staticPages = [

    ["url" => "/index.php", "priority" => "1.0"],
    ["url" => "/properties.php", "priority" => "0.9"],
    ["url" => "/contact-us.php", "priority" => "0.6"],
    ["url" => "/faq.php", "priority" => "0.5"],
    ["url" => "/privacy-policy.php", "priority" => "0.2"],
    ["url" => "/terms.php", "priority" => "0.2"],

];


/*====================================================
DYNAMIC: EVERY AVAILABLE PROPERTY
====================================================*/

$properties = [];

$result = mysqli_query($conn, "SELECT id, updated_at FROM properties WHERE status = 'Available'");

if ($result) {

    while ($row = mysqli_fetch_assoc($result)) {
        $properties[] = $row;
    }

}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <?php foreach ($staticPages as $page) { ?>

    <url>
        <loc><?= htmlspecialchars($baseUrl . $page['url']); ?></loc>
        <priority><?= $page['priority']; ?></priority>
    </url>

    <?php } ?>

    <?php foreach ($properties as $property) { ?>

    <url>
        <loc><?= htmlspecialchars($baseUrl . "/property-details.php?id=" . $property['id']); ?></loc>
        <priority>0.8</priority>
        <?php if (!empty($property['updated_at'])) { ?>
        <lastmod><?= date("Y-m-d", strtotime($property['updated_at'])); ?></lastmod>
        <?php } ?>
    </url>

    <?php } ?>

</urlset>