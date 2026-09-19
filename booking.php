<?php

require_once "config/db.php";

session_start();


/*====================================================
READ BOOKING PARAMS
====================================================*/

$property_id = (int)($_GET['property_id'] ?? 0);

$room_id = (int)($_GET['room_id'] ?? 0);

$check_in = trim($_GET['check_in'] ?? "");

$check_out = trim($_GET['check_out'] ?? "");

$guests = (int)($_GET['guests'] ?? 1);

$rooms = (int)($_GET['rooms'] ?? 1);

if ($rooms < 1) {
    $rooms = 1;
}

/*====================================================
CSRF TOKEN
====================================================*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}

$errors = [];

$formErrors = [];

if (isset($_SESSION['booking_errors'])) {

    $formErrors = $_SESSION['booking_errors'];

    unset($_SESSION['booking_errors']);

}

if ($property_id <= 0 || $room_id <= 0) {

    header("Location: properties.php");

    exit();

}

if ($check_in == "" || $check_out == "") {
    $errors[] = "Please select both a check-in and check-out date.";
}


/*====================================================
LOGGED-IN USER? - if so, skip the registration form
and book directly against their existing account.
====================================================*/

$loggedInUser = null;

if (isset($_SESSION['user_id'])) {

    $sql = "SELECT id, name, email, phone FROM users WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);

    mysqli_stmt_execute($stmt);

    $loggedInUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    /* Session pointed at a user_id that no longer exists in
       the DB - treat as logged out rather than trusting it */

    if (!$loggedInUser) {

        unset($_SESSION['user_id']);
        unset($_SESSION['user_name']);

    }

}


/*====================================================
FETCH PROPERTY + ROOM
====================================================*/

$sql = "

SELECT

p.id, p.title, p.property_code, d.destination_name

FROM properties p

INNER JOIN destinations d ON p.destination_id = d.id

WHERE p.id = ?

AND p.status = 'Available'

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $property_id);

mysqli_stmt_execute($stmt);

$property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$sql = "SELECT * FROM property_rooms WHERE id = ? AND property_id = ? AND status = 'Available'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ii", $room_id, $property_id);

mysqli_stmt_execute($stmt);

$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$property || !$room) {

    header("Location: properties.php");

    exit();

}

$sql = "SELECT image_path FROM property_room_images WHERE room_id=? AND is_cover=1 LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $room_id);

mysqli_stmt_execute($stmt);

$coverRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$roomImage = $coverRow
    ? "assets/uploads/" . $coverRow['image_path']
    : "https://picsum.photos/300/200?random=room" . $room_id;


/*====================================================
CALCULATE NIGHTS + PRICE BREAKDOWN
====================================================*/

$nights = 1;

$validDates = false;

if ($check_in != "" && $check_out != "") {

    $inDate = DateTime::createFromFormat("Y-m-d", $check_in);

    $outDate = DateTime::createFromFormat("Y-m-d", $check_out);

    if ($inDate && $outDate && $outDate > $inDate) {

        $nights = $outDate->diff($inDate)->days;

        $validDates = true;

    } else {

        $errors[] = "Check-out date must be after check-in date.";

    }

}

$roomRate = (float)$room['price'];

$discountPercent = (float)($room['discount_percent'] ?? 0);

$discountedRate = $room['discounted_price'] ?? $roomRate;

$subtotal = $roomRate * $nights * $rooms;

$discountAmount = $subtotal - ($discountedRate * $nights * $rooms);

$payableAmount = $discountedRate * $nights * $rooms;


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Complete Your Booking - Godaddy Booking";

$currentPage = "";

