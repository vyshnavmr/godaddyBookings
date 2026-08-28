<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
MANAGER SCOPE
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];


/*====================================================
SELECTED PROPERTY (IF ANY)
====================================================*/

$property_id = (int)($_GET['property_id'] ?? 0);

$property = null;

$accessDenied = false;

$rooms = [];

if ($property_id > 0) {

    /* Managers can only manage rooms for their own properties */

    if ($isRestrictedManager && !in_array($property_id, $managerPropertyIds)) {

        $accessDenied = true;

    } else {

        $sql = "

        SELECT

        p.*,

        d.destination_name,

        t.type_name

        FROM properties p

        INNER JOIN destinations d ON p.destination_id = d.id

        INNER JOIN property_types t ON p.property_type_id = t.id

        WHERE p.id = ?

        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $property_id);

        mysqli_stmt_execute($stmt);

        $property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$property) {

            $_SESSION['errors'][] = "Property not found.";

            header("Location: rooms.php");

            exit();

        }

        /* Fetch room types for this property */

        $sql = "SELECT * FROM property_rooms WHERE property_id=? ORDER BY sort_order ASC, created_at ASC";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $property_id);

        mysqli_stmt_execute($stmt);

        $roomResult = mysqli_stmt_get_result($stmt);

        while ($room = mysqli_fetch_assoc($roomResult)) {

            $sql2 = "SELECT image_path FROM property_room_images WHERE room_id=? AND is_cover=1 LIMIT 1";

            $stmt2 = mysqli_prepare($conn, $sql2);

            mysqli_stmt_bind_param($stmt2, "i", $room['id']);

            mysqli_stmt_execute($stmt2);

            $coverRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

            $room['cover_image'] = $coverRow['image_path'] ?? null;

            $rooms[] = $room;

        }

    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Rooms</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/property.css">

    <link rel="stylesheet" href="../assets/css/property-edit.css">

    <link rel="stylesheet" href="../assets/css/rooms.css">

    <style>
        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 6px;
            background: #eee;
            color: #333;
            text-decoration: none;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            background: #e0e0e0;
        }

        @media (max-width: 600px) {
            .btn-cancel {
                width: 100%;
                justify-content: center;
                margin-right: 0;
                margin-bottom: 14px;
            }
        }

        .page-header-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 10px;
        }

        .page-header-row h1 {
            margin: 0;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #eef0f4;
            color: #333;
            text-decoration: none;
            flex-shrink: 0;
        }

        .btn-back:hover {
            background: #e2e4e9;
        }

        .access-denied {
            text-align: center;
            padding: 80px 20px;
            color: #888;
            background: #fff;
            border-radius: 14px;
        }

        .access-denied i {
            font-size: 42px;
            color: #ccc;
            margin-bottom: 14px;
        }

        .access-denied h2 {
            color: #333;
            margin-bottom: 8px;
        }

        .btn-back-link {
            display: inline-block;
            margin-top: 18px;
            padding: 10px 22px;
            border-radius: 6px;
            background: #4a6cf7;
            color: #fff;
            text-decoration: none;
        }
    </style>

</head>

<body>

