<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require_once "../config/db.php";

/*====================================================
HANDLE PASSWORD CHANGE
====================================================*/

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $current_password = $_POST['current_password'] ?? "";
    $new_password      = $_POST['new_password'] ?? "";
    $confirm_password  = $_POST['confirm_password'] ?? "";

    $errors = [];

    if ($current_password == "")
        $errors[] = "Current password is required.";

    if ($new_password == "")
        $errors[] = "New password is required.";

    if (strlen($new_password) < 8)
        $errors[] = "New password must be at least 8 characters.";

    if ($new_password !== $confirm_password)
        $errors[] = "New password and confirmation do not match.";

    /* Look up the admin so we can verify the current password */

    $admin = null;

    if (empty($errors)) {

        $sql = "SELECT * FROM admins WHERE id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $_SESSION['admin_id']);

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        $admin = mysqli_fetch_assoc($result);

        if (!$admin || !password_verify($current_password, $admin['password'])) {

            $errors[] = "Current password is incorrect.";

        }

    }

    if (empty($errors) && password_verify($new_password, $admin['password'])) {

        $errors[] = "New password must be different from your current password.";

    }

    if (!empty($errors)) {

        $_SESSION['errors'] = $errors;

        header("Location: change_password.php");

        exit();

    }

    /*====================================================
    UPDATE PASSWORD - also keeps the reversible encrypted
    copy in sync, but only for manager accounts, matching
    the scope documented in crypto.php.
    ====================================================*/

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

    if ((int)($admin['is_manager'] ?? 0) === 1) {

        require_once "../config/crypto.php";

        $new_encrypted = encryptManagerPassword($new_password);

        $sql = "UPDATE admins SET password=?, encrypted_password=? WHERE id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "ssi", $new_hash, $new_encrypted, $_SESSION['admin_id']);

    } else {

        $sql = "UPDATE admins SET password=? WHERE id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "si", $new_hash, $_SESSION['admin_id']);

    }

    mysqli_stmt_execute($stmt);

    $_SESSION['success'] = "Password updated successfully.";

    header("Location: change_password.php");

    exit();

}


/*====================================================
RENDER PAGE
====================================================*/

$currentPage = "";

include "includes/header.php";

?>

<style>
    .password-container {
        max-width: 480px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .password-card {
        background: #fff;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .password-card h1 {
        font-size: 22px;
        margin-bottom: 6px;
    }

    .password-card .subtitle {
        color: #888;
        font-size: 14px;
        margin-bottom: 24px;
    }

    .password-form-group {
        margin-bottom: 18px;
    }

    .password-form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .password-form-group input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Poppins', sans-serif;
        box-sizing: border-box;
    }

    .password-form-group input:focus {
        outline: none;
        border-color: #4a6cf7;
    }

    .password-hint {
        font-size: 12px;
        color: #999;
        margin-top: 4px;
    }

    .password-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .btn-update-password {
        flex: 1;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #4a6cf7;
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-update-password:hover {
        background: #3a5ce0;
    }

    .password-alert {
        padding: 12px 16px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 18px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .password-alert.success {
        background: #e7f8ee;
        color: #1e7e42;
    }

    .password-alert.error ul {
        margin: 0;
        padding-left: 18px;
    }

    .password-alert.error {
        background: #fdeceb;
        color: #c0392b;
    }
</style>

<div class="password-container">

    <div class="password-card">

        <h1>Change Password</h1>

        <p class="subtitle">Update the password used to sign in to this admin panel.</p>

        <?php if (isset($_SESSION['success'])) { ?>

            <div class="password-alert success">

                <i class="fa-solid fa-circle-check"></i>

                <?= htmlspecialchars($_SESSION['success']); ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php } ?>

        <?php if (isset($_SESSION['errors'])) { ?>

            <div class="password-alert error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <ul>

                    <?php foreach ($_SESSION['errors'] as $error) { ?>

                        <li><?= htmlspecialchars($error); ?></li>

                    <?php } ?>

                </ul>

            </div>

            <?php unset($_SESSION['errors']); ?>

        <?php } ?>

        <form method="POST">

            <div class="password-form-group">

                <label>Current Password</label>

                <input
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                    required>

            </div>

            <div class="password-form-group">

                <label>New Password</label>

                <input
                    type="password"
                    name="new_password"
                    autocomplete="new-password"
                    minlength="8"
                    required>

                <div class="password-hint">At least 8 characters.</div>

            </div>

            <div class="password-form-group">

                <label>Confirm New Password</label>

                <input
                    type="password"
                    name="confirm_password"
                    autocomplete="new-password"
                    minlength="8"
                    required>

            </div>

            <div class="password-actions">

                <button
                    type="submit"
                    class="btn-update-password">

                    <i class="fa-solid fa-key"></i>

                    Update Password

                </button>

            </div>

        </form>

    </div>

</div>

</body>

</html>