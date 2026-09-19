<?php

if (!isset($currentPage)) {
    $currentPage = "";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>

        Godaddy Booking Admin

    </title>

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <?php if (isset($pageCss) && !empty($pageCss)): ?>

        <link
            rel="stylesheet"
            href="<?= htmlspecialchars($pageCss); ?>">

    <?php endif; ?>


<?php

/* Detect current folder */

$currentDir = basename(dirname($_SERVER['PHP_SELF']));

if ($currentDir == "admin") {

?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<?php

} else {

?>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<?php

}

?>

    <style>
        .admin-dropdown {
            position: relative;
        }

        .admin {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin .dropdown-caret {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.85);
            margin-left: 2px;
        }

        .admin-dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 14px);
            right: 0;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            min-width: 210px;
            overflow: hidden;
            z-index: 100;
        }

        .admin-dropdown-menu.active {
            display: block;
        }

        .admin-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .admin-dropdown-menu a i {
            width: 16px;
            color: #888;
        }

        .admin-dropdown-menu a:hover {
            background: #f5f7ff;
        }

        .admin-dropdown-menu a.logout-link {
            border-top: 1px solid #eee;
            color: #c0392b;
        }

        .admin-dropdown-menu a.logout-link i {
            color: #c0392b;
        }
    </style>

</head>

<body>

<!-- ===========================================
TOP BAR
=========================================== -->

<header class="topbar">

    <div class="left">

        <!-- <i class="fa-solid fa-bars"></i> -->

        <h2>

            Godaddy Booking Admin

        </h2>

    </div>

    <div class="right">

        <i class="fa-regular fa-bell"></i>

        <div class="admin-dropdown">

            <div class="admin" id="adminDropdownTrigger">

                <div>

                    <strong>

                        <?= htmlspecialchars($_SESSION['admin_name']); ?>

                    </strong>

                    <p>

                        Administrator

                    </p>

                </div>

                <img
                    src="https://i.pravatar.cc/50"
                    alt="Admin">

                <i class="fa-solid fa-chevron-down dropdown-caret"></i>

            </div>

            <div class="admin-dropdown-menu" id="adminDropdownMenu">

                <a href="/WEBSITE/admin/change_password.php">

                    <i class="fa-solid fa-key"></i>

                    Change Password

                </a>

                <a href="/WEBSITE/admin/logout.php" class="logout-link">

                    <i class="fa-solid fa-right-from-bracket"></i>

                    Logout

                </a>

            </div>

        </div>

    </div>

</header>

<!-- ===========================================
MENU
=========================================== -->

<nav class="menu">

<a
href="/WEBSITE/admin/dashboard.php"
class="<?= ($currentPage=="dashboard") ? "active" : ""; ?>">

<i class="fa-solid fa-house"></i>

Dashboard

</a>

<a
href="/WEBSITE/admin/property/list.php"
class="<?= ($currentPage=="properties") ? "active" : ""; ?>">

<i class="fa-solid fa-hotel"></i>

Properties

</a>

<a
href="/WEBSITE/admin/property/rooms.php"
class="<?= ($currentPage=="rooms") ? "active" : ""; ?>">

<!-- <i class="fa-solid fa-wifi"></i> -->
<i class="fa-solid fa-bed"></i>

Add rooms

</a>


<a
href="/WEBSITE/admin/property/bookings.php"
class="<?= ($currentPage=="bookings") ? "active" : ""; ?>">

<i class="fa-solid fa-calendar-check"></i>

Bookings

</a>

<?php if (!isset($_SESSION['is_manager']) || (int)$_SESSION['is_manager'] === 0) { ?>



<a
href="/WEBSITE/admin/property/destinations.php"
class="<?= ($currentPage=="destinations") ? "active" : ""; ?>">

<i class="fa-solid fa-location-dot"></i>

Destinations

</a>

<a
href="/WEBSITE/admin/property/manager_list.php"
class="<?= ($currentPage=="managers") ? "active" : ""; ?>">

<i class="fa-solid fa-users"></i>

Managers

</a>

<a
href="/WEBSITE/admin/property/user_list.php"
class="<?= ($currentPage=="registered_users") ? "active" : ""; ?>">

<i class="fa-solid fa-user-group"></i>

Users

</a>

<a
href="/WEBSITE/admin/property/reviews.php"
class="<?= ($currentPage=="reviews") ? "active" : ""; ?>">

<i class="fa-solid fa-users"></i>

Reviews

</a>

<?php } ?>

<a
href="/WEBSITE/admin/logout.php">

<i class="fa-solid fa-right-from-bracket"></i>

Logout

</a>

</nav>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        const trigger = document.getElementById("adminDropdownTrigger");
        const menu = document.getElementById("adminDropdownMenu");

        if (trigger && menu) {

            trigger.addEventListener("click", function (e) {
                e.stopPropagation();
                menu.classList.toggle("active");
            });

            document.addEventListener("click", function (e) {
                if (!e.target.closest(".admin-dropdown")) {
                    menu.classList.remove("active");
                }
            });

        }

    });
</script>