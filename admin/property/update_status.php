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


/*====================================================
VALIDATE FIELD + VALUE
====================================================*/

$booking_id = (int)($_POST['booking_id'] ?? 0);

$field = trim($_POST['field'] ?? "");

$value = trim($_POST['value'] ?? "");

$allowedFields = [

    "booking_status" => ["Booked", "Pending", "Cancellation Requested", "Cancelled"],

    "payment_status" => ["Paid", "Unpaid", "Refunded"]

];

if ($booking_id <= 0 || !array_key_exists($field, $allowedFields)) {

    echo json_encode(["success" => false, "message" => "Invalid request."]);

    exit();

}

if (!in_array($value, $allowedFields[$field])) {

    echo json_encode(["success" => false, "message" => "Invalid value for $field."]);

    exit();

}


/*====================================================
FETCH THE FULL BOOKING - not just property_id, since a
booking_status change to/from Cancelled needs room_id,
rooms, and the CURRENT status to decide whether to
restock or un-restock property_rooms.rooms_available
====================================================*/

$sql = "SELECT property_id FROM bookings WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $booking_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$booking = mysqli_fetch_assoc($result);

if (!$booking) {

    echo json_encode(["success" => false, "message" => "Booking not found."]);

    exit();

}


/*====================================================
MANAGERS CAN ONLY EDIT BOOKINGS FOR THEIR OWN PROPERTIES
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array((int)$booking['property_id'], $managerPropertyIds)) {

        http_response_code(403);

        echo json_encode(["success" => false, "message" => "This booking isn't for one of your properties."]);

        exit();

    }

}


/*====================================================
UPDATE - column name comes from a whitelist above, never
directly from user input, so this stays injection-safe
even though it can't be a bound parameter
====================================================*/

mysqli_begin_transaction($conn);

try {

    if ($field === "booking_status") {

        $sql = "UPDATE bookings SET booking_status=? WHERE id=?";

    } else {

        $sql = "UPDATE bookings SET payment_status=? WHERE id=?";

    }

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "si", $value, $booking_id);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception("Database error while updating.");

    }

    mysqli_commit($conn);

    echo json_encode(["success" => true]);

}

catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode(["success" => false, "message" => $e->getMessage()]);

}

?>