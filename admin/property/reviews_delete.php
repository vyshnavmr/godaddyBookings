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

if ($review_id <= 0) {

    echo json_encode(["success" => false, "message" => "Invalid review."]);

    exit();

}


/*====================================================
FETCH REVIEW - need property_id to recompute the average
rating afterward, and to enforce manager scoping
====================================================*/

$sql = "SELECT property_id FROM reviews WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $review_id);

mysqli_stmt_execute($stmt);

$review = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$review) {

    echo json_encode(["success" => false, "message" => "Review not found."]);

    exit();

}


/*====================================================
MANAGERS CAN ONLY DELETE REVIEWS FOR THEIR OWN PROPERTIES
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
DELETE + RECOMPUTE THE PROPERTY'S CACHED AVERAGE RATING
====================================================*/

mysqli_begin_transaction($conn);

try {

    $sql = "DELETE FROM reviews WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $review_id);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception("Failed to delete review.");

    }

    $property_id = (int)$review['property_id'];

    $sql = "UPDATE properties SET rating = (SELECT AVG(rating) FROM reviews WHERE property_id = ?) WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $property_id, $property_id);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception("Failed to update property rating.");

    }

    mysqli_commit($conn);

    echo json_encode(["success" => true]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode(["success" => false, "message" => $e->getMessage()]);

}

?>