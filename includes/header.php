<?php

/*====================================================
ENSURE A SESSION EXISTS - header.php is included by pages
that don't always call session_start() themselves (e.g.
index.php, property-details.php), but the nav below needs
to know if the person is logged in regardless of page.
====================================================*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*====================================================
DEFAULTS - so this still works even if a page forgets
to set these before including header.php
====================================================*/

if (!isset($pageTitle)) {
    $pageTitle = "Godaddy Booking";
}

if (!isset($currentPage)) {
    $currentPage = "";
}

if (!isset($metaDescription)) {
    $metaDescription = "Book verified resorts, villas, homestays, and cottages across Kerala's top destinations. Best prices, instant booking, 24/7 support.";
}

if (!isset($canonicalUrl)) {

    $canonicalUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . strtok($_SERVER['REQUEST_URI'], '?');

}

/*====================================================
PROPERTY TYPES - for the Properties nav dropdown.
$conn is expected to already be in scope, since every
page requires config/db.php before including this file.
====================================================*/

$navPropertyTypes = [];

if (isset($conn)) {

    $typeResult = mysqli_query($conn, "SELECT * FROM property_types WHERE is_active = 1 ORDER BY type_name ASC");

    if ($typeResult) {

        while ($row = mysqli_fetch_assoc($typeResult)) {
            $navPropertyTypes[] = $row;
        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle); ?></title>

    <meta name="description" content="<?= htmlspecialchars($metaDescription); ?>">

    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl); ?>">
    <link rel="icon" type="image/png" href="assets/images/logo-mb.png">
    <link rel="apple-touch-icon" href="assets/images/logo-mb.png">

    <!-- Open Graph (WhatsApp / Facebook link previews) -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription); ?>">
    <meta property="og:image" content="<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']; ?>/assets/images/logo-mb.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">

    <!-- Twitter Card (falls back to Open Graph values automatically on most platforms) -->
    <meta name="twitter:card" content="summary_large_image">

    <link rel="preconnect"
        href="https://fonts.googleapis.com">
    <link rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet"
        href="assets/css/styles.css">

    <?php if (isset($pageCss) && !empty($pageCss)) {

    $pageCssFiles = is_array($pageCss) ? $pageCss : [$pageCss];

    foreach ($pageCssFiles as $cssFile) { ?>

        <link rel="stylesheet" href="<?= htmlspecialchars($cssFile); ?>">

    <?php }} ?>

</head>

<body id="top">
    <!-- ================= NAVBAR ================= -->
        <header class="<?= ($currentPage != 'home' || isset($_GET['booking_confirmed'])) ? 'header-solid' : ''; ?>">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <img src="assets/images/logo-mb.png" alt="">
                <h2>GodaddyBooking</h2>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div class="nav-collapse" id="navCollapse">

                <nav>
                    <a href="index.php" class="<?= ($currentPage == 'home') ? 'active' : ''; ?>">Home</a>

                    <div class="nav-item-dropdown">

                        <a href="properties.php" class="nav-dropdown-trigger <?= ($currentPage == 'properties') ? 'active' : ''; ?>">
                            Properties
                            <?php if (!empty($navPropertyTypes)) { ?>
                                <i class="fa-solid fa-chevron-down nav-caret"></i>
                            <?php } ?>
                        </a>

                        <?php if (!empty($navPropertyTypes)) { ?>

                            <div class="nav-dropdown-menu">

                                <a href="properties.php">All Properties</a>

                                <?php foreach ($navPropertyTypes as $type) { ?>

                                    <a href="properties.php?type_id=<?= $type['id']; ?>"><?= htmlspecialchars($type['type_name']); ?></a>

                                <?php } ?>

                            </div>

                        <?php } ?>

                    </div>

                    <a href="index.php#destinations" class="<?= ($currentPage == 'destinations') ? 'active' : ''; ?>">Destinations</a>
                    <a href="index.php#why-us" class="<?= ($currentPage == 'about') ? 'active' : ''; ?>">About</a>
                    <a href="contact-us.php" class="<?= ($currentPage == 'contact') ? 'active' : ''; ?>">Contact</a>

                    <?php if (isset($_SESSION['user_id'])) { ?>

                        <a href="current-booking.php" class="<?= ($currentPage == 'my-bookings') ? 'active' : ''; ?>">My Bookings</a>
                        <a href="account-settings.php" class="<?= ($currentPage == 'account-settings') ? 'active' : ''; ?>">Account Settings</a>
                    <?php } ?>

                </nav>

                <div class="nav-account">

                    <?php if (isset($_SESSION['user_id'])) { ?>

                        <span class="nav-account-name"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?></span>

                        <a href="logout.php" class="nav-logout-link">Log Out</a>

                    <?php } else { ?>

                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>" class="nav-login-link">Log In</a>

                    <?php } ?>

                </div>

                <a href="properties.php" class="book-btn">
                    Book Now
                </a>

            </div>
        </div>
    </header>

    <script>
        document.querySelectorAll(".nav-item-dropdown").forEach(function (item) {

            const caret = item.querySelector(".nav-caret");

            if (caret) {

                caret.addEventListener("click", function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    item.classList.toggle("open");
                });

            }

        });

        document.addEventListener("click", function (e) {

            document.querySelectorAll(".nav-item-dropdown.open").forEach(function (item) {

                if (!item.contains(e.target)) {
                    item.classList.remove("open");
                }

            });

        });

        const navToggle = document.getElementById("navToggle");
        const navCollapse = document.getElementById("navCollapse");

        if (navToggle && navCollapse) {

            navToggle.addEventListener("click", function () {

                const isOpen = navCollapse.classList.toggle("open");

                navToggle.setAttribute("aria-expanded", isOpen ? "true" : "false");

                navToggle.innerHTML = isOpen
                    ? '<i class="fa-solid fa-xmark"></i>'
                    : '<i class="fa-solid fa-bars"></i>';

            });

            // Close the menu when a normal link is tapped (but not the
            // Properties dropdown trigger, which needs its own click first)
            navCollapse.querySelectorAll("a").forEach(function (link) {

                link.addEventListener("click", function () {

                    if (window.innerWidth <= 768 && !link.classList.contains("nav-dropdown-trigger")) {

                        navCollapse.classList.remove("open");
                        navToggle.setAttribute("aria-expanded", "false");
                        navToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';

                    }

                });

            });

        }
    </script>