$pageCss = "assets/css/booking.css";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <a href="property-details.php?id=<?= $property_id; ?>" class="bk-back-link">

            <i class="fa-solid fa-chevron-left"></i> Modify your booking

        </a>

        <div class="bk-layout">

            <!-- ===========================
                 DETAILS FORM
            ============================ -->

            <div class="bk-main">

                <?php if (!empty($errors)) { ?>

                    <div class="bk-alert bk-alert-error">

                        <ul>

                            <?php foreach ($errors as $error) { ?>

                                <li><?= htmlspecialchars($error); ?></li>

                            <?php } ?>

                        </ul>

                        <a href="property-details.php?id=<?= $property_id; ?>">Go back and choose your dates</a>

                    </div>

                <?php } elseif ($discountAmount > 0) { ?>

                    <div class="bk-alert bk-alert-success">

                        🎉 You just saved ₹<?= number_format($discountAmount, 0); ?> on this booking!

                    </div>

                <?php } ?>

                <?php if (!empty($formErrors)) { ?>

                    <div class="bk-alert bk-alert-error">

                        <ul>

                            <?php foreach ($formErrors as $error) { ?>

                                <li><?= htmlspecialchars($error); ?></li>

                            <?php } ?>

                        </ul>

                    </div>

                <?php } ?>

                <div class="bk-card">

                    <div class="bk-card-header">

                        <span class="bk-step-number">1</span>

                        <h2>Enter Your Details</h2>

                    </div>

                    <div class="bk-card-body">

                        <?php if ($loggedInUser) { ?>

                            <p class="bk-card-subtitle">Confirm your details before booking.</p>

                            <p class="bk-login-prompt">

                                Booking as <strong><?= htmlspecialchars($loggedInUser['name']); ?></strong>

                                (<?= htmlspecialchars($loggedInUser['email']); ?>).

                                Not you?

                                <a href="logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Log out</a>

                            </p>

                        <?php } else { ?>

                            <p class="bk-card-subtitle">We'll use these details to share your booking information.</p>

                            <p class="bk-login-prompt">

                                Already registered?

                                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Log in</a>

                                instead.

                            </p>

                        <?php } ?>

                        <form action="booking-save.php" method="POST" id="bookingDetailsForm">

                            <input type="hidden" name="property_id" value="<?= $property_id; ?>">

                            <input type="hidden" name="room_id" value="<?= $room_id; ?>">

                            <input type="hidden" name="check_in" value="<?= htmlspecialchars($check_in); ?>">

                            <input type="hidden" name="check_out" value="<?= htmlspecialchars($check_out); ?>">

                            <input type="hidden" name="guests" value="<?= $guests; ?>">

                            <input type="hidden" name="rooms" value="<?= $rooms; ?>">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <?php if (!$loggedInUser) { ?>

                                <div class="bk-form-row">

                                    <div class="bk-form-group">

                                        <label>Full Name</label>

                                        <input type="text" name="full_name" placeholder="Enter first and last name" required>

                                    </div>

                                    <div class="bk-form-group">

                                        <label>Email Address</label>

                                        <input type="email" name="email" placeholder="name@abc.com" required>

                                    </div>

                                </div>

                                <div class="bk-form-row">

                                    <div class="bk-form-group">

                                        <label>Mobile Number</label>

                                        <div class="bk-phone-group">

                                            <span class="bk-phone-prefix">+91</span>

                                            <input type="tel" name="phone" placeholder="e.g. 1234567890" pattern="[0-9]{10}" required>

                                        </div>

                                    </div>

                                    <div class="bk-form-group">

                                        <label>Password</label>

                                        <input type="password" name="password" placeholder="Create a password" minlength="8" required>

                                        <small class="bk-hint">This creates your account so you can track this booking later.</small>

                                    </div>

                                </div>

                            <?php } ?>

                            <button type="submit" class="bk-submit-btn" <?= !empty($errors) ? 'disabled' : ''; ?>>

                                Confirm Booking Request — ₹<?= number_format($payableAmount, 0); ?>

                            </button>

                        </form>

                    </div>

                </div>

            </div>

            <!-- ===========================
                 BOOKING SUMMARY SIDEBAR
            ============================ -->

            <div class="bk-sidebar">

                <div class="bk-summary-box">

                    <div class="bk-summary-header">

                        <div>

                            <h3><?= htmlspecialchars($property['title']); ?></h3>

                            <span class="bk-nights"><?= $nights; ?> Night<?= $nights > 1 ? 's' : ''; ?></span>

                        </div>

                        <img src="<?= htmlspecialchars($roomImage); ?>" alt="<?= htmlspecialchars($room['room_name']); ?>">

                    </div>

                    <?php if ($validDates) { ?>

                        <div class="bk-summary-row">

                            <i class="fa-regular fa-calendar"></i>

                            <?= date("D, j M", strtotime($check_in)); ?> - <?= date("D, j M", strtotime($check_out)); ?>

                            <span class="bk-summary-divider">|</span>

                            <?= $rooms; ?> Room<?= $rooms > 1 ? 's' : ''; ?>, <?= $guests; ?> Guest<?= $guests > 1 ? 's' : ''; ?>

                        </div>

                    <?php } ?>

                    <div class="bk-summary-row">

                        <i class="fa-solid fa-bed"></i>

                        <?= htmlspecialchars($room['room_name']); ?>

                    </div>

                    <div class="bk-price-breakdown">

                        <div class="bk-price-row">

                            <span>Room price for <?= $nights; ?> Night<?= $nights > 1 ? 's' : ''; ?> &times; <?= $rooms; ?> Room<?= $rooms > 1 ? 's' : ''; ?></span>

                            <span>₹<?= number_format($subtotal, 0); ?></span>

                        </div>

                        <?php if ($discountAmount > 0) { ?>

                            <div class="bk-price-row bk-price-discount">

                                <span><?= rtrim(rtrim(number_format($discountPercent, 2), '0'), '.'); ?>% Discount</span>

                                <span>-₹<?= number_format($discountAmount, 0); ?></span>

                            </div>

                        <?php } ?>

                    </div>

                    <div class="bk-payable-row">

                        <span>Payable Amount</span>

                        <span>₹<?= number_format($payableAmount, 0); ?></span>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>