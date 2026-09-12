<?php

session_start();

/*====================================================
READ REDIRECT (OPTIONAL) - same whitelist protection as
login.php, so this can't be abused to bounce someone to
an external URL after logging out.
====================================================*/

$redirect = $_GET['redirect'] ?? "index.php";

if (preg_match('#^(https?:)?//#i', $redirect) || str_starts_with($redirect, '\\')) {
    $redirect = "index.php";
}


/*====================================================
CLEAR SESSION
====================================================*/

$_SESSION = [];

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(

        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]

    );

}

session_destroy();

header("Location: " . $redirect);

exit();

?>