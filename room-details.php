<?php

require_once "config/db.php";

/*====================================================
GET ROOM
====================================================*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {

    header("Location: properties.php");

    exit();

}

$sql = "

SELECT

r.*,

p.id AS property_id,

p.title AS property_title,

p.property_code,

d.destination_name

FROM property_rooms r

INNER JOIN properties p ON r.property_id = p.id

INNER JOIN destinations d ON p.destination_id = d.id

WHERE r.id = ?

AND r.status = 'Available'

AND p.status = 'Available'

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$room) {

    header("Location: properties.php");

    exit();

}

$property_id = (int)$room['property_id'];


/*====================================================
ROOM IMAGES
====================================================*/

$sql = "SELECT * FROM property_room_images WHERE room_id=? ORDER BY is_cover DESC, id ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$roomImagesResult = mysqli_stmt_get_result($stmt);

$roomImages = [];

while ($row = mysqli_fetch_assoc($roomImagesResult)) {
    $roomImages[] = $row;
}

$mainImage = !empty($roomImages)
    ? "assets/uploads/" . $roomImages[0]['image_path']
    : "https://picsum.photos/900/600?random=room" . $id;


/*====================================================
OTHER ROOMS OF THE SAME PROPERTY - for jumping between them
====================================================*/

$sql = "SELECT * FROM property_rooms WHERE property_id=? AND status='Available' ORDER BY sort_order ASC, created_at ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $property_id);

mysqli_stmt_execute($stmt);

$siblingResult = mysqli_stmt_get_result($stmt);

$siblingRooms = [];

while ($sibling = mysqli_fetch_assoc($siblingResult)) {

    $sql2 = "SELECT image_path FROM property_room_images WHERE room_id=? AND is_cover=1 LIMIT 1";

    $stmt2 = mysqli_prepare($conn, $sql2);

    mysqli_stmt_bind_param($stmt2, "i", $sibling['id']);

    mysqli_stmt_execute($stmt2);

    $coverRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

    $sibling['cover_image'] = $coverRow['image_path'] ?? null;

    $siblingRooms[] = $sibling;

}

$roomDiscount = (float)($room['discount_percent'] ?? 0);

$roomFinalPrice = $room['discounted_price'] ?? $room['price'];


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = htmlspecialchars($room['room_name']) . " - " . htmlspecialchars($room['property_title']) . " - Godaddy Booking";

$currentPage = "properties";

$pageCss = "assets/css/room-details.css";

include "includes/header.php";

?>

