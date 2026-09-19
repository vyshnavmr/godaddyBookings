<?php

require_once "config/db.php";

session_start();


if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: account-recovery.php");

    exit();

}

$token = trim($_POST['token'] ?? "");

$new_password = $_POST['new_password'] ?? "";

$confirm_password = $_POST['confirm_password'] ?? "";

$redirectBack = "forgot-password.php?token=" . urlencode($token);

$errors = [];

if (strlen($new_password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}

if ($new_password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

if (!empty($errors)) {

    $_SESSION['reset_errors'] = $errors;

    header("Location: " . $redirectBack);

    exit();

}


/*====================================================
RE-VERIFY THE TOKEN SERVER-SIDE - never trust that the
form was rendered correctly; the token could theoretically
have expired in the seconds between page load and submit.
====================================================*/

$hashedToken = hash('sha256', $token);

$sql = "SELECT id FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $hashedToken);

mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {

    header("Location: forgot-password.php?token=" . urlencode($token));

    exit();

}


/*====================================================
UPDATE PASSWORD + INVALIDATE THE TOKEN - clearing it
immediately means the same link can't be used a second
time, even if it hasn't hit its 10-minute expiry yet.
====================================================*/

$newHash = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "si", $newHash, $user['id']);

mysqli_stmt_execute($stmt);

$_SESSION['login_errors'] = ["Your password has been updated. Please log in with your new password."];

header("Location: login.php");

exit();

?>