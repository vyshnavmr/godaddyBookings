<?php

session_start();

require_once "config/db.php";

$token = trim($_GET['token'] ?? "");

$errors = [];

$success = "";

$resetLink = "";

$showResetForm = false;


/*====================================================
STAGE 1 - REQUEST A RESET (no token in URL)
====================================================*/

if ($token == "" && $_SERVER["REQUEST_METHOD"] == "POST") {

    $identifier = trim($_POST['identifier'] ?? "");

    if ($identifier == "") {

        $errors[] = "Please enter your email or mobile number.";

    } else {

        $sql = "SELECT * FROM users WHERE email=? OR phone=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);

        mysqli_stmt_execute($stmt);

        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        /* Always show the same message whether or not the account
           exists, so this can't be used to check which emails/phone
           numbers are registered. */

        $success = "If an account matches those details, a reset link has been generated below.";

        if ($user) {

            $newToken = bin2hex(random_bytes(32));

            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $sql = "UPDATE users SET reset_token=?, reset_token_expiry=? WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "ssi", $newToken, $expiry, $user['id']);

            mysqli_stmt_execute($stmt);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";

            $resetLink = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/forgot-password.php?token=" . $newToken;

        }

    }

}


/*====================================================
STAGE 2 - SET NEW PASSWORD (token present in URL)
====================================================*/

if ($token != "") {

    $sql = "SELECT * FROM users WHERE reset_token=? AND reset_token_expiry > NOW()";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $token);

    mysqli_stmt_execute($stmt);

    $resetUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$resetUser) {

        $errors[] = "This reset link is invalid or has expired. Please request a new one.";

    } else {

        $showResetForm = true;

        if ($_SERVER["REQUEST_METHOD"] == "POST") {

            $newPassword = $_POST['new_password'] ?? "";

            $confirmPassword = $_POST['confirm_password'] ?? "";

            if (strlen($newPassword) < 8) {

                $errors[] = "Password must be at least 8 characters.";

            } elseif ($newPassword !== $confirmPassword) {

                $errors[] = "Passwords do not match.";

            } else {

                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $sql = "UPDATE users SET password=?, reset_token=NULL, reset_token_expiry=NULL WHERE id=?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $resetUser['id']);

                mysqli_stmt_execute($stmt);

                $_SESSION['login_errors'] = null;

                unset($_SESSION['login_errors']);

                header("Location: login.php?reset=success");

                exit();

            }

        }

    }

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Reset Password - Godaddy Booking";

$currentPage = "";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap">

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2><?= $showResetForm ? 'Set a New Password' : 'Forgot Password'; ?></h2>

                </div>

                <div class="bk-card-body">

                    <?php if (!empty($errors)) { ?>

                        <div class="bk-alert bk-alert-error">

                            <ul>

                                <?php foreach ($errors as $error) { ?>

                                    <li><?= htmlspecialchars($error); ?></li>

                                <?php } ?>

                            </ul>

                        </div>

                    <?php } ?>

                    <?php if ($success) { ?>

                        <div class="bk-alert bk-alert-success"><?= htmlspecialchars($success); ?></div>

                    <?php } ?>

                    <?php if ($resetLink) { ?>

                        <div class="bk-alert bk-alert-info">

                            No email system is connected yet, so here's your reset link directly
                            (in production this would be emailed instead):

                            <br><br>

                            <a href="<?= htmlspecialchars($resetLink); ?>"><?= htmlspecialchars($resetLink); ?></a>

                        </div>

                    <?php } ?>

                    <?php if ($showResetForm) { ?>

                        <form action="forgot-password.php?token=<?= htmlspecialchars($token); ?>" method="POST">

                            <div class="bk-form-group">

                                <label>New Password</label>

                                <input type="password" name="new_password" placeholder="At least 8 characters" minlength="8" required>

                            </div>

                            <div class="bk-form-group">

                                <label>Confirm New Password</label>

                                <input type="password" name="confirm_password" placeholder="Re-enter new password" minlength="8" required>

                            </div>

                            <button type="submit" class="bk-submit-btn">Update Password</button>

                        </form>

                    <?php } elseif (!$token) { ?>

                        <p class="bk-card-subtitle">Enter your email or mobile number and we'll generate a reset link.</p>

                        <form action="forgot-password.php" method="POST">

                            <div class="bk-form-group">

                                <label>Email or Mobile Number</label>

                                <input type="text" name="identifier" placeholder="name@abc.com or 10-digit number" required>

                            </div>

                            <button type="submit" class="bk-submit-btn">Send Reset Link</button>

                        </form>

                    <?php } ?>

                    <p class="bk-login-prompt bk-no-border">

                        <a href="login.php">Back to Log In</a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>