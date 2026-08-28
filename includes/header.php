<?php

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

    <?php if (isset($pageCss) && !empty($pageCss)) { ?>

        <link rel="stylesheet" href="<?= htmlspecialchars($pageCss); ?>">

    <?php } ?>

</head>

<body id="top">
    <!-- ================= NAVBAR ================= -->
    <header class="<?= ($currentPage != 'home') ? 'header-solid' : ''; ?>">
        <div class="container nav-container">
            <div class="logo">
                <img src="assets/images/logo-mb.png" alt="">
                <h2>GodaddyBooking</h2>
            </div>
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
                <a href="index.php#contact" class="<?= ($currentPage == 'contact') ? 'active' : ''; ?>">Contact</a>
            </nav>
            <a href="index.php#search-box" class="book-btn">
                Book Now
            </a>
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
    </script>