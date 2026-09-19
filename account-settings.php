<?php

require_once "config/db.php";

session_start();


if (!isset($_SESSION['user_id'])) {

    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));

    exit();

}

$sql = "SELECT id, name, email, phone FROM users WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);

mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {

    header("Location: logout.php");

    exit();

}


/*====================================================
CSRF TOKEN
====================================================*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}


/*====================================================
FLASH MESSAGES
====================================================*/

$profileErrors = [];
$profileSuccess = null;

$passwordErrors = [];
$passwordSuccess = null;

if (isset($_SESSION['profile_errors'])) {
    $profileErrors = $_SESSION['profile_errors'];
    unset($_SESSION['profile_errors']);
}

if (isset($_SESSION['profile_success'])) {
    $profileSuccess = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

if (isset($_SESSION['password_errors'])) {
    $passwordErrors = $_SESSION['password_errors'];
    unset($_SESSION['password_errors']);
}

if (isset($_SESSION['password_success'])) {
    $passwordSuccess = $_SESSION['password_success'];
    unset($_SESSION['password_success']);
}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Account Settings - Godaddy Booking";

$currentPage = "account-settings";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="section-heading" style="margin-bottom:35px;">

            <div class="eyebrow">Your Account</div>

            <h2>Account Settings</h2>

            <p>Manage your personal details and password.</p>

        </div>

        <div class="bk-layout" style="grid-template-columns:1fr; max-width:760px; margin:0 auto;">

            <!-- ===========================
                 PROFILE DETAILS
            ============================ -->

            <div class="bk-main">

                <div class="bk-card" style="margin-bottom:24px;">

                    <div class="bk-card-header">

                        <h2>Profile Details</h2>

                    </div>

                    <div class="bk-card-body">

                        <?php if ($profileSuccess) { ?>

                            <div class="bk-alert bk-alert-success"><?= htmlspecialchars($profileSuccess); ?></div>

                        <?php } ?>

                        <?php if (!empty($profileErrors)) { ?>

                            <div class="bk-alert bk-alert-error">

                                <ul>

                                    <?php foreach ($profileErrors as $error) { ?>

                                        <li><?= htmlspecialchars($error); ?></li>

                                    <?php } ?>

                                </ul>

                            </div>

                        <?php } ?>

                        <form action="account-settings-save.php" method="POST">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="bk-form-row">

                                <div class="bk-form-group">

                                    <label>Full Name</label>

                                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['name']); ?>" required>

                                </div>

                                <div class="bk-form-group">

                                    <label>Email Address</label>

                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>

                                </div>

                            </div>

                            <div class="bk-form-group" style="margin-bottom:20px;">

                                <label>Mobile Number</label>

                                <div class="bk-phone-group">

                                    <span class="bk-phone-prefix">+91</span>

                                    <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']); ?>" pattern="[0-9]{10}" required>

                                </div>

                            </div>

                            <button type="submit" class="bk-submit-btn" style="width:auto; padding:14px 32px;">Save Changes</button>

                        </form>

                    </div>

                </div>

                <!-- ===========================
                     CHANGE PASSWORD
                ============================ -->

                <div class="bk-card">

                    <div class="bk-card-header">

                        <h2>Change Password</h2>

                    </div>

                    <div class="bk-card-body">

                        <?php if ($passwordSuccess) { ?>

                            <div class="bk-alert bk-alert-success"><?= htmlspecialchars($passwordSuccess); ?></div>

                        <?php } ?>

                        <?php if (!empty($passwordErrors)) { ?>

                            <div class="bk-alert bk-alert-error">

                                <ul>

                                    <?php foreach ($passwordErrors as $error) { ?>

                                        <li><?= htmlspecialchars($error); ?></li>

                                    <?php } ?>

                                </ul>

                            </div>

                        <?php } ?>

                        <form action="account-password-save.php" method="POST">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <div class="bk-form-group" style="margin-bottom:20px;">

                                <label>Current Password</label>

                                <input type="password" name="current_password" placeholder="Enter your current password" required>

                            </div>

                            <div class="bk-form-row">

                                <div class="bk-form-group">

                                    <label>New Password</label>

                                    <input type="password" name="new_password" placeholder="At least 8 characters" minlength="8" required>

                                </div>

                                <div class="bk-form-group">

                                    <label>Confirm New Password</label>

                                    <input type="password" name="confirm_password" placeholder="Re-enter new password" minlength="8" required>

                                </div>

                            </div>

                            <button type="submit" class="bk-submit-btn" style="width:auto; padding:14px 32px;">Update Password</button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>