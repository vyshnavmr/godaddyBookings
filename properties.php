<?php

require_once "config/db.php";

/*====================================================
FILTERS FROM URL
====================================================*/

$search         = trim($_GET['search'] ?? "");

$sort           = $_GET['sort'] ?? "";

$destination_id = (int)($_GET['destination_id'] ?? 0);

$type_id        = (int)($_GET['type_id'] ?? 0);

$destinationName = "";

if ($destination_id > 0) {

    $sql = "SELECT destination_name FROM destinations WHERE id=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $destination_id);

    mysqli_stmt_execute($stmt);

    $destRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($destRow) {
        $destinationName = $destRow['destination_name'];
    } else {
        $destination_id = 0;
    }

}

$typeName = "";

if ($type_id > 0) {

    $sql = "SELECT type_name FROM property_types WHERE id=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $type_id);

    mysqli_stmt_execute($stmt);

    $typeRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($typeRow) {
        $typeName = $typeRow['type_name'];
    } else {
        $type_id = 0;
    }

}


/*====================================================
BUILD "CLEAR THIS FILTER" LINKS - keeps every other
active filter/search/sort intact, drops only one key
====================================================*/

function buildFilterUrl($dropKey) {

    $params = $_GET;

    unset($params[$dropKey]);

    $query = http_build_query($params);

    return "properties.php" . ($query != "" ? "?$query" : "");

}


/*====================================================
PROPERTY QUERY

Only Available properties are shown to customers, same
policy as the homepage. Discounted properties lead by
default (highest discount first); an explicit sort choice
from the toolbar overrides that default ordering.
====================================================*/

$sql = "

SELECT

p.*,

d.destination_name,

t.type_name,

(SELECT image_path FROM property_images WHERE property_id = p.id AND is_cover = 1 LIMIT 1) AS cover_image

FROM properties p

INNER JOIN destinations d ON p.destination_id = d.id

INNER JOIN property_types t ON p.property_type_id = t.id

WHERE p.status = 'Available'

";

if ($destination_id > 0) {

    $sql .= " AND p.destination_id = " . $destination_id;

}

if ($type_id > 0) {

    $sql .= " AND p.property_type_id = " . $type_id;

}

if ($search != "") {

    $escaped = mysqli_real_escape_string($conn, $search);

    $sql .= " AND (p.title LIKE '%$escaped%' OR d.destination_name LIKE '%$escaped%')";

}

switch ($sort) {

    case "price_asc":
        $sql .= " ORDER BY p.discounted_price ASC";
        break;

    case "price_desc":
        $sql .= " ORDER BY p.discounted_price DESC";
        break;

    case "name_asc":
        $sql .= " ORDER BY p.title ASC";
        break;

    case "name_desc":
        $sql .= " ORDER BY p.title DESC";
        break;

    default:
        $sql .= " ORDER BY p.discount_percent DESC, p.title ASC";
        break;

}

$result = mysqli_query($conn, $sql);

$properties = [];

while ($row = mysqli_fetch_assoc($result)) {

    $properties[] = $row;

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "All Properties - Godaddy Booking";

$currentPage = "properties";

include "includes/header.php";

?>

<section class="listing-page">

    <div class="container">

        <div class="section-heading" style="margin-bottom:35px;">

            <div class="eyebrow">Stay With Us</div>

            <h2>All Properties</h2>

            <p>Browse every available property, or narrow it down below.</p>

        </div>

        <form method="GET" class="listing-toolbar" id="listingToolbar">

            <?php if ($destination_id > 0) { ?>
                <input type="hidden" name="destination_id" value="<?= $destination_id; ?>">
            <?php } ?>

            <?php if ($type_id > 0) { ?>
                <input type="hidden" name="type_id" value="<?= $type_id; ?>">
            <?php } ?>

            <div class="search-input-wrap">

                <div class="search-box-group">

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by property name or place..."
                        value="<?= htmlspecialchars($search); ?>">

                    <button type="submit" class="search-submit-btn" aria-label="Search">

                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2.2"></circle>
                            <line x1="16.4" y1="16.4" x2="21" y2="21" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"></line>
                        </svg>

                    </button>

                </div>

            </div>

            <div class="filter-group">

                <label class="filter-label">

                    <i class="fa-solid fa-filter"></i>

                    <select name="sort" id="sortSelect">

                        <option value="" <?= ($sort == '') ? 'selected' : ''; ?>>Recommended</option>

                        <option value="price_asc" <?= ($sort == 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>

                        <option value="price_desc" <?= ($sort == 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>

                        <option value="name_asc" <?= ($sort == 'name_asc') ? 'selected' : ''; ?>>Name: A - Z</option>

                        <option value="name_desc" <?= ($sort == 'name_desc') ? 'selected' : ''; ?>>Name: Z - A</option>

                    </select>

                </label>

            </div>

        </form>

        <script>
            document.getElementById("sortSelect").addEventListener("change", function () {
                document.getElementById("listingToolbar").submit();
            });
        </script>


        <div class="property-grid">

            <?php if (empty($properties)) { ?>

                <p style="text-align:center; grid-column:1/-1; color:var(--ink-soft); padding:40px 0;">

                    No properties matched your search. Try a different name or place.

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

<?php include "includes/footer.php"; ?>