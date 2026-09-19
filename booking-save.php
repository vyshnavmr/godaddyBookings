<?php

require_once "config/db.php";

session_start();


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

$rooms = (int)($_POST['rooms'] ?? 1);

/*====================================================
CLAMP GUESTS/ROOMS - the client-side <select> dropdowns
only ever offer 1-5, but this endpoint can be POSTed to
directly, so the same bounds are enforced here too.
====================================================*/

if ($guests < 1) {
    $guests = 1;
}

if ($guests > 20) {
    $guests = 20;
}

if ($rooms < 1) {
    $rooms = 1;
}

if ($rooms > 10) {
    $rooms = 10;
}

$bookingRedirect = "booking.php?property_id=$property_id&room_id=$room_id&check_in=" .
    urlencode($check_in) . "&check_out=" . urlencode($check_out) . "&guests=$guests&rooms=$rooms";

/*====================================================
CSRF CHECK
====================================================*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['booking_errors'] = ["Your session has expired. Please try again."];

    header("Location: " . $bookingRedirect);

    exit();

}



/*====================================================
LOGGED-IN USER? - if their session points at a real
account, skip registration entirely and book as them.
====================================================*/

$existingUser = null;

if (isset($_SESSION['user_id'])) {

    $sql = "SELECT id, name, email, phone FROM users WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);

    mysqli_stmt_execute($stmt);

    $existingUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$existingUser) {

        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);

    }

}

$isLoggedIn = $existingUser !== null;


/*====================================================
VALIDATE BASIC FIELDS

Guest checkout needs name/email/phone/password. Logged-in
users already have all of this on file - skip straight to
the booking-level checks.
====================================================*/

$errors = [];

if ($isLoggedIn) {

    $full_name = $existingUser['name'];
    $email = $existingUser['email'];
    $phone = $existingUser['phone'];

} else {

    $full_name = trim($_POST['full_name'] ?? "");

    $email = trim($_POST['email'] ?? "");

    $phone = trim($_POST['phone'] ?? "");

    $password = $_POST['password'] ?? "";

    if ($full_name == "")
        $errors[] = "Full name is required.";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Please enter a valid email address.";

    if (!preg_match('/^[0-9]{10}$/', $phone))
        $errors[] = "Please enter a valid 10-digit mobile number.";

    if (strlen($password) < 8)
        $errors[] = "Password must be at least 8 characters.";

}

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


/*====================================================
VALIDATE GUESTS AGAINST THIS ROOM'S ACTUAL CAPACITY -
the early clamp only guards against garbage/absurd values;
this checks the real per-room limit now that $room is loaded.
====================================================*/

$maxGuestsForRoom = (int)$room['max_guests'];

$maxTotalGuests = $maxGuestsForRoom * $rooms;

if ($maxGuestsForRoom > 0 && $guests > $maxTotalGuests) {

    $_SESSION['booking_errors'] = [

        "This room type allows a maximum of $maxGuestsForRoom guest" . ($maxGuestsForRoom > 1 ? 's' : '') . " per room ($maxTotalGuests total for $rooms room" . ($rooms > 1 ? 's' : '') . "). Please adjust your guest count."

    ];

    header("Location: " . $bookingRedirect);

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


/*====================================================
CHECK ROOM AVAILABILITY - date-aware. Sums rooms already
booked (excluding Cancelled) for this room type across any
booking whose dates overlap the requested check-in/check-out,
then checks that against total_rooms.
====================================================*/

$totalRoomsOfType = (int)($room['total_rooms'] ?? 0);

$sql = "

SELECT COALESCE(SUM(rooms), 0) AS booked_rooms

FROM bookings

WHERE room_id = ?

AND booking_status NOT IN ('Cancelled')

AND check_in < ?

AND check_out > ?

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "iss", $room_id, $check_out, $check_in);

mysqli_stmt_execute($stmt);

$overlapRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$alreadyBooked = (int)$overlapRow['booked_rooms'];

$availableForTheseDates = $totalRoomsOfType - $alreadyBooked;

if ($rooms > $availableForTheseDates) {

    $_SESSION['booking_errors'] = [

        $availableForTheseDates > 0

            ? "Only $availableForTheseDates room" . ($availableForTheseDates > 1 ? 's are' : ' is') . " available for these dates. Please reduce the number of rooms or choose different dates."

            : "This room type is fully booked for these dates. Please choose different dates."

    ];

    header("Location: " . $bookingRedirect);

    exit();

}


$discountedRate = $room['discounted_price'] ?? $room['price'];

$totalPrice = $discountedRate * $nights * $rooms;


/*====================================================
CHECK EMAIL/PHONE UNIQUENESS - guest checkout only, since
a logged-in user is obviously already registered. Checks
both fields, matching how login.php looks users up (by
email OR phone) - otherwise two guest bookings with the
same phone but different emails could create two accounts
sharing one phone number.
====================================================*/

if (!$isLoggedIn) {

    $sql = "SELECT id, email, phone FROM users WHERE email=? OR phone=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ss", $email, $phone);

    mysqli_stmt_execute($stmt);

    $existingMatch = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existingMatch) {

        if (strcasecmp($existingMatch['email'], $email) === 0) {

            $_SESSION['booking_errors'] = ["This email is already registered. Please log in instead."];

        } else {

            $_SESSION['booking_errors'] = ["This mobile number is already registered. Please log in instead."];

        }

        header("Location: " . $bookingRedirect);

        exit();

    }

}


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

try {

    /*====================================================
    CREATE USER ACCOUNT - skipped entirely for logged-in
    users, who already have a user_id from their session.
    ====================================================*/

    if ($isLoggedIn) {

        $user_id = $existingUser['id'];

    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users(name, email, phone, password) VALUES(?,?,?,?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "ssss", $full_name, $email, $phone, $hashedPassword);

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(mysqli_error($conn));

        }

        $user_id = mysqli_insert_id($conn);

    }


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

    rooms,

    total_price,

    booking_status,

    payment_status

    )

    VALUES(

    ?,?,?,?,?,?,?,?,?,?,?

    )

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(

        $stmt,

        "iisissiidss",

        $property_id,
        $room_id,
        $full_name,
        $user_id,
        $check_in,
        $check_out,
        $guests,
        $rooms,
        $totalPrice,
        $booking_status,
        $payment_status

    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }

    $booking_id = mysqli_insert_id($conn);


    /*====================================================
    COMMIT

    Logged-in users already have a session - nothing to
    set. Guests get logged in as the account we just made.
    ====================================================*/

    mysqli_commit($conn);

    unset($_SESSION['csrf_token']);

    if (!$isLoggedIn) {

        $_SESSION['user_id'] = $user_id;

        $_SESSION['user_name'] = $full_name;

    }

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