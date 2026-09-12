<?php

session_start();

require_once "config/db.php";

/*====================================================
CHECK REQUEST + LOGIN
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_SESSION['user_id'])) {

    header("Location: index.php");

    exit();

}

$property_id = (int)($_POST['property_id'] ?? 0);

$booking_id = (int)($_POST['booking_id'] ?? 0);

$rating = (int)($_POST['rating'] ?? 0);

$comment = trim($_POST['comment'] ?? "");

$redirectBack = "property-details.php?id=" . $property_id . "#reviews";


/*====================================================
CSRF CHECK
====================================================*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {

    $_SESSION['review_error'] = "Your session has expired. Please try again.";

    header("Location: " . $redirectBack);

    exit();

}


/*====================================================
VALIDATE
====================================================*/

if ($rating < 1 || $rating > 5) {

    $_SESSION['review_error'] = "Please select a rating between 1 and 5 stars.";

    header("Location: " . $redirectBack);

    exit();

}


/*====================================================
RE-VERIFY ELIGIBILITY SERVER-SIDE - never trust that the
form was rendered correctly; confirm this booking really
belongs to this user, is for this property, has a past
check-out date, isn't cancelled, and hasn't been reviewed.
====================================================*/

$sql = "

SELECT b.id

FROM bookings b

LEFT JOIN reviews r ON r.booking_id = b.id

WHERE b.id = ?

AND b.property_id = ?

AND b.user_id = ?

AND b.check_out < CURDATE()

AND b.booking_status NOT IN ('Cancelled', 'Cancellation Requested')

AND r.id IS NULL

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "iii", $booking_id, $property_id, $_SESSION['user_id']);

mysqli_stmt_execute($stmt);

$validBooking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$validBooking) {

    $_SESSION['review_error'] = "You can only review a property after completing a stay there, and only once per stay.";

    header("Location: " . $redirectBack);

    exit();

}


/*====================================================
INSERT REVIEW + RECOMPUTE CACHED AVERAGE
====================================================*/

$sql = "INSERT INTO reviews (property_id, user_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "iiiis", $property_id, $_SESSION['user_id'], $booking_id, $rating, $comment);

if (mysqli_stmt_execute($stmt)) {

    $sql = "UPDATE properties SET rating = (SELECT AVG(rating) FROM reviews WHERE property_id = ?) WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $property_id, $property_id);

    mysqli_stmt_execute($stmt);

    $_SESSION['review_message'] = "Thank you for your review!";

} else {

    $_SESSION['review_error'] = "Something went wrong submitting your review. Please try again.";

}

header("Location: " . $redirectBack);

exit();

?>