<?php

require_once "config/db.php";

/*====================================================
FEATURED PROPERTIES

Only show properties marked as Featured. If none are
featured yet, fall back to a random selection of available
properties so the homepage is never empty.
====================================================*/

$sql = "

SELECT

p.*,

d.destination_name,

(SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) AS cover_image

FROM properties p

INNER JOIN destinations d ON p.destination_id = d.id

WHERE p.status = 'Available'

AND p.featured = 1

ORDER BY p.discount_percent DESC, p.created_at DESC

LIMIT 8

";

$result = mysqli_query($conn, $sql);

$properties = [];

while ($row = mysqli_fetch_assoc($result)) {

    $properties[] = $row;

}


/*====================================================
FILL REMAINING SLOTS - if fewer than 8 properties are
marked Featured, top up with other Available properties
(excluding ones already picked) so the homepage still
shows up to 8 whenever that many exist site-wide.
====================================================*/

$remainingSlots = 8 - count($properties);

if ($remainingSlots > 0) {

    $excludeIds = array_column($properties, 'id');

    $excludeIdList = !empty($excludeIds) ? implode(",", array_map('intval', $excludeIds)) : "0";

    $sql = "

    SELECT

    p.*,

    d.destination_name,

    (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) AS cover_image

    FROM properties p

    INNER JOIN destinations d ON p.destination_id = d.id

    WHERE p.status = 'Available'

    AND p.id NOT IN ($excludeIdList)

    ORDER BY p.discount_percent DESC, RAND()

    LIMIT $remainingSlots

    ";

    $result = mysqli_query($conn, $sql);

    while ($row = mysqli_fetch_assoc($result)) {

        $properties[] = $row;

    }

}


/*====================================================
DESTINATIONS - for the "Popular Destinations" grid
====================================================*/

$sql = "SELECT * FROM destinations WHERE is_active = 1 ORDER BY destination_name ASC LIMIT 4";

$destResult = mysqli_query($conn, $sql);

$destinations = [];

while ($row = mysqli_fetch_assoc($destResult)) {

    $destinations[] = $row;

}

/*====================================================
TESTIMONIALS - highlighted reviews first, then most
recent. Limited to 6 so the homepage doesn't grow
unbounded as reviews accumulate.
====================================================*/

$sql = "

SELECT

r.rating,

r.comment,

r.created_at,

u.name AS reviewer_name,

p.title AS property_title

FROM reviews r

INNER JOIN users u ON r.user_id = u.id

INNER JOIN properties p ON r.property_id = p.id

ORDER BY r.is_highlighted DESC, r.created_at DESC

LIMIT 6

";

$testimonialsResult = mysqli_query($conn, $sql);

$testimonials = [];

while ($row = mysqli_fetch_assoc($testimonialsResult)) {

    $testimonials[] = $row;

}


?>

<?php

$pageTitle = "Godaddy Booking - Discover Your Perfect Stay";

$currentPage = "home";

include "includes/header.php";

?>

