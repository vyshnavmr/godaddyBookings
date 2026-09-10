<?php

session_start();

require_once "config/db.php";

/*====================================================
ALREADY LOGGED IN?
====================================================*/

$redirect = $_GET['redirect'] ?? "index.php";

/* Only allow redirecting within this site - never to an
   external URL, to avoid an open-redirect vulnerability */

if (preg_match('#^(https?:)?//#i', $redirect) || str_starts_with($redirect, '\\')) {
    $redirect = "index.php";
}

if (isset($_SESSION['user_id'])) {

    header("Location: " . $redirect);

    exit();

}


/*====================================================
CSRF TOKEN
====================================================*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}


/*====================================================
FLASH ERRORS FROM login-save.php
====================================================*/

$errors = [];

if (isset($_SESSION['login_errors'])) {

    $errors = $_SESSION['login_errors'];

    unset($_SESSION['login_errors']);

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Log In - Godaddy Booking";

$currentPage = "";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap">

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Log In</h2>

                </div>

                <div class="bk-card-body">

                    <p class="bk-card-subtitle">Log in to continue with your booking or manage your account.</p>

                    <?php if (!empty($errors)) { ?>

                        <div class="bk-alert bk-alert-error">

                            <ul>

                                <?php foreach ($errors as $error) { ?>

                                    <li><?= htmlspecialchars($error); ?></li>

                                <?php } ?>

                            </ul>

                        </div>

                    <?php } ?>

                    <form action="login-save.php" method="POST">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                        <div class="bk-form-group">

                            <label>Email or Mobile Number</label>

                            <input type="text" name="identifier" placeholder="name@abc.com or 10-digit number" required autofocus>

                        </div>

                        <div class="bk-form-group">

                            <label>Password</label>

                            <input type="password" name="password" placeholder="Enter your password" required>

                        </div>

                        <p class="bk-forgot-link">

                            <a href="forgot-password.php">Forgot your password?</a>

                        </p>

                        <button type="submit" class="bk-submit-btn">Log In</button>

                    </form>

                    <p class="bk-login-prompt bk-no-border">

                        New here? <a href="properties.php">Browse properties</a> and your account will be created automatically when you book.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>