<section class="rd-page">

    <div class="container">

        <a href="property-details.php?id=<?= $property_id; ?>" class="rd-back-link">

            <i class="fa-solid fa-arrow-left"></i> Back to <?= htmlspecialchars($room['property_title']); ?>

        </a>

        <!-- ===========================
             GALLERY
        ============================ -->

        <div class="rd-gallery">

            <div class="rd-gallery-main">

                <img id="rdMainImage" src="<?= htmlspecialchars($mainImage); ?>" alt="<?= htmlspecialchars($room['room_name']); ?>">

                <?php if ($roomDiscount > 0) { ?>

                    <div class="rd-discount-badge">

                        <?= rtrim(rtrim(number_format($roomDiscount, 2), '0'), '.'); ?>% OFF

                    </div>

                <?php } ?>

            </div>

            <?php if (count($roomImages) > 1) { ?>

                <div class="rd-gallery-thumbs">

                    <?php foreach ($roomImages as $index => $img) { ?>

                        <img
                            class="rd-thumb <?= $index === 0 ? 'active' : ''; ?>"
                            src="assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                            data-full="assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                            alt="Room photo <?= $index + 1; ?>">

                    <?php } ?>

                </div>

            <?php } ?>

        </div>

        <div class="rd-layout">

            <!-- ===========================
                 MAIN CONTENT
            ============================ -->

            <div class="rd-main">

                <div class="rd-header">

                    <h1><?= htmlspecialchars($room['room_name']); ?></h1>

                    <p class="rd-property-link">

                        Part of

                        <a href="property-details.php?id=<?= $property_id; ?>"><?= htmlspecialchars($room['property_title']); ?></a>

                        · <?= htmlspecialchars($room['destination_name']); ?>

                    </p>

                    <div class="rd-badges">

                        <span class="badge"><i class="fa-solid fa-user-group"></i> Up to <?= (int)$room['max_guests']; ?> guests</span>

                    </div>

                </div>

                <?php if (!empty($room['description'])) { ?>

                    <div class="rd-section">

                        <h2>About This Room</h2>

                        <p class="rd-description"><?= nl2br(htmlspecialchars($room['description'])); ?></p>

                    </div>

                <?php } ?>

                <?php if (count($siblingRooms) > 1) { ?>

                    <div class="rd-section">

                        <h2>Other Rooms in This Property</h2>

                        <div class="rd-switcher">

                            <?php foreach ($siblingRooms as $sibling) { ?>

                                <?php

                                $isCurrent = ((int)$sibling['id'] === $id);

                                $siblingFinalPrice = $sibling['discounted_price'] ?? $sibling['price'];

                                $siblingImage = $sibling['cover_image']
                                    ? "assets/uploads/" . $sibling['cover_image']
                                    : "https://picsum.photos/300/200?random=room" . $sibling['id'];

                                ?>

                                <a
                                    href="room-details.php?id=<?= $sibling['id']; ?>"
                                    class="rd-switch-card <?= $isCurrent ? 'active' : ''; ?>">

                                    <img src="<?= htmlspecialchars($siblingImage); ?>" alt="<?= htmlspecialchars($sibling['room_name']); ?>">

                                    <div class="rd-switch-info">

                                        <h4><?= htmlspecialchars($sibling['room_name']); ?></h4>

                                        <span class="rd-switch-price">₹<?= number_format($siblingFinalPrice, 0); ?> / night</span>

                                    </div>

                                    <?php if ($isCurrent) { ?>

                                        <span class="rd-switch-current">Viewing</span>

                                    <?php } ?>

                                </a>

                            <?php } ?>

                        </div>

                    </div>

                <?php } ?>

            </div>

            <!-- ===========================
                 BOOKING SIDEBAR
            ============================ -->

            <div class="rd-sidebar">

                <div class="rd-booking-box">

                    <div class="rd-booking-price">

                        <?php if ($roomDiscount > 0) { ?>

                            <span class="price-original">₹<?= number_format($room['price'], 0); ?></span>

                        <?php } ?>

                        <span class="price-final">₹<?= number_format($roomFinalPrice, 0); ?></span>

                        <span class="price-unit">/ night</span>

                    </div>

                    <form action="booking.php" method="GET" id="bookingForm">

                        <input type="hidden" name="property_id" value="<?= $property_id; ?>">

                        <input type="hidden" name="room_id" value="<?= $id; ?>">

                        <div class="rd-booking-field">

                            <label>Check In</label>

                            <input type="date" name="check_in">

                        </div>

                        <div class="rd-booking-field">

                            <label>Check Out</label>

                            <input type="date" name="check_out">

                        </div>

                        <div class="rd-booking-field">

                            <label>Guests</label>

                            <select name="guests">

                                <?php for ($g = 1; $g <= (int)$room['max_guests']; $g++) { ?>

                                    <option value="<?= $g; ?>"><?= $g; ?> Guest<?= $g > 1 ? 's' : ''; ?></option>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="pd-booking-field">

                            <label>Rooms</label>

                            <select name="rooms" id="pdRoomsCount">

                                <?php for ($r = 1; $r <= 5; $r++) { ?>

                                    <option value="<?= $r; ?>"><?= $r; ?> Room<?= $r > 1 ? 's' : ''; ?></option>

                                <?php } ?>

                            </select>

                        </div>

                        <button type="submit" class="rd-book-btn">Book Now</button>

                    </form>

                    <p class="rd-booking-note">You won't be charged yet.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>