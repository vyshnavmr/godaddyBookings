<?php

require_once "config/db.php";

/*====================================================
DEVICE DETECTION - server-side pagination needs to know
how many properties to fetch per page BEFORE rendering,
so this uses a basic user-agent check. Not perfectly
accurate for every device, but a reasonable approximation
for deciding grid density (2 cols mobile / 4 cols desktop).
====================================================*/

$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$isMobileDevice = (bool) preg_match('/Mobi|Android|iPhone|iPod/i', $userAgent);

$perPage = $isMobileDevice ? 18 : 28;


/*====================================================
FILTERS FROM URL
====================================================*/

$search         = trim($_GET['search'] ?? "");

$sort           = $_GET['sort'] ?? "";

$destination_id = (int)($_GET['destination_id'] ?? 0);

$type_id        = (int)($_GET['type_id'] ?? 0);

$guests         = (int)($_GET['guests'] ?? 0);

$priceMin       = trim($_GET['price_min'] ?? "");

$priceMax       = trim($_GET['price_max'] ?? "");

$page           = max(1, (int)($_GET['page'] ?? 1));

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

    unset($params['page']);

    $query = http_build_query($params);

    return "properties.php" . ($query != "" ? "?$query" : "");

}


/*====================================================
BUILD PAGINATION LINKS - preserves every active filter,
only changes the page number
====================================================*/

function buildPageUrl($pageNum) {

    $params = $_GET;

    $params['page'] = $pageNum;

    return "properties.php?" . http_build_query($params);

}


/*====================================================
SHARED WHERE CLAUSE - built once, reused for both the
COUNT query (to know how many pages exist) and the main
SELECT query (to fetch just this page's rows)
====================================================*/

$whereClause = " WHERE p.status = 'Available' ";

if ($destination_id > 0) {

    $whereClause .= " AND p.destination_id = " . $destination_id;

}

if ($type_id > 0) {

    $whereClause .= " AND p.property_type_id = " . $type_id;

}

if ($guests > 0) {

    $whereClause .= "

    AND EXISTS (

        SELECT 1 FROM property_rooms pr

        WHERE pr.property_id = p.id

        AND pr.status = 'Available'

        AND pr.max_guests >= " . $guests . "

    )";

}

if ($priceMin !== "" && is_numeric($priceMin)) {

    $whereClause .= " AND COALESCE(p.discounted_price, p.price) >= " . (float)$priceMin;

}

if ($priceMax !== "" && is_numeric($priceMax)) {

    $whereClause .= " AND COALESCE(p.discounted_price, p.price) <= " . (float)$priceMax;

}

if ($search != "") {

    $escaped = mysqli_real_escape_string($conn, $search);

    $whereClause .= "

    AND (

        p.title LIKE '%$escaped%'

        OR d.destination_name LIKE '%$escaped%'

        OR p.address LIKE '%$escaped%'

        OR t.type_name LIKE '%$escaped%'

    )";

}


/*====================================================
COUNT TOTAL MATCHING PROPERTIES - needed to calculate
how many pages exist, before fetching just this page's rows
====================================================*/

$countSql = "

SELECT COUNT(*) AS total

FROM properties p

INNER JOIN destinations d ON p.destination_id = d.id

INNER JOIN property_types t ON p.property_type_id = t.id

" . $whereClause;

$countResult = mysqli_query($conn, $countSql);

$totalProperties = (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalProperties / $perPage));

if ($page > $totalPages) {

    $page = $totalPages;

}

$offset = ($page - 1) * $perPage;


/*====================================================
ORDER BY
====================================================*/

$orderBy = "";

switch ($sort) {

    case "price_asc":
        $orderBy = " ORDER BY p.discounted_price ASC";
        break;

    case "price_desc":
        $orderBy = " ORDER BY p.discounted_price DESC";
        break;

    case "name_asc":
        $orderBy = " ORDER BY p.title ASC";
        break;

    case "name_desc":
        $orderBy = " ORDER BY p.title DESC";
        break;

    default:
        $orderBy = " ORDER BY p.discount_percent DESC, p.title ASC";
        break;

}


/*====================================================
PROPERTY QUERY - this page's rows only, via LIMIT/OFFSET
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

" . $whereClause . $orderBy . "

LIMIT $perPage OFFSET $offset

";

$result = mysqli_query($conn, $sql);

$properties = [];

while ($row = mysqli_fetch_assoc($result)) {

    $properties[] = $row;

}


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "All Properties - Godaddy Booking";

$canonicalParams = [];

if ($destination_id > 0) $canonicalParams['destination_id'] = $destination_id;

if ($type_id > 0) $canonicalParams['type_id'] = $type_id;

$canonicalUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . "/properties.php" . (!empty($canonicalParams) ? "?" . http_build_query($canonicalParams) : "");

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

            <?php if ($guests > 0) { ?>
                <input type="hidden" name="guests" value="<?= $guests; ?>">
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

                <div class="price-range-group">

                    <i class="fa-solid fa-indian-rupee-sign price-range-icon"></i>

                    <input type="number" name="price_min" class="price-range-input" placeholder="Min" min="0" value="<?= htmlspecialchars($priceMin); ?>">

                    <span class="price-range-divider"></span>

                    <input type="number" name="price_max" class="price-range-input" placeholder="Max" min="0" value="<?= htmlspecialchars($priceMax); ?>">

                </div>

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

        <?php if ($totalPages > 1) { ?>

            <div class="pagination">

                <?php if ($page > 1) { ?>

                    <a href="<?= buildPageUrl($page - 1); ?>" class="pagination-btn pagination-prev">

                        <i class="fa-solid fa-chevron-left"></i> Previous

                    </a>

                <?php } else { ?>

                    <span class="pagination-btn pagination-disabled">

                        <i class="fa-solid fa-chevron-left"></i> Previous

                    </span>

                <?php } ?>

                <div class="pagination-numbers">

                    <?php

                    /* Show a window of page numbers around the current
                       page, rather than every single page number when
                       there are many - keeps the control usable even
                       with dozens of pages */

                    $windowStart = max(1, $page - 2);

                    $windowEnd = min($totalPages, $page + 2);

                    if ($windowStart > 1) { ?>

                        <a href="<?= buildPageUrl(1); ?>" class="pagination-number">1</a>

                        <?php if ($windowStart > 2) { ?>
                            <span class="pagination-ellipsis">…</span>
                        <?php } ?>

                    <?php } ?>

                    <?php for ($p = $windowStart; $p <= $windowEnd; $p++) { ?>

                        <a href="<?= buildPageUrl($p); ?>" class="pagination-number <?= ($p == $page) ? 'active' : ''; ?>"><?= $p; ?></a>

                    <?php } ?>

                    <?php if ($windowEnd < $totalPages) { ?>

                        <?php if ($windowEnd < $totalPages - 1) { ?>
                            <span class="pagination-ellipsis">…</span>
                        <?php } ?>

                        <a href="<?= buildPageUrl($totalPages); ?>" class="pagination-number"><?= $totalPages; ?></a>

                    <?php } ?>

                </div>

                <?php if ($page < $totalPages) { ?>

                    <a href="<?= buildPageUrl($page + 1); ?>" class="pagination-btn pagination-next">

                        Next <i class="fa-solid fa-chevron-right"></i>

                    </a>

                <?php } else { ?>

                    <span class="pagination-btn pagination-disabled">

                        Next <i class="fa-solid fa-chevron-right"></i>

                    </span>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</section>

<?php include "includes/footer.php"; ?>