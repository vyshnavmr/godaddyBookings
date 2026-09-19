<?php

require_once "config/db.php";

session_start();


/*====================================================
REQUIRE LOGIN
====================================================*/

$isLoggedIn = isset($_SESSION['user_id']);

$bookings = [];


/*====================================================
CSRF TOKEN - for the cancellation request form
====================================================*/

if ($isLoggedIn && empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}


/*====================================================
FLASH MESSAGES FROM cancel-booking.php
====================================================*/

$cancelMessage = null;

$cancelError = null;

if (isset($_SESSION['cancel_message'])) {
    $cancelMessage = $_SESSION['cancel_message'];
    unset($_SESSION['cancel_message']);
}

if (isset($_SESSION['cancel_error'])) {
    $cancelError = $_SESSION['cancel_error'];
    unset($_SESSION['cancel_error']);
}


if ($isLoggedIn) {

    $sql = "

    SELECT

    b.*,

    p.title AS property_title,

    p.property_code,

    d.destination_name,

    r.room_name,

    (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) AS cover_image

    FROM bookings b

    LEFT JOIN properties p ON b.property_id = p.id

    LEFT JOIN destinations d ON p.destination_id = d.id

    LEFT JOIN property_rooms r ON b.room_id = r.id

    WHERE b.user_id = ?

    ORDER BY b.created_at DESC

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {

        $bookings[] = $row;

    }

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "My Bookings - Godaddy Booking";

$currentPage = "my-bookings";

$pageCss = ["assets/css/booking.css", "assets/css/my-bookings.css"];

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <?php if (!$isLoggedIn) { ?>

            <!-- ===========================
                 NOT LOGGED IN
            ============================ -->

            <div class="bk-auth-wrap">

                <div class="bk-card">

                    <div class="bk-card-header">

                        <h2>My Bookings</h2>

                    </div>

                    <div class="bk-card-body" style="text-align:center;">

                        <p class="bk-card-subtitle">Log in to view your current and past bookings.</p>

                        <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>" class="bk-submit-btn" style="display:inline-block; text-decoration:none; width:auto; padding:14px 40px;">

                            Log In

                        </a>

                        <p class="bk-login-prompt bk-no-border">

                            New here? <a href="properties.php">Browse properties</a> and your account will be created automatically when you book.

                        </p>

                    </div>

                </div>

            </div>

        <?php } else { ?>

            <!-- ===========================
                 LOGGED IN - BOOKING LIST
            ============================ -->

            <div class="section-heading" style="margin-bottom:35px;">

                <div class="eyebrow">Your Trips</div>

                <h2>My Bookings</h2>

                <p>Hi <?= htmlspecialchars($_SESSION['user_name'] ?? 'there'); ?>, here's everything you've booked with us.</p>

            </div>

            <?php if ($cancelMessage) { ?>

                <div class="bk-alert bk-alert-success"><?= htmlspecialchars($cancelMessage); ?></div>

            <?php } ?>

            <?php if ($cancelError) { ?>

                <div class="bk-alert bk-alert-error"><?= htmlspecialchars($cancelError); ?></div>

            <?php } ?>

            <?php if (empty($bookings)) { ?>

                <div class="mb-empty">

                    <i class="fa-regular fa-calendar-xmark"></i>

                    <h3>No bookings yet</h3>

                    <p>When you book a stay, it'll show up here.</p>

                    <a href="properties.php" class="bk-submit-btn" style="display:inline-block; text-decoration:none; width:auto; padding:14px 32px;">

                        Browse Properties

                    </a>

                </div>

            <?php } else { ?>

                <div class="mb-list">

                    <?php foreach ($bookings as $booking) { ?>

                        <?php

                        $image = !empty($booking['cover_image'])
                            ? "assets/uploads/" . $booking['cover_image']
                            : "https://picsum.photos/300/200?random=booking" . $booking['id'];

                        $inDate = DateTime::createFromFormat("Y-m-d", $booking['check_in']);

                        $outDate = DateTime::createFromFormat("Y-m-d", $booking['check_out']);

                        $nights = ($inDate && $outDate) ? $outDate->diff($inDate)->days : 0;

                        $statusLower = strtolower($booking['booking_status'] ?? '');

                        $statusClass = "mb-status-default";

                        if ($statusLower === 'booked') {
                            $statusClass = "mb-status-confirmed";
                        } elseif ($statusLower === 'pending') {
                            $statusClass = "mb-status-pending";
                        } elseif ($statusLower === 'cancelled') {
                            $statusClass = "mb-status-cancelled";
                        } elseif ($statusLower === 'cancellation requested') {
                            $statusClass = "mb-status-requested";
                        }

                        $paymentLower = strtolower($booking['payment_status'] ?? '');

                        $paymentClass = ($paymentLower === 'paid') ? "mb-payment-paid" : "mb-payment-unpaid";

                        /*====================================================
                        CAN THIS BOOKING BE CANCELLED?
                        - not already Cancelled or Cancellation Requested
                        - at least 24 hours before check-in (midnight of
                          check-in date), per the site's cancellation policy
                        ====================================================*/

                        $canCancel = false;

                        if (!in_array($statusLower, ['cancelled', 'cancellation requested']) && $inDate) {

                            $checkInMidnight = clone $inDate;

                            $checkInMidnight->setTime(0, 0, 0);

                            $now = new DateTime();

                            $hoursUntilCheckIn = ($checkInMidnight->getTimestamp() - $now->getTimestamp()) / 3600;

                            $canCancel = $hoursUntilCheckIn >= 24;

                        }

                        ?>

                        <div class="mb-card">

                            <img src="<?= htmlspecialchars($image); ?>" alt="<?= htmlspecialchars($booking['property_title'] ?? 'Property'); ?>" class="mb-card-image">

                            <div class="mb-card-body">

                                <div class="mb-card-top">

                                    <div>

                                        <h3><?= htmlspecialchars($booking['property_title'] ?? 'Property no longer available'); ?></h3>

                                        <?php if (!empty($booking['destination_name'])) { ?>

                                            <p class="mb-location">📍 <?= htmlspecialchars($booking['destination_name']); ?></p>

                                        <?php } ?>

                                    </div>

                                    <span class="mb-status-badge <?= $statusClass; ?>">

                                        <?= htmlspecialchars($booking['booking_status'] ?? 'Unknown'); ?>

                                    </span>

                                </div>

                                <div class="mb-card-details">

                                    <span><i class="fa-solid fa-bed"></i> <?= htmlspecialchars($booking['room_name'] ?? 'Room'); ?></span>

                                    <span><i class="fa-regular fa-calendar"></i> <?= date("D, j M Y", strtotime($booking['check_in'])); ?> - <?= date("D, j M Y", strtotime($booking['check_out'])); ?></span>

                                    <span><i class="fa-solid fa-moon"></i> <?= $nights; ?> Night<?= $nights != 1 ? 's' : ''; ?></span>

                                    <span><i class="fa-solid fa-door-open"></i> <?= (int)$booking['rooms']; ?> Room<?= (int)$booking['rooms'] > 1 ? 's' : ''; ?></span>

                                    <span><i class="fa-solid fa-user-group"></i> <?= (int)$booking['guests']; ?> Guest<?= (int)$booking['guests'] > 1 ? 's' : ''; ?></span>

                                </div>

                                <div class="mb-card-bottom">

                                    <span class="mb-payment-badge <?= $paymentClass; ?>">

                                        <?= htmlspecialchars($booking['payment_status'] ?? 'Unknown'); ?>

                                    </span>

                                    <span class="mb-price">₹<?= number_format($booking['total_price'], 0); ?></span>

                                    <?php if (!empty($booking['property_id'])) { ?>

                                        <a href="property-details.php?id=<?= (int)$booking['property_id']; ?>" class="mb-view-link">

                                            View Property <i class="fa-solid fa-arrow-right"></i>

                                        </a>

                                    <?php } ?>

                                    <a href="javascript:void(0);" onclick="window.open('booking-receipt.php?id=<?= (int)$booking['id']; ?>', '_blank', 'noopener'); return false;" class="mb-view-link">

                                        View Receipt <i class="fa-solid fa-receipt"></i>

                                    </a>

                                </div>

                                <?php if ($canCancel) { ?>

                                    <form action="cancel-booking.php" method="POST" class="mb-cancel-form" onsubmit="return confirm('Request cancellation for this booking? This cannot be undone.');">

                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <input type="hidden" name="booking_id" value="<?= (int)$booking['id']; ?>">

                                        <button type="submit" class="mb-cancel-btn">

                                            <i class="fa-solid fa-ban"></i> Request Cancellation

                                        </button>

                                    </form>

                                <?php } elseif ($statusLower === 'cancellation requested') { ?>

                                    <p class="mb-cancel-note">Cancellation requested — awaiting confirmation.</p>

                                <?php } elseif (!in_array($statusLower, ['cancelled']) && $inDate) { ?>

                                    <p class="mb-cancel-note">Cancellation window has passed (within 24 hours of check-in).</p>

                                <?php } ?>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            <?php } ?>

        <?php } ?>

    </div>

</section>

<?php include "includes/footer.php"; ?>