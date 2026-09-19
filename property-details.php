<?php

require_once "config/db.php";

session_start();

/*====================================================
GET PROPERTY
====================================================*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {

    header("Location: properties.php");

    exit();

}

$sql = "

SELECT

p.*,

d.destination_name,

t.type_name

FROM properties p

INNER JOIN destinations d ON p.destination_id = d.id

INNER JOIN property_types t ON p.property_type_id = t.id

WHERE p.id = ?

AND p.status = 'Available'

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$property) {

    header("Location: properties.php");

    exit();

}


/*====================================================
PROPERTY IMAGES (cover + gallery)
====================================================*/

$sql = "SELECT * FROM property_images WHERE property_id=? ORDER BY is_cover DESC, id ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$imagesResult = mysqli_stmt_get_result($stmt);

$propertyImages = [];

while ($row = mysqli_fetch_assoc($imagesResult)) {
    $propertyImages[] = $row;
}

$mainImage = !empty($propertyImages)
    ? "assets/uploads/" . $propertyImages[0]['image_path']
    : "https://picsum.photos/900/600?random=" . $id;


/*====================================================
AMENITIES
====================================================*/

$sql = "

SELECT a.*

FROM property_amenities pa

INNER JOIN amenities a ON pa.amenity_id = a.id

WHERE pa.property_id = ?

ORDER BY a.amenity_name ASC

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$amenitiesResult = mysqli_stmt_get_result($stmt);

$amenities = [];

while ($row = mysqli_fetch_assoc($amenitiesResult)) {
    $amenities[] = $row;
}


/*====================================================
ROOM TYPES - the purchasable options for this property
====================================================*/

$sql = "SELECT * FROM property_rooms WHERE property_id=? AND status='Available' ORDER BY sort_order ASC, created_at ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$roomsResult = mysqli_stmt_get_result($stmt);

$rooms = [];

$cheapestRoomPrice = null;

while ($room = mysqli_fetch_assoc($roomsResult)) {

    $sql2 = "SELECT image_path FROM property_room_images WHERE room_id=? AND is_cover=1 LIMIT 1";

    $stmt2 = mysqli_prepare($conn, $sql2);

    mysqli_stmt_bind_param($stmt2, "i", $room['id']);

    mysqli_stmt_execute($stmt2);

    $coverRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

    $room['cover_image'] = $coverRow['image_path'] ?? null;

    $effectivePrice = $room['discounted_price'] ?? $room['price'];

    if ($cheapestRoomPrice === null || $effectivePrice < $cheapestRoomPrice) {
        $cheapestRoomPrice = $effectivePrice;
    }

    $rooms[] = $room;

}

/* Fall back to the property's own price if it has no room types yet */

$startingPrice = $cheapestRoomPrice ?? $property['discounted_price'] ?? $property['price'];


/*====================================================
REVIEWS
====================================================*/

$sql = "

SELECT

r.*,

u.name AS reviewer_name

FROM reviews r

INNER JOIN users u ON r.user_id = u.id

WHERE r.property_id = ?

ORDER BY r.created_at DESC

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$reviewsResult = mysqli_stmt_get_result($stmt);

$reviews = [];

while ($row = mysqli_fetch_assoc($reviewsResult)) {
    $reviews[] = $row;
}

$reviewCount = count($reviews);

$averageRating = $reviewCount > 0
    ? array_sum(array_column($reviews, 'rating')) / $reviewCount
    : null;


/*====================================================
CAN THE CURRENT USER LEAVE A REVIEW? - only if they have
a completed stay (past check-out, not cancelled) at this
property that they haven't already reviewed.
====================================================*/

$eligibleBookingId = null;

if (isset($_SESSION['user_id'])) {

    $sql = "

    SELECT b.id

    FROM bookings b

    LEFT JOIN reviews r ON r.booking_id = b.id

    WHERE b.property_id = ?

    AND b.user_id = ?

    AND b.check_out < CURDATE()

    AND b.booking_status NOT IN ('Cancelled', 'Cancellation Requested')

    AND r.id IS NULL

    ORDER BY b.check_out DESC

    LIMIT 1

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $id, $_SESSION['user_id']);

    mysqli_stmt_execute($stmt);

    $eligibleRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $eligibleBookingId = $eligibleRow['id'] ?? null;

}


/*====================================================
CSRF TOKEN - for the review form
====================================================*/

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

}


