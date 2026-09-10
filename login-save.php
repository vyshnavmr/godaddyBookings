<?php

session_start();

require_once "config/db.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: login.php");

    exit();

}


/*====================================================
READ FORM DATA
====================================================*/

$identifier = trim($_POST['identifier'] ?? "");

$password = $_POST['password'] ?? "";

$redirect = $_POST['redirect'] ?? "index.php";

/* Same open-redirect protection as login.php */

if (preg_match('#^(https?:)?//#i', $redirect) || str_starts_with($redirect, '\\')) {
    $redirect = "index.php";
}

$loginRedirect = "login.php?redirect=" . urlencode($redirect);


/*====================================================
CSRF CHECK
====================================================*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['login_errors'] = ["Your session has expired. Please try again."];

    header("Location: " . $loginRedirect);

    exit();

}


/*====================================================
RATE LIMITING (session-based)
====================================================*/

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (!isset($_SESSION['login_last_attempt'])) {
    $_SESSION['login_last_attempt'] = 0;
}

$maxAttempts   = 5;
$lockoutWindow = 60; // seconds

if ((time() - $_SESSION['login_last_attempt']) >= $lockoutWindow) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SESSION['login_attempts'] >= $maxAttempts) {

    $_SESSION['login_errors'] = ["Too many login attempts. Please wait a minute and try again."];

    header("Location: " . $loginRedirect);

    exit();

}


/*====================================================
VALIDATE
====================================================*/

if ($identifier == "" || $password == "") {

    $_SESSION['login_errors'] = ["Please enter your email/mobile number and password."];

    header("Location: " . $loginRedirect);

    exit();

}


/*====================================================
LOOK UP USER - by email OR phone
====================================================*/

$sql = "SELECT * FROM users WHERE email=? OR phone=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $identifier, $identifier);

mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user || !password_verify($password, $user['password'])) {

    $_SESSION['login_attempts']++;

    $_SESSION['login_last_attempt'] = time();

    $_SESSION['login_errors'] = ["Incorrect email/mobile number or password."];

    header("Location: " . $loginRedirect);

    exit();

}


/*====================================================
LOG THE USER IN
====================================================*/

// Prevent session fixation

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];

$_SESSION['user_name'] = $user['name'];

unset($_SESSION['login_attempts']);

unset($_SESSION['login_last_attempt']);

unset($_SESSION['csrf_token']);

header("Location: " . $redirect);

exit();

?>