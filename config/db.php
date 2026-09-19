<?php

/*====================================================
ERROR HANDLING - PRODUCTION SAFE

Never show raw PHP errors/warnings on-screen to visitors -
they can expose server file paths, database structure, and
other internals. Errors are still logged to a file so you
(the developer) can still see and debug them, just not the
public.

IMPORTANT: set $isProduction to true before deploying to
Hostinger. Keep it false while developing locally in Laragon
so you can still see errors on-screen during development.
====================================================*/

$isProduction = false; // <-- set to true before going live on Hostinger

if ($isProduction) {

    ini_set('display_errors', '0');

    ini_set('display_startup_errors', '0');

    error_reporting(E_ALL);

    ini_set('log_errors', '1');

    ini_set('error_log', __DIR__ . '/../error_log.txt');

} else {

    ini_set('display_errors', '1');

    ini_set('display_startup_errors', '1');

    error_reporting(E_ALL);

}


/*====================================================
SESSION COOKIE SECURITY

httponly: stops JavaScript (document.cookie) from ever
reading the session cookie - blocks a whole category of
session-hijacking via XSS, even if an XSS bug existed
elsewhere on the site.

secure: only sends the session cookie over HTTPS, never
plain HTTP - prevents it being sniffed on an insecure
network. This MUST stay false on localhost (Laragon has
no HTTPS), or your session will silently break locally -
only becomes true once $isProduction is true AND the site
is actually being served over HTTPS.

samesite=Lax: stops the cookie being sent on cross-site
requests initiated by other websites (a basic CSRF
mitigation), while still allowing normal navigation
(clicking a link to your site from elsewhere) to work.

IMPORTANT: this must run BEFORE session_start() is called
anywhere - ini_set() has no effect on a session that's
already started. Since this file is require_once'd at the
top of most pages, that's usually already the case, but
if any page calls session_start() before requiring this
file, this won't take effect there.
====================================================*/

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.cookie_httponly', '1');

    ini_set('session.cookie_secure', $isProduction ? '1' : '0');

    ini_set('session.cookie_samesite', 'Lax');

}

/*====================================================
DATABASE CONNECTION
====================================================*/

$host = "localhost";
$username = "root";
$password = "";
$database = "godaddy_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {

    if ($isProduction) {

        error_log("Database Connection Failed: " . mysqli_connect_error());

        die("We're experiencing a temporary issue. Please try again shortly.");

    } else {

        die("Database Connection Failed: " . mysqli_connect_error());

    }

}

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

// Set PHP timezone
date_default_timezone_set("Asia/Kolkata");