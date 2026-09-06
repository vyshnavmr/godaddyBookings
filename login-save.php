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

    $_SESSION['login_errors'] = ["Incorrect email/mobile number or password."];

    header("Location: " . $loginRedirect);

    exit();

}


/*====================================================
LOG THE USER IN
====================================================*/

$_SESSION['user_id'] = $user['id'];

$_SESSION['user_name'] = $user['name'];

header("Location: " . $redirect);

exit();

?>