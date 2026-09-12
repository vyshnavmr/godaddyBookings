<?php

session_start();

require_once "config/db.php";

/*====================================================
ALREADY LOGGED IN?
====================================================*/

$redirect = $_GET['redirect'] ?? "index.php";

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
FLASH ERRORS FROM signup-save.php
====================================================*/

$errors = [];

if (isset($_SESSION['signup_errors'])) {

    $errors = $_SESSION['signup_errors'];

    unset($_SESSION['signup_errors']);

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Sign Up - Godaddy Booking";

$currentPage = "";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap">

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Create Your Account</h2>

                </div>

                <div class="bk-card-body">

                    <p class="bk-card-subtitle">Sign up to book faster and track your bookings.</p>

                    <?php if (!empty($errors)) { ?>

                        <div class="bk-alert bk-alert-error">

                            <ul>

                                <?php foreach ($errors as $error) { ?>

                                    <li><?= htmlspecialchars($error); ?></li>

                                <?php } ?>

                            </ul>

                        </div>

                    <?php } ?>

                    <form action="signup-save.php" method="POST">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect); ?>">

                        <div class="bk-form-group">

                            <label>Full Name</label>

                            <input type="text" name="full_name" placeholder="Enter first and last name" required autofocus>

                        </div>

                        <div class="bk-form-group">

                            <label>Email Address</label>

                            <input type="email" name="email" placeholder="name@abc.com" required>

                        </div>

                        <div class="bk-form-group">

                            <label>Mobile Number</label>

                            <div class="bk-phone-group">

                                <span class="bk-phone-prefix">+91</span>

                                <input type="tel" name="phone" placeholder="e.g. 1234567890" pattern="[0-9]{10}" required>

                            </div>

                        </div>

                        <div class="bk-form-row">

                            <div class="bk-form-group">

                                <label>Password</label>

                                <input type="password" name="password" placeholder="Create a password" minlength="8" required>

                            </div>

                            <div class="bk-form-group">

                                <label>Confirm Password</label>

                                <input type="password" name="confirm_password" placeholder="Re-enter your password" minlength="8" required>

                            </div>

                        </div>

                        <button type="submit" class="bk-submit-btn">Sign Up</button>

                    </form>

                    <p class="bk-login-prompt bk-no-border">

                        Already have an account? <a href="login.php?redirect=<?= urlencode($redirect); ?>">Log in</a> instead.

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>