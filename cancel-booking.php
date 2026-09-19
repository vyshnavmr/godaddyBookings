<?php

require_once "config/db.php";

session_start();


/*====================================================
CHECK REQUEST + LOGIN
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['user_id'])) {

    header("Location: current-booking.php");

    exit();

}


/*====================================================
CSRF CHECK
====================================================*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['cancel_error'] = "Your session has expired. Please try again.";

    header("Location: current-booking.php");

    exit();

}


/*====================================================
FETCH BOOKING - ownership check via user_id in the
WHERE clause, not just booking_id, so no one can cancel
someone else's booking by guessing an id.
====================================================*/

$booking_id = (int)($_POST['booking_id'] ?? 0);

$sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);

mysqli_stmt_execute($stmt);

$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$booking) {

    $_SESSION['cancel_error'] = "Booking not found.";

    header("Location: current-booking.php");

    exit();

}


/*====================================================
VALIDATE - status + 24 hour cutoff, re-checked here
server-side even though the button is already hidden
client-side once these conditions fail.
====================================================*/

$statusLower = strtolower($booking['booking_status'] ?? '');

if (in_array($statusLower, ['cancelled', 'cancellation requested'])) {

    $_SESSION['cancel_error'] = "This booking has already been cancelled or a cancellation is already pending.";

    header("Location: current-booking.php");

    exit();

}

$inDate = DateTime::createFromFormat("Y-m-d", $booking['check_in']);

if (!$inDate) {

    $_SESSION['cancel_error'] = "Something went wrong reading this booking's dates.";

    header("Location: current-booking.php");

    exit();

}

$checkInMidnight = clone $inDate;

$checkInMidnight->setTime(0, 0, 0);

$now = new DateTime();

$hoursUntilCheckIn = ($checkInMidnight->getTimestamp() - $now->getTimestamp()) / 3600;

if ($hoursUntilCheckIn < 24) {

    $_SESSION['cancel_error'] = "Cancellations must be requested at least 24 hours before check-in.";

    header("Location: current-booking.php");

    exit();

}


/*====================================================
UPDATE STATUS TO "Cancellation Requested"

NOTE: this does not restock rooms_available or fully
cancel the booking - that should happen when an admin
actually approves the cancellation, in whichever file
manages bookings on the admin side.
====================================================*/

$newStatus = "Cancellation Requested";

$sql = "UPDATE bookings SET booking_status = ? WHERE id = ? AND user_id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "sii", $newStatus, $booking_id, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['cancel_message'] = "Your cancellation request has been submitted. We'll confirm it shortly.";

} else {

    $_SESSION['cancel_error'] = "Something went wrong. Please try again.";

}

header("Location: current-booking.php");

exit();

?>