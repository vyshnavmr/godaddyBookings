<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['admin_id'])) {

    http_response_code(403);

    echo json_encode(["success" => false, "message" => "Not logged in."]);

    exit();

}

require_once "../../config/db.php";

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    echo json_encode(["success" => false, "message" => "Invalid request."]);

    exit();

}

$review_id = (int)($_POST['review_id'] ?? 0);

$is_highlighted = ((int)($_POST['is_highlighted'] ?? 0) === 1) ? 1 : 0;

if ($review_id <= 0) {

    echo json_encode(["success" => false, "message" => "Invalid request."]);

    exit();

}


/*====================================================
CHECK REVIEW EXISTS + WHICH PROPERTY IT BELONGS TO
====================================================*/

$sql = "SELECT property_id FROM reviews WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $review_id);

mysqli_stmt_execute($stmt);

$review = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$review) {

    echo json_encode(["success" => false, "message" => "Review not found."]);

    exit();

}


/*====================================================
MANAGERS CAN ONLY EDIT REVIEWS FOR THEIR OWN PROPERTIES
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array((int)$review['property_id'], $managerPropertyIds)) {

        http_response_code(403);

        echo json_encode(["success" => false, "message" => "This review isn't for one of your properties."]);

        exit();

    }

}


/*====================================================
UPDATE
====================================================*/

$sql = "UPDATE reviews SET is_highlighted=? WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $is_highlighted, $review_id);

if (mysqli_stmt_execute($stmt)) {

    echo json_encode(["success" => true]);

} else {

    echo json_encode(["success" => false, "message" => "Database error while updating."]);

}

?>