/*====================================================
FLASH MESSAGES FROM review-save.php
====================================================*/

$reviewMessage = null;
$reviewError = null;

if (isset($_SESSION['review_message'])) {
    $reviewMessage = $_SESSION['review_message'];
    unset($_SESSION['review_message']);
}

if (isset($_SESSION['review_error'])) {
    $reviewError = $_SESSION['review_error'];
    unset($_SESSION['review_error']);
}

/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = htmlspecialchars($property['title']) . " - Godaddy Booking";

$metaDescription = "Book " . htmlspecialchars($property['title']) . " in " . htmlspecialchars($property['destination_name']) . ". " . htmlspecialchars(mb_strimwidth(strip_tags($property['description'] ?? ''), 0, 140, '...'));

$canonicalUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/property-details.php?id=" . $id;

$currentPage = "properties";

$pageCss = ["assets/css/property-details.css", "assets/css/booking.css"];

include "includes/header.php";

?>

<section class="property-details-page">

    <div class="container">

        <!-- ===========================
             IMAGE GALLERY
        ============================ -->

        <div class="pd-gallery">

            <div class="pd-gallery-main">

                <img id="pdMainImage" src="<?= htmlspecialchars($mainImage); ?>" alt="<?= htmlspecialchars($property['title']); ?>">

                <?php if ((float)($property['discount_percent'] ?? 0) > 0) { ?>

                    <div class="pd-discount-badge">

                        <?= rtrim(rtrim(number_format($property['discount_percent'], 2), '0'), '.'); ?>% OFF

                    </div>

                <?php } ?>

            </div>

            <?php if (count($propertyImages) > 1) { ?>

                <div class="pd-gallery-thumbs">

                    <?php foreach ($propertyImages as $index => $img) { ?>

                        <img
                            class="pd-thumb <?= $index === 0 ? 'active' : ''; ?>"
                            src="assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                            data-full="assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                            alt="Property photo <?= $index + 1; ?>">

                    <?php } ?>

                </div>

            <?php } ?>

        </div>

        <div class="pd-layout">

            <!-- ===========================
                 MAIN CONTENT
            ============================ -->

            <div class="pd-main">

                <div class="pd-header">

                    <h1><?= htmlspecialchars($property['title']); ?></h1>

                    <p class="pd-location">

                        📍

                        <?php

                        $locationParts = array_filter([
                            $property['place'] ?? '',
                            $property['destination_name'],
                            $property['district'] ?? '',
                            $property['state'] ?? ''
                        ]);

                        echo htmlspecialchars(implode(', ', $locationParts));

                        ?>

                    </p>

                    <?php if (!empty($property['landmark'])) { ?>

                        <p class="pd-landmark">
                            <i class="fa-solid fa-location-dot"></i> Near <?= htmlspecialchars($property['landmark']); ?>
                        </p>

                    <?php } ?>

                    <div class="pd-badges">

                        <span class="badge"><?= htmlspecialchars($property['property_code']); ?></span>

                        <span class="badge"><?= htmlspecialchars($property['type_name']); ?></span>

                        <span class="badge"><i class="fa-solid fa-user-group"></i> Up to <?= (int)$property['max_guests']; ?> guests</span>

                        <?php if ($averageRating !== null) { ?>

                            <a href="#reviews" class="badge pd-rating-badge">

                                <i class="fa-solid fa-star"></i> <?= number_format($averageRating, 1); ?> (<?= $reviewCount; ?> review<?= $reviewCount > 1 ? 's' : ''; ?>)

                            </a>

                        <?php } ?>

                    </div>

                </div>

                <?php if (!empty($property['description'])) { ?>

                    <div class="pd-section">

                        <h2>About This Property</h2>

                        <p class="pd-description"><?= nl2br(htmlspecialchars($property['description'])); ?></p>

                    </div>

                <?php } ?>

                <?php if (!empty($property['address'])) { ?>

                    <div class="pd-section">

                        <h2>Address</h2>

                        <p class="pd-description"><?= nl2br(htmlspecialchars($property['address'])); ?></p>

                        <?php if (!empty($property['google_map_link'])) { ?>

                            <a href="<?= htmlspecialchars($property['google_map_link']); ?>" target="_blank" rel="noopener" class="pd-map-link">
                                <i class="fa-solid fa-map-location-dot"></i> View on Google Maps
                            </a>

                        <?php } ?>

                    </div>

                <?php } ?>

                <?php if (!empty($amenities)) { ?>

                    <div class="pd-section">

                        <h2>Amenities</h2>

                        <div class="pd-amenities-grid">

                            <?php foreach ($amenities as $amenity) { ?>

                                <div class="pd-amenity">

                                    <i class="<?= htmlspecialchars($amenity['icon'] ?: 'fa-solid fa-circle-check'); ?>"></i>

                                    <?= htmlspecialchars($amenity['amenity_name']); ?>

                                </div>

                            <?php } ?>

                        </div>

                    </div>

                <?php } ?>

                <!-- ===========================
                     ROOM TYPES - purchasable options
                ============================ -->

                <div class="pd-section">

                    <h2>Available Rooms</h2>

                    <?php if (empty($rooms)) { ?>

                        <p class="pd-no-rooms">No specific room types have been listed for this property yet.</p>

                    <?php } else { ?>

                        <form id="bookingForm" action="booking.php" method="GET">

                            <input type="hidden" name="property_id" value="<?= (int)$property['id']; ?>">

                            <div class="pd-rooms-list">

                                <?php foreach ($rooms as $index => $room) { ?>

                                    <?php

                                    $roomDiscount = (float)($room['discount_percent'] ?? 0);

                                    $roomFinalPrice = $room['discounted_price'] ?? $room['price'];

                                    $roomImage = $room['cover_image']
                                        ? "assets/uploads/" . $room['cover_image']
                                        : "https://picsum.photos/400/300?random=room" . $room['id'];

                                    ?>

                                    <div class="pd-room-item">

                                        <label class="pd-room-card">
                                            <input
                                                type="radio"
                                                name="room_id"
                                                value="<?= $room['id']; ?>"
                                                class="pd-room-radio"
                                                data-price="<?= $roomFinalPrice; ?>"
                                                data-max-rooms="<?= max(1, (int)($room['total_rooms'] ?? 1)); ?>"
                                                data-max-guests="<?= max(1, (int)$room['max_guests']); ?>"
                                                <?= $index === 0 ? 'checked' : ''; ?>>

                                            <img class="pd-room-image" src="<?= htmlspecialchars($roomImage); ?>" alt="<?= htmlspecialchars($room['room_name']); ?>">

                                            <div class="pd-room-info">

                                                <h3><?= htmlspecialchars($room['room_name']); ?></h3>

                                                <?php if (!empty($room['description'])) { ?>

                                                    <p class="pd-room-desc"><?= nl2br(htmlspecialchars($room['description'])); ?></p>

                                                <?php } ?>

                                                <div class="pd-room-meta">

                                                    <span><i class="fa-solid fa-user-group"></i> <?= (int)$room['max_guests']; ?> guests</span>

                                                </div>

                                            </div>

                                            <div class="pd-room-price">

                                                <?php if ($roomDiscount > 0) { ?>

                                                    <span class="price-original">₹<?= number_format($room['price'], 0); ?></span>

                                                <?php } ?>

                                                <span class="price-final">₹<?= number_format($roomFinalPrice, 0); ?></span>

                                                <span class="price-unit">/ night</span>

                                            </div>

                                            <div class="pd-room-selected-check">

                                                <i class="fa-solid fa-circle-check"></i>

                                            </div>

                                        </label>

                                        <a href="room-details.php?id=<?= $room['id']; ?>" class="pd-room-view-link">

                                            View Room Details <i class="fa-solid fa-arrow-right"></i>

                                        </a>

                                    </div>

                                <?php } ?>

                            </div>

                        </form>

                    <?php } ?>

                </div>

                <!-- ===========================
                     REVIEWS
                ============================ -->

                <div class="pd-section" id="reviews">

                    <h2>Guest Reviews <?php if ($reviewCount > 0) { ?>(<?= $reviewCount; ?>)<?php } ?></h2>

                    <?php if ($reviewMessage) { ?>

                        <div class="bk-alert bk-alert-success"><?= htmlspecialchars($reviewMessage); ?></div>

                    <?php } ?>

                    <?php if ($reviewError) { ?>

                        <div class="bk-alert bk-alert-error"><?= htmlspecialchars($reviewError); ?></div>

                    <?php } ?>

                    <?php if ($eligibleBookingId) { ?>

                        <form action="review-save.php" method="POST" class="pd-review-form">

                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <input type="hidden" name="property_id" value="<?= (int)$property['id']; ?>">

                            <input type="hidden" name="booking_id" value="<?= (int)$eligibleBookingId; ?>">

                            <label class="pd-review-form-label">Rate your stay</label>

                            <div class="pd-star-input">

                                <?php for ($s = 5; $s >= 1; $s--) { ?>

                                    <input type="radio" name="rating" id="star<?= $s; ?>" value="<?= $s; ?>" required>
                                    <label for="star<?= $s; ?>"><i class="fa-solid fa-star"></i></label>

                                <?php } ?>

                            </div>

                            <textarea name="comment" placeholder="Tell other guests about your stay (optional)" maxlength="1000"></textarea>

                            <button type="submit" class="bk-submit-btn" style="width:auto; padding:12px 28px;">Submit Review</button>

                        </form>

                    <?php } elseif (!isset($_SESSION['user_id'])) { ?>

                        <p class="pd-review-note"><a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']); ?>">Log in</a> after your stay to leave a review.</p>

                    <?php } ?>

                    <?php if (empty($reviews)) { ?>

                        <p class="pd-no-rooms">No reviews yet. Be the first to share your experience.</p>

                    <?php } else { ?>

                        <div class="pd-reviews-list">

                            <?php foreach ($reviews as $review) { ?>

                                <div class="pd-review-card">

                                    <div class="pd-review-avatar-row">

                                        <div class="pd-review-avatar">

                                            <i class="fa-solid fa-user"></i>

                                        </div>

                                        <strong><?= htmlspecialchars($review['reviewer_name']); ?></strong>

                                    </div>

                                    <div class="pd-review-stars">

                                        <?php for ($s = 1; $s <= 5; $s++) { ?>

                                            <i class="fa-solid fa-star <?= $s <= (int)$review['rating'] ? 'filled' : ''; ?>"></i>

                                        <?php } ?>

                                    </div>

                                    <span class="pd-review-date">Reviewed on <?= date("j F Y", strtotime($review['created_at'])); ?></span>

                                    <?php if (!empty($review['comment'])) { ?>

                                        <p class="pd-review-comment"><?= nl2br(htmlspecialchars($review['comment'])); ?></p>

                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </div>

                    <?php } ?>

                </div>


            </div>

            <!-- ===========================
                 BOOKING SIDEBAR
            ============================ -->

            <div class="pd-sidebar">

                <div class="pd-booking-box">

                    <div class="pd-booking-price">

                        <span class="pd-booking-from">Starting from</span>

                        <span class="pd-booking-amount">₹<?= number_format($startingPrice, 0); ?></span>

                        <span class="pd-booking-unit">/ night</span>

                    </div>

                                        <div class="pd-booking-field">

                        <label>Check In</label>

                        <input type="date" id="pdCheckIn" form="bookingForm" name="check_in">

                    </div>

                    <div class="pd-booking-field">

                        <label>Check Out</label>

                        <input type="date" id="pdCheckOut" form="bookingForm" name="check_out">

                    </div>

                    <?php $defaultRoomForDropdown = $rooms[0] ?? null; ?>

                    <div class="pd-booking-field">

                        <label>Guests</label>

                        <?php $defaultGuestsMax = max(1, (int)($defaultRoomForDropdown['max_guests'] ?? $property['max_guests'])); ?>

                        <select form="bookingForm" name="guests" id="pdGuestsCount">

                            <?php for ($g = 1; $g <= $defaultGuestsMax; $g++) { ?>

                                <option value="<?= $g; ?>"><?= $g; ?> Guest<?= $g > 1 ? 's' : ''; ?></option>

                            <?php } ?>

                        </select>

                    </div>

                    <div class="pd-booking-field">

                        <label>Rooms</label>

                        <?php $maxRoomsAvailable = max(1, (int)($defaultRoomForDropdown['total_rooms'] ?? 1)); ?>

                        <select form="bookingForm" name="rooms" id="pdRoomsCount">

                            <?php for ($r = 1; $r <= $maxRoomsAvailable; $r++) { ?>

                                <option value="<?= $r; ?>"><?= $r; ?> Room<?= $r > 1 ? 's' : ''; ?></option>

                            <?php } ?>

                        </select>

                        <small class="bk-hint" id="pdRoomsHint"></small>

                    </div>

                    <button type="submit" form="bookingForm" class="pd-book-btn" <?= empty($rooms) ? 'disabled' : ''; ?>>

                        Book Now

                    </button>

                    <p class="pd-booking-note">You won't be charged yet.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>