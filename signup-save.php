<?php

session_start();

require_once "config/db.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: signup.php");

    exit();

}


/*====================================================
READ FORM DATA
====================================================*/

$full_name = trim($_POST['full_name'] ?? "");

$email = trim($_POST['email'] ?? "");

$phone = trim($_POST['phone'] ?? "");

$password = $_POST['password'] ?? "";

$confirm_password = $_POST['confirm_password'] ?? "";

$redirect = $_POST['redirect'] ?? "index.php";

if (preg_match('#^(https?:)?//#i', $redirect) || str_starts_with($redirect, '\\')) {
    $redirect = "index.php";
}

$signupRedirect = "signup.php?redirect=" . urlencode($redirect);


/*====================================================
CSRF CHECK
====================================================*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['signup_errors'] = ["Your session has expired. Please try again."];

    header("Location: " . $signupRedirect);

    exit();

}


/*====================================================
VALIDATE
====================================================*/

$errors = [];

if ($full_name == "")
    $errors[] = "Full name is required.";

if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = "Please enter a valid email address.";

if (!preg_match('/^[0-9]{10}$/', $phone))
    $errors[] = "Please enter a valid 10-digit mobile number.";

if (strlen($password) < 8)
    $errors[] = "Password must be at least 8 characters.";

if ($password !== $confirm_password)
    $errors[] = "Passwords do not match.";

if (!empty($errors)) {

    $_SESSION['signup_errors'] = $errors;

    header("Location: " . $signupRedirect);

    exit();

}


/*====================================================
CHECK EMAIL/PHONE UNIQUENESS
====================================================*/

$sql = "SELECT id, email, phone FROM users WHERE email=? OR phone=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $email, $phone);

mysqli_stmt_execute($stmt);

$existingMatch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($existingMatch) {

    if (strcasecmp($existingMatch['email'], $email) === 0) {

        $_SESSION['signup_errors'] = ["This email is already registered. Please log in instead."];

    } else {

        $_SESSION['signup_errors'] = ["This mobile number is already registered. Please log in instead."];

    }

    header("Location: " . $signupRedirect);

    exit();

}


/*====================================================
CREATE ACCOUNT
====================================================*/

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users(name, email, phone, password) VALUES(?,?,?,?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $hashedPassword);

if (!mysqli_stmt_execute($stmt)) {

    $_SESSION['signup_errors'] = ["Something went wrong while creating your account. Please try again."];

    header("Location: " . $signupRedirect);

    exit();

}

$user_id = mysqli_insert_id($conn);


/*====================================================
LOG THE USER IN
====================================================*/

session_regenerate_id(true);

$_SESSION['user_id'] = $user_id;

$_SESSION['user_name'] = $full_name;

unset($_SESSION['csrf_token']);

header("Location: " . $redirect);

exit();

?>