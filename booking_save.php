<?php

session_start();

require_once "config/db.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: properties.php");

    exit();

}


/*====================================================
READ FORM DATA
====================================================*/

$property_id = (int)($_POST['property_id'] ?? 0);

$room_id = (int)($_POST['room_id'] ?? 0);

$check_in = trim($_POST['check_in'] ?? "");

$check_out = trim($_POST['check_out'] ?? "");

$guests = (int)($_POST['guests'] ?? 1);

$full_name = trim($_POST['full_name'] ?? "");

$email = trim($_POST['email'] ?? "");

$phone = trim($_POST['phone'] ?? "");

$password = $_POST['password'] ?? "";

$bookingRedirect = "booking.php?property_id=$property_id&room_id=$room_id&check_in=" .
    urlencode($check_in) . "&check_out=" . urlencode($check_out) . "&guests=$guests";


/*====================================================
VALIDATE BASIC FIELDS
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

if ($property_id <= 0 || $room_id <= 0 || $check_in == "" || $check_out == "") {
    $errors[] = "Missing booking details. Please start your booking again.";
}

if (!empty($errors)) {

    $_SESSION['booking_errors'] = $errors;

    header("Location: " . $bookingRedirect);

    exit();

}


/*====================================================
RE-FETCH PROPERTY + ROOM SERVER-SIDE

Never trust price/date math submitted by the client - all of
this is recalculated fresh here, the same way booking.php
computed it for display.
====================================================*/

$sql = "SELECT id FROM properties WHERE id=? AND status='Available'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $property_id);

mysqli_stmt_execute($stmt);

$property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$sql = "SELECT * FROM property_rooms WHERE id=? AND property_id=? AND status='Available'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $room_id, $property_id);

mysqli_stmt_execute($stmt);

$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$property || !$room) {

    $_SESSION['booking_errors'] = ["This room is no longer available. Please choose another."];

    header("Location: property-details.php?id=" . $property_id);

    exit();

}

$inDate = DateTime::createFromFormat("Y-m-d", $check_in);

$outDate = DateTime::createFromFormat("Y-m-d", $check_out);

if (!$inDate || !$outDate || $outDate <= $inDate) {

    $_SESSION['booking_errors'] = ["Invalid check-in/check-out dates."];

    header("Location: " . $bookingRedirect);

    exit();

}

$nights = $outDate->diff($inDate)->days;

$discountedRate = $room['discounted_price'] ?? $room['price'];

$totalPrice = $discountedRate * $nights;


/*====================================================
CHECK EMAIL UNIQUENESS
====================================================*/

$sql = "SELECT id FROM users WHERE email=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "s", $email);

mysqli_stmt_execute($stmt);

if (mysqli_num_rows(mysqli_stmt_get_result($stmt)) > 0) {

    $_SESSION['booking_errors'] = ["This email is already registered. Please log in instead."];

    header("Location: " . $bookingRedirect);

    exit();

}


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

try {

    /*====================================================
    CREATE USER ACCOUNT
    ====================================================*/

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users(name, email, phone, password) VALUES(?,?,?,?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $hashedPassword);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }

    $user_id = mysqli_insert_id($conn);


    /*====================================================
    CREATE BOOKING
    ====================================================*/

    $booking_status = "Pending";

    $payment_status = "Unpaid";

    $sql = "

    INSERT INTO bookings(

    property_id,

    room_id,

    user_name,

    user_id,

    check_in,

    check_out,

    guests,

    total_price,

    booking_status,

    payment_status

    )

    VALUES(

    ?,?,?,?,?,?,?,?,?,?

    )

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(

        $stmt,

        "iisissidss",

        $property_id,
        $room_id,
        $full_name,
        $user_id,
        $check_in,
        $check_out,
        $guests,
        $totalPrice,
        $booking_status,
        $payment_status

    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }

    $booking_id = mysqli_insert_id($conn);


    /*====================================================
    COMMIT + LOG THE USER IN
    ====================================================*/

    mysqli_commit($conn);

    $_SESSION['user_id'] = $user_id;

    $_SESSION['user_name'] = $full_name;

    header("Location: index.php?booking_confirmed=1");

    exit();

}


/*====================================================
ROLLBACK
====================================================*/

catch (Exception $e) {

    mysqli_rollback($conn);

    $_SESSION['booking_errors'] = ["Something went wrong while creating your booking. Please try again."];

    header("Location: " . $bookingRedirect);

    exit();

}

?>