<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['admin_id'])) {

    http_response_code(403);

    echo json_encode(["success" => false, "message" => "Not logged in."]);

    exit();

}

/* Only the main admin can issue reset links */

if (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1) {

    http_response_code(403);

    echo json_encode(["success" => false, "message" => "Not authorized."]);

    exit();

}

require_once "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    echo json_encode(["success" => false, "message" => "Invalid request."]);

    exit();

}

$user_id = (int)($_POST['user_id'] ?? 0);

if ($user_id <= 0) {

    echo json_encode(["success" => false, "message" => "Invalid user."]);

    exit();

}

$sql = "SELECT id FROM users WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user) {

    echo json_encode(["success" => false, "message" => "User not found."]);

    exit();

}


/*====================================================
GENERATE TOKEN - the plain token goes into the shareable
link; only its hash is stored, so a database leak alone
can never be used to reset someone's password.
====================================================*/

$plainToken = bin2hex(random_bytes(32));

$hashedToken = hash('sha256', $plainToken);

$expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

$sql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ssi", $hashedToken, $expiry, $user_id);

if (!mysqli_stmt_execute($stmt)) {

    echo json_encode(["success" => false, "message" => "Database error while generating link."]);

    exit();

}

$baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . str_replace('/admin/property', '', $_SERVER['HTTP_HOST'] . dirname(dirname(dirname($_SERVER['PHP_SELF']))));

/* Build the link relative to the site root, two levels up from
   /admin/property/ - keeping this simple and explicit rather than
   guessing paths dynamically in a fragile way */

$resetLink = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/WEBSITE/forgot-password.php?token=" . $plainToken;

echo json_encode([

    "success" => true,

    "link" => $resetLink,

    "expires_at" => date("g:i A", strtotime($expiry))

]);

?>