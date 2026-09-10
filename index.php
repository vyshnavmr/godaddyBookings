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

LIMIT 6

";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {

    $sql = "

    SELECT

    p.*,

    d.destination_name,

    (SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) AS cover_image

    FROM properties p

    INNER JOIN destinations d ON p.destination_id = d.id

    WHERE p.status = 'Available'

    ORDER BY p.discount_percent DESC, RAND()

    LIMIT 8

    ";

    $result = mysqli_query($conn, $sql);

}

$properties = [];

while ($row = mysqli_fetch_assoc($result)) {

    $properties[] = $row;

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

?>

<?php

$pageTitle = "Godaddy Booking - Discover Your Perfect Stay";

$currentPage = "home";

include "includes/header.php";

?>

<?php if (isset($_GET['booking_confirmed']) && $_GET['booking_confirmed'] == '1') { ?>

    <div class="container" style="margin-top:30px;">

        <div style="background:var(--red-soft); color:var(--red-dark); padding:18px 24px; border-radius:12px; text-align:center; font-weight:600;">

            🎉 Your booking request has been received! We'll be in touch shortly to confirm the details.

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

                <a
                    href="properties.php?destination_id=<?= $destination['id']; ?>"
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
        <div class="testimonial-container">
            <div class="testimonial">
                <p>
                    "Excellent service and beautiful properties. Booking was very easy."
                </p>
                <h4>
                    — Rahul Nair
                </h4>
            </div>
            <div class="testimonial">
                <p>
                    "Our family vacation was amazing. Highly recommended."
                </p>
                <h4>
                    — Priya Menon
                </h4>
            </div>
        </div>
    </section>

    <!-- ================= FOOTER ================= -->
    <?php include "includes/footer.php"; ?>