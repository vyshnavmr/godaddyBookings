<?php

require_once "config/db.php";

session_start();


if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: account-settings.php");

    exit();

}

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['password_errors'] = ["Your session has expired. Please try again."];

    header("Location: account-settings.php");

    exit();

}

$current_password = $_POST['current_password'] ?? "";

$new_password = $_POST['new_password'] ?? "";

$confirm_password = $_POST['confirm_password'] ?? "";

$errors = [];

if ($current_password === "")
    $errors[] = "Current password is required.";

if (strlen($new_password) < 8)
    $errors[] = "New password must be at least 8 characters.";

if ($new_password !== $confirm_password)
    $errors[] = "New password and confirmation do not match.";

$sql = "SELECT password FROM users WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);

mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (empty($errors) && (!$user || !password_verify($current_password, $user['password']))) {

    $errors[] = "Current password is incorrect.";

}

if (empty($errors) && password_verify($new_password, $user['password'])) {

    $errors[] = "New password must be different from your current password.";

}

if (!empty($errors)) {

    $_SESSION['password_errors'] = $errors;

    header("Location: account-settings.php");

    exit();

}

$newHash = password_hash($new_password, PASSWORD_DEFAULT);

$sql = "UPDATE users SET password = ? WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "si", $newHash, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['password_success'] = "Your password has been updated.";

} else {

    $_SESSION['password_errors'] = ["Something went wrong. Please try again."];

}

header("Location: account-settings.php");

exit();

?>