<div class="container">

    <div class="page-header-row">

        <a href="../dashboard.php" class="btn-back">

            <i class="fa-solid fa-arrow-left"></i>

        </a>

        <h1>Manage Rooms</h1>

    </div>

    <?php if ($accessDenied) { ?>

        <div class="access-denied">

            <i class="fa-solid fa-lock"></i>

            <h2>Access Denied</h2>

            <p>This property isn't assigned to your account.</p>

            <a href="rooms.php" class="btn-back-link">Back to Rooms</a>

        </div>

    <?php } else { ?>

    <?php if (isset($_SESSION['success'])) { ?>

        <div class="alert success">

            <i class="fa-solid fa-circle-check"></i>

            <?= htmlspecialchars($_SESSION['success']); ?>

        </div>

        <?php unset($_SESSION['success']); ?>

    <?php } ?>

    <?php if (isset($_SESSION['errors'])) { ?>

        <div class="alert error">

            <i class="fa-solid fa-circle-exclamation"></i>

            <ul>

                <?php foreach ($_SESSION['errors'] as $error) { ?>

                    <li><?= htmlspecialchars($error); ?></li>

                <?php } ?>

            </ul>

        </div>

        <?php unset($_SESSION['errors']); ?>

    <?php } ?>

    <!-- ===========================
         PROPERTY SEARCH
    ============================ -->

    <div class="card">

        <h2>Select Property</h2>

        <div class="search-wrap">

            <input
                type="text"
                id="propertySearch"
                autocomplete="off"
                placeholder="Type a property name or code..."
                value="<?= $property ? htmlspecialchars($property['title'] . " (" . $property['property_code'] . ")") : ""; ?>">

            <div id="searchDropdown" class="search-dropdown"></div>

        </div>

    </div>

    <?php if ($property) { ?>

        <div class="card property-summary-card">

            <h2><?= htmlspecialchars($property['title']); ?></h2>

            <p>

                <span class="badge normal"><?= htmlspecialchars($property['property_code']); ?></span>

                <span class="badge normal"><?= htmlspecialchars($property['destination_name']); ?></span>

                <span class="badge normal"><?= htmlspecialchars($property['type_name']); ?></span>

            </p>

        </div>

        <!-- ===========================
             EXISTING ROOM TYPES
        ============================ -->

        <?php if (!empty($rooms)) { ?>

            <div class="card">

                <h2>Room Types (<?= count($rooms); ?>)</h2>

                <div class="room-type-grid">

                    <?php foreach ($rooms as $room) { ?>

                        <div class="room-type-card">

                            <div class="room-type-image">

                                <?php if ($room['cover_image']) { ?>

                                    <img src="../../assets/uploads/<?= htmlspecialchars($room['cover_image']); ?>" alt="<?= htmlspecialchars($room['room_name']); ?>">

                                <?php } else { ?>

                                    <div class="room-type-no-image"><i class="fa-solid fa-image"></i></div>

                                <?php } ?>

                                <span class="room-type-status-badge status-<?= strtolower($room['status']); ?>">

                                    <?= htmlspecialchars($room['status']); ?>

                                </span>

                            </div>

                            <div class="room-type-content">

                                <h3><?= htmlspecialchars($room['room_name']); ?></h3>

                                <div class="room-type-meta">

                                    <span><i class="fa-solid fa-user-group"></i> <?= (int)$room['max_guests']; ?> guests</span>

                                    <span><i class="fa-solid fa-bed"></i> <?= (int)$room['total_rooms']; ?> room<?= ((int)$room['total_rooms'] != 1) ? 's' : ''; ?></span>

                                </div>

                                <div class="room-type-price">

                                    <?php if ((float)$room['discount_percent'] > 0) { ?>

                                        <span class="price-original">₹<?= number_format($room['price'], 0); ?></span>

                                    <?php } ?>

                                    <span class="price-final">₹<?= number_format($room['discounted_price'] ?? $room['price'], 0); ?></span>

                                    <span class="price-unit">/ night</span>

                                </div>

                                <div class="room-type-actions">

                                    <a href="room_edit.php?id=<?= $room['id']; ?>" class="btn-room-edit">

                                        <i class="fa-solid fa-pen"></i> Edit

                                    </a>

                                    <a
                                        href="room_delete.php?id=<?= $room['id']; ?>&property_id=<?= $property_id; ?>"
                                        class="btn-room-delete"
                                        onclick="return confirm('Delete this room type and all its images? This cannot be undone.')">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            </div>

        <?php } ?>

        <!-- ===========================
             ADD NEW ROOM TYPE
        ============================ -->

        <div class="card">

            <h2>Add New Room Type</h2>

            <form action="rooms_save.php" method="POST" enctype="multipart/form-data">

                <input type="hidden" name="property_id" value="<?= (int)$property['id']; ?>">

                <div class="row">

                    <div class="form-group">

                        <label>Room Name</label>

                        <input type="text" name="room_name" placeholder="e.g. Deluxe Room, AC Suite" required>

                    </div>

                    <div class="form-group">

                        <label>Status</label>

                        <select name="status">

                            <option value="Available">Available</option>

                            <option value="Unavailable">Unavailable</option>

                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label>Description</label>

                    <textarea name="description" rows="3" placeholder="Optional details about this room type"></textarea>

                </div>

                <div class="row">

                    <div class="form-group">

                        <label>Price Per Night (₹)</label>

                        <input
                            type="number"
                            id="roomPriceInput"
                            name="price"
                            min="0"
                            step="0.01"
                            required>

                    </div>

                    <div class="form-group">

                        <label>Discount (%)</label>

                        <input
                            type="number"
                            id="roomDiscountInput"
                            name="discount_percent"
                            min="0"
                            max="100"
                            step="0.01"
                            placeholder="0">

                        <small class="hint">Leave blank for no discount. <span id="roomDiscountPreview"></span></small>

                    </div>

                </div>

                <div class="row">

                    <div class="form-group">

                        <label>Max Guests</label>

                        <input type="number" name="max_guests" min="1" value="2">

                    </div>

                    <div class="form-group">

                        <label>Total Rooms of This Type</label>

                        <input type="number" name="total_rooms" min="1" value="1">

                        <small class="hint">How many physical rooms of this type exist (for inventory).</small>

                    </div>

                </div>

                <div class="form-group">

                    <label>Cover Image</label>

                    <input type="file" name="cover_image" accept="image/*" required>

                </div>

                <div class="form-group">

                    <label>Gallery Images</label>

                    <input type="file" name="gallery_images[]" multiple accept="image/*">

                </div>

                <div class="submit-area">

                    <button type="submit" class="btn-save">

                        <i class="fa-solid fa-plus"></i> Add Room Type

                    </button>

                </div>

            </form>

        </div>

    <?php } else { ?>

        <div class="empty-state">

            <i class="fa-solid fa-magnifying-glass"></i>

            <h3>No Property Selected</h3>

            <p>Search for a property above to manage its room types.</p>

        </div>

    <?php } ?>

    <?php } ?>

</div>

<script src="../assets/js/property.js"></script>

<script>

    /*====================================================
    PROPERTY AUTOCOMPLETE SEARCH
    ====================================================*/

    const searchInput = document.getElementById("propertySearch");
    const dropdown = document.getElementById("searchDropdown");

    let searchTimer = null;

    if (searchInput) {

    searchInput.addEventListener("input", function () {

        const query = this.value.trim();

        clearTimeout(searchTimer);

        if (query.length === 0) {
            dropdown.innerHTML = "";
            dropdown.classList.remove("active");
            return;
        }

        searchTimer = setTimeout(function () {

            fetch("search_properties.php?q=" + encodeURIComponent(query))

                .then(function (res) { return res.json(); })

                .then(function (properties) {

                    dropdown.innerHTML = "";

                    if (properties.length === 0) {

                        dropdown.innerHTML = '<div class="search-dropdown-empty">No properties found</div>';

                        dropdown.classList.add("active");

                        return;

                    }

                    properties.forEach(function (property) {

                        const item = document.createElement("div");

                        item.className = "search-dropdown-item";

                        item.innerHTML =
                            "<strong>" + property.title + "</strong>" +
                            "<span>" + property.property_code + " &middot; " + property.destination_name + "</span>";

                        item.addEventListener("click", function () {

                            window.location.href = "rooms.php?property_id=" + property.id;

                        });

                        dropdown.appendChild(item);

                    });

                    dropdown.classList.add("active");

                })

                .catch(function () {

                    dropdown.innerHTML = '<div class="search-dropdown-empty">Search failed. Try again.</div>';

                    dropdown.classList.add("active");

                });

        }, 250);

    });

    document.addEventListener("click", function (e) {

        if (!e.target.closest(".search-wrap")) {

            dropdown.classList.remove("active");

        }

    });

    }


    /*====================================================
    LIVE DISCOUNT PREVIEW FOR NEW ROOM FORM
    ====================================================*/

    const roomPriceInput = document.getElementById("roomPriceInput");
    const roomDiscountInput = document.getElementById("roomDiscountInput");
    const roomDiscountPreview = document.getElementById("roomDiscountPreview");

    function updateRoomDiscountPreview() {

        if (!roomPriceInput || !roomDiscountInput || !roomDiscountPreview) return;

        const price = parseFloat(roomPriceInput.value) || 0;
        const discount = parseFloat(roomDiscountInput.value) || 0;

        if (price > 0 && discount > 0) {

            const finalPrice = price - (price * discount / 100);

            roomDiscountPreview.textContent = "≈ ₹" + finalPrice.toLocaleString("en-IN", { maximumFractionDigits: 2 }) + " after discount.";

        } else {

            roomDiscountPreview.textContent = "";

        }

    }

    if (roomPriceInput && roomDiscountInput) {
        roomPriceInput.addEventListener("input", updateRoomDiscountPreview);
        roomDiscountInput.addEventListener("input", updateRoomDiscountPreview);
    }

</script>

</body>

</html>