<?php if (isset($_GET['booking_confirmed']) && $_GET['booking_confirmed'] == '1') { ?>

    <div class="bkc-overlay" id="bkcOverlay">
        <div class="bkc-modal">
            
            <button type="button" class="bkc-close" id="bkcClose" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            
            <div class="bkc-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <h2>Thank You for Your Booking!</h2>
            
            <p>Our executive will contact you shortly to confirm the details.</p>
            
            <p class="bkc-call-note">Need to speak to us right away?</p>
            
            <a href="tel:+919895928883" class="bkc-call-btn">
                <i class="fa-solid fa-phone"></i>
                Call +91 98959 28883
            </a>
            
        </div>
        
    </div>
    
    <?php } ?>
    
    <!-- ================= HERO ================= -->
    
    <section class="hero">
        <div class="slides">
            <div class="slide active">
                <img src="https://picsum.photos/1600/900?random=21">
            </div>
            <div class="slide">
                <img src="https://picsum.photos/1600/900?random=22">
            </div>
            
            <div class="slide">
                <img src="https://picsum.photos/1600/900?random=23">
            </div>
        </div>
        
        <div class="overlay"></div>
        
        <div class="hero-content">
            <h1>
                Discover Your Perfect Stay
            </h1>
            <p>
                Luxury Resorts • Villas • Homestays • Cottages
            </p>
            
            <div class="search-box" id="search-box">
                <input
                type="text"
                id="heroDestination"
                placeholder="Destination">
                <input
                type="date"
                id="heroCheckIn">
                <input
                    type="date"
                    id="heroCheckOut">
                <select id="heroGuests">
                    <option value="">Guests</option>
                    <option value="1">1 Guest</option>
                    <option value="2">2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4+ Guests</option>
                </select>
                <button id="heroSearchBtn" type="button">
                    Search
                </button>
            </div>
        </div>
    </section>

    <!-- ================= FEATURED ================= -->
    <section class="properties" id="properties">
        <div class="section-heading">
            <div class="eyebrow">Featured Stays</div>
            <h2>
                Featured Properties
            </h2>
            <p>
                Handpicked accommodations for your perfect vacation.
            </p>
        </div>
        <div class="container">

            <div class="property-grid">

            <?php if (empty($properties)) { ?>

                <p style="text-align:center; grid-column:1/-1; color:#6b7280;">
                    No properties available right now. Check back soon.
                </p>

            <?php } ?>

            <?php foreach ($properties as $property): ?>

                <?php

                $image = !empty($property['cover_image'])
                    ? "assets/uploads/" . $property['cover_image']
                    : "https://picsum.photos/600/400?random=" . $property['id'];

                $discountPercent = (float)($property['discount_percent'] ?? 0);

                $finalPrice = $property['discounted_price'] ?? $property['price'];

                ?>

                <div class="property-card">

                    <div class="property-image-wrap">

                        <img
                            src="<?= htmlspecialchars($image); ?>"
                            alt="<?= htmlspecialchars($property['title']); ?>">

                        <?php if ($discountPercent > 0) { ?>

                            <div class="discount-badge">

                                <?= rtrim(rtrim(number_format($discountPercent, 2), '0'), '.'); ?>% OFF

                            </div>

                        <?php } ?>

                    </div>

                    <div class="property-content">

                        <h3>
                            <?= htmlspecialchars($property['title']); ?>
                        </h3>
                        <p>
                            📍 <?= htmlspecialchars($property['destination_name']); ?>
                        </p>
                        <div class="price">

                            <?php if ($discountPercent > 0) { ?>

                                <span class="price-original">₹<?= number_format($property['price'], 0); ?></span>

                            <?php } ?>

                            <span class="price-final">₹<?= number_format($finalPrice, 0); ?></span>

                            <span class="price-unit">/ Night</span>

                        </div>
                        <a href="property-details.php?id=<?= $property['id']; ?>">
                            <button>
                                View Details
                            </button>
                        </a>
                    </div>
                </div>

            <?php endforeach; ?>

            </div>

            <?php if (!empty($properties)) { ?>

                <div class="show-more-wrap">

                    <a href="properties.php" class="show-more-btn">
                        Show More Properties
                    </a>

                </div>

            <?php } ?>

        </div>
    </section>

    <!-- ================= WHY CHOOSE US ================= -->
    <section class="why-us" id="why-us">
        <div class="container">
            <div class="section-heading">
                <div class="eyebrow">The Difference</div>
                <h2>
                    Why Choose Godaddy Booking?
                </h2>
            </div>
            <div class="features">
                <div class="feature">
                    <div class="icon">🏨</div>
                    <h3>
                        Verified Properties
                    </h3>
                    <p>
                        Every accommodation is carefully verified before listing.
                    </p>
            </div>
                <div class="feature">
                    <div class="icon">💰</div>
                    <h3>
                        Best Prices
                    </h3>
                    <p>
                        Affordable prices with no hidden charges.
                    </p>
                </div>
                <div class="feature">
                    <div class="icon">⚡</div>
                    <h3>
                        Instant Booking
                    </h3>
                    <p>
                        Book your dream stay within seconds.
                    </p>
                </div>
                <div class="feature">
                    <div class="icon">📞</div>
                    <h3>
                        24/7 Support
                    </h3>
                    <p>
                        Our support team is always available.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= DESTINATIONS ================= -->
    <section class="destinations" id="destinations">
        <div class="section-heading">
            <div class="eyebrow">Where To Go</div>
            <h2>
                Popular Destinations
            </h2>
        </div>
        <div class="destination-grid">

            <?php if (empty($destinations)) { ?>

                <p style="text-align:center; color:#6b7280;">
                    No destinations available yet.
                </p>

            <?php } ?>

            <?php foreach ($destinations as $destination): ?>

                <?php

                $destImage = !empty($destination['hero_image'])
                    ? "assets/uploads/" . $destination['hero_image']
                    : (!empty($destination['image'])
                        ? "assets/uploads/" . $destination['image']
                        : "https://picsum.photos/700/500?random=" . $destination['id']);

                ?>

                
                    <a href="properties.php?destination_id=<?= $destination['id']; ?>"
                    class="destination"
                    style="background-image:url('<?= htmlspecialchars($destImage); ?>');">

                    <span><?= htmlspecialchars($destination['destination_name']); ?></span>

                </a>

            <?php endforeach; ?>

        </div>
    </section>

    <!-- ================= TESTIMONIALS ================= -->
    <section class="testimonials">
        <div class="section-heading">
            <div class="eyebrow">Guest Stories</div>
            <h2>
                What Our Guests Say
            </h2>
        </div>

        <?php if (!empty($testimonials)) { ?>

            <div class="testimonial-container">

                <?php foreach ($testimonials as $testimonial) { ?>

                    <div class="testimonial">

                        <div class="testimonial-stars">

                            <?php for ($s = 1; $s <= 5; $s++) { ?>

                                <i class="fa-solid fa-star <?= $s <= (int)$testimonial['rating'] ? 'filled' : ''; ?>"></i>

                            <?php } ?>

                        </div>

                        <p>
                            "<?= nl2br(htmlspecialchars($testimonial['comment'] ?: 'A wonderful stay from start to finish.')); ?>"
                        </p>

                        <h4>
                            — <?= htmlspecialchars($testimonial['reviewer_name']); ?>
                        </h4>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </section>

    <!-- ================= FOOTER ================= -->
    <?php include "includes/footer.php"; ?>