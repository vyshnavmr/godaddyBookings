<?php

require_once "config/db.php";

session_start();


$token = trim($_GET['token'] ?? "");

$validUser = null;

$formErrors = [];

if (isset($_SESSION['reset_errors'])) {

    $formErrors = $_SESSION['reset_errors'];

    unset($_SESSION['reset_errors']);

}

if ($token !== "") {

    $hashedToken = hash('sha256', $token);

    $sql = "SELECT id, name FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $hashedToken);

    mysqli_stmt_execute($stmt);

    $validUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

}

$pageTitle = "Reset Password - Godaddy Booking";

$currentPage = "";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap">

            <div class="bk-card">

                <?php if ($validUser) { ?>

                    <div class="bk-card-header">

                        <h2>Set a New Password</h2>

                    </div>

                    <div class="bk-card-body">

                        <p class="bk-card-subtitle">Hi <?= htmlspecialchars($validUser['name']); ?>, choose a new password for your account.</p>

                        <?php if (!empty($formErrors)) { ?>

                            <div class="bk-alert bk-alert-error">

                                <ul>

                                    <?php foreach ($formErrors as $error) { ?>

                                        <li><?= htmlspecialchars($error); ?></li>

                                    <?php } ?>

                                </ul>

                            </div>

                        <?php } ?>

                        <form action="reset-password-save.php" method="POST">

                            <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">

                            <div class="bk-form-group">

                                <label>New Password</label>

                                <input type="password" name="new_password" placeholder="Create a password" minlength="8" required>

                            </div>

                            <div class="bk-form-group">

                                <label>Confirm Password</label>

                                <input type="password" name="confirm_password" placeholder="Re-enter your password" minlength="8" required>

                            </div>

                            <button type="submit" class="bk-submit-btn">Update Password</button>

                        </form>

                    </div>

                <?php } else { ?>

                    <div class="bk-card-header">

                        <h2>Link Invalid or Expired</h2>

                    </div>

                    <div class="bk-card-body" style="text-align:center;">

                        <p class="bk-card-subtitle">This password reset link is invalid or has expired. Reset links are only valid for 10 minutes.</p>

                        <a href="account-recovery.php" class="bk-submit-btn" style="display:inline-block; text-decoration:none; width:auto; padding:14px 32px;">

                            Contact Us for Help

                        </a>

                    </div>

                <?php } ?>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>