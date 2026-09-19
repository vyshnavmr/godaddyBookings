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

    $_SESSION['profile_errors'] = ["Your session has expired. Please try again."];

    header("Location: account-settings.php");

    exit();

}

$full_name = trim($_POST['full_name'] ?? "");

$email = trim($_POST['email'] ?? "");

$phone = trim($_POST['phone'] ?? "");

$errors = [];

if ($full_name === "")
    $errors[] = "Full name is required.";

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = "Please enter a valid email address.";

if (!preg_match('/^[0-9]{10}$/', $phone))
    $errors[] = "Please enter a valid 10-digit mobile number.";

if (!empty($errors)) {

    $_SESSION['profile_errors'] = $errors;

    header("Location: account-settings.php");

    exit();

}


/*====================================================
UNIQUENESS CHECK - excluding this user's own row, since
otherwise saving your own unchanged email/phone would
incorrectly flag itself as "already taken".
====================================================*/

$sql = "SELECT id, email, phone FROM users WHERE (email = ? OR phone = ?) AND id != ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ssi", $email, $phone, $_SESSION['user_id']);

mysqli_stmt_execute($stmt);

$existingMatch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($existingMatch) {

    if (strcasecmp($existingMatch['email'], $email) === 0) {

        $_SESSION['profile_errors'] = ["This email is already in use by another account."];

    } else {

        $_SESSION['profile_errors'] = ["This mobile number is already in use by another account."];

    }

    header("Location: account-settings.php");

    exit();

}


/*====================================================
UPDATE
====================================================*/

$sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $phone, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['user_name'] = $full_name;

    $_SESSION['profile_success'] = "Your details have been updated.";

} else {

    $_SESSION['profile_errors'] = ["Something went wrong. Please try again."];

}

header("Location: account-settings.php");

exit();

?>