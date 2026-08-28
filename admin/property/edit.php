<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
GET PROPERTY ID
====================================================*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: list.php");
    exit();
}


/*====================================================
FETCH PROPERTY
====================================================*/

$sql = "SELECT * FROM properties WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$property = mysqli_fetch_assoc($result);

if (!$property) {

    $_SESSION['errors'][] = "Property not found.";

    header("Location: list.php");

    exit();

}


/*====================================================
FETCH DROPDOWN DATA
====================================================*/

$typeQuery = mysqli_query($conn, "SELECT * FROM property_types ORDER BY type_name ASC");

$destinationQuery = mysqli_query($conn, "SELECT * FROM destinations ORDER BY destination_name ASC");

$amenityQuery = mysqli_query($conn, "SELECT * FROM amenities ORDER BY amenity_name ASC");


/*====================================================
FETCH SELECTED AMENITIES
====================================================*/

$selectedAmenities = [];

$sql = "SELECT amenity_id FROM property_amenities WHERE property_id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$amenityResult = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($amenityResult)) {

    $selectedAmenities[] = (int)$row['amenity_id'];

}


/*====================================================
FETCH CURRENT MANAGER (IF ANY)
====================================================*/

$manager = null;

$sql = "SELECT a.*
        FROM property_managers pm
        INNER JOIN admins a ON pm.admin_id = a.id
        WHERE pm.property_id = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$managerResult = mysqli_stmt_get_result($stmt);

$manager = mysqli_fetch_assoc($managerResult);

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;


/*====================================================
FETCH PROPERTY IMAGES
====================================================*/

$sql = "SELECT * FROM property_images WHERE property_id=? ORDER BY is_cover DESC, display_order ASC";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$imagesResult = mysqli_stmt_get_result($stmt);

$coverImage = null;

$galleryImages = [];

while ($row = mysqli_fetch_assoc($imagesResult)) {

    if ($row['is_cover']) {
        $coverImage = $row;
    } else {
        $galleryImages[] = $row;
    }

}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Property</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/property.css">

    <link rel="stylesheet" href="../assets/css/property-edit.css">

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
    </style>

</head>

<body>

<div class="container">

    <h1>Edit Property</h1>

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

    <form
        action="update.php"
        method="POST"
        enctype="multipart/form-data">

        <input type="hidden" name="id" value="<?= (int)$property['id']; ?>">

        <!-- ===========================
             BASIC DETAILS
        ============================ -->

        <div class="card">

            <h2>Basic Information</h2>

            <div class="row">

                <div class="form-group">

                    <label>Property Code</label>

                    <input
                        type="text"
                        name="property_code"
                        value="<?= htmlspecialchars($property['property_code']); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Property Name</label>

                    <input
                        type="text"
                        name="title"
                        value="<?= htmlspecialchars($property['title']); ?>"
                        required>

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Destination</label>

                    <select
                        name="destination_id"
                        required>

                        <option value="">Choose Destination</option>

                        <?php while ($destination = mysqli_fetch_assoc($destinationQuery)) { ?>

                            <option
                                value="<?= $destination['id']; ?>"
                                <?= ($destination['id'] == $property['destination_id']) ? 'selected' : ''; ?>>

                                <?= htmlspecialchars($destination['destination_name']); ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>Property Type</label>

                    <select
                        name="property_type_id"
                        required>

                        <option value="">Choose Property Type</option>

                        <?php while ($type = mysqli_fetch_assoc($typeQuery)) { ?>

                            <option
                                value="<?= $type['id']; ?>"
                                <?= ($type['id'] == $property['property_type_id']) ? 'selected' : ''; ?>>

                                <?= htmlspecialchars($type['type_name']); ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

            </div>

            <div class="form-group">

                <label>Description</label>

                <textarea
                    name="description"
                    rows="6"><?= htmlspecialchars($property['description']); ?></textarea>

            </div>

            <div class="form-group">

                <label>Address</label>

                <textarea
                    name="address"
                    rows="3"><?= htmlspecialchars($property['address']); ?></textarea>

            </div>

        </div>

        <!-- ===========================
             LOCATION
        ============================ -->

        <div class="card">

            <h2>Location</h2>

            <div class="form-group">

                <label>Google Maps Link</label>

                <input
                    type="text"
                    name="google_map_link"
                    value="<?= htmlspecialchars($property['google_map_link']); ?>">

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Latitude</label>

                    <input
                        type="text"
                        name="latitude"
                        value="<?= htmlspecialchars($property['latitude'] ?? ''); ?>">

                </div>

                <div class="form-group">

                    <label>Longitude</label>

                    <input
                        type="text"
                        name="longitude"
                        value="<?= htmlspecialchars($property['longitude'] ?? ''); ?>">

                </div>

            </div>

        </div>

        <!-- ===========================
             PRICING
        ============================ -->

        <div class="card">

            <h2>Pricing & Capacity</h2>

            <div class="row">

                <div class="form-group">

                    <label>Price Per Night (₹)</label>

                    <input
                        type="number"
                        id="priceInput"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?= htmlspecialchars($property['price']); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Discount (%)</label>

                    <input
                        type="number"
                        id="discountInput"
                        name="discount_percent"
                        min="0"
                        max="100"
                        step="0.01"
                        value="<?= htmlspecialchars($property['discount_percent'] ?? '0'); ?>"
                        placeholder="0">

                    <small class="hint">
                        Leave blank for no discount.
                        Price after discount (reference only):
                        <strong>₹<?= number_format($property['discounted_price'] ?? $property['price'], 2); ?></strong>
                        <span id="discountPreview"></span>
                    </small>

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Bedrooms</label>

                    <input
                        type="number"
                        name="bedrooms"
                        min="1"
                        value="<?= (int)$property['bedrooms']; ?>">

                </div>

                <div class="form-group">

                    <label>Bathrooms</label>

                    <input
                        type="number"
                        name="bathrooms"
                        min="1"
                        value="<?= (int)$property['bathrooms']; ?>">

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Maximum Guests</label>

                    <input
                        type="number"
                        name="max_guests"
                        min="1"
                        value="<?= (int)$property['max_guests']; ?>">

                </div>

            </div>

        </div>

        <!-- ===========================
             CHECK IN / CHECK OUT
        ============================ -->

        <div class="card">

            <h2>Check In / Check Out</h2>

            <div class="row">

                <div class="form-group">

                    <label>Check In Time</label>

                    <input
                        type="time"
                        name="check_in_time"
                        value="<?= substr($property['check_in_time'] ?? '', 0, 5); ?>">

                </div>

                <div class="form-group">

                    <label>Check Out Time</label>

                    <input
                        type="time"
                        name="check_out_time"
                        value="<?= substr($property['check_out_time'] ?? '', 0, 5); ?>">

                </div>

            </div>

        </div>

        <!-- ===========================
             PROPERTY OPTIONS
        ============================ -->

        <div class="card">

            <h2>Property Options</h2>

            <div class="row">

                <div class="form-group">

                    <label>Featured Property</label>

                    <select name="featured">

                        <option value="1" <?= ($property['featured'] == 1) ? 'selected' : ''; ?>>Yes</option>

                        <option value="0" <?= ($property['featured'] == 0) ? 'selected' : ''; ?>>No</option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <option value="Available" <?= ($property['status'] == 'Available') ? 'selected' : ''; ?>>Available</option>

                        <option value="Booked" <?= ($property['status'] == 'Booked') ? 'selected' : ''; ?>>Booked</option>

                        <option value="Inactive" <?= ($property['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>

                    </select>

                </div>

            </div>

        </div>

        <!-- ===========================
             PROPERTY MANAGER
        ============================ -->

        <div class="card">

            <h2>Property Manager</h2>

            <div class="form-group">

                <label>Manager Email</label>

                <input
                    type="email"
                    name="manager_email"
                    value="<?= htmlspecialchars($manager['email'] ?? ''); ?>"
                    placeholder="manager@example.com"
                    <?= $isRestrictedManager ? 'disabled' : ''; ?>>

                <?php if ($isRestrictedManager) { ?>

                    <small class="hint">Only administrators can change the manager email.</small>

                <?php } else { ?>

                    <small class="hint"><?= $manager ? 'A manager account already exists for this email.' : 'Leave blank if this property has no manager yet, or add one now.'; ?></small>

                <?php } ?>

            </div>

        </div>

        <!-- ===========================
             AMENITIES
        ============================ -->

        <div class="card">

            <h2>Amenities</h2>

            <div class="amenities-grid">

                <?php while ($amenity = mysqli_fetch_assoc($amenityQuery)) { ?>

                    <label class="checkbox">

                        <input
                            type="checkbox"
                            name="amenities[]"
                            value="<?= $amenity['id']; ?>"
                            <?= in_array($amenity['id'], $selectedAmenities) ? 'checked' : ''; ?>>

                        <?= htmlspecialchars($amenity['amenity_name']); ?>

                    </label>

                <?php } ?>

            </div>

        </div>

        <!-- ===========================
             PROPERTY IMAGES
        ============================ -->

        <div class="card">

            <h2>Property Images</h2>

            <!-- Cover -->

            <div class="form-group">

                <label>Cover Image</label>

                <?php if ($coverImage) { ?>

                    <div class="current-image">

                        <img
                            src="../../assets/uploads/<?= htmlspecialchars($coverImage['image_path']); ?>"
                            alt="Current Cover">

                        <span class="current-image-label">Current Cover</span>

                    </div>

                <?php } ?>

                <input
                    type="file"
                    id="coverImage"
                    name="cover_image"
                    accept="image/*">

                <small class="hint">Leave empty to keep the current cover image.</small>

                <div class="image-preview">

                    <img
                        id="coverPreview"
                        src=""
                        alt="Cover Preview">

                </div>

            </div>

            <!-- Existing Gallery -->

            <?php if (!empty($galleryImages)) { ?>

                <div class="form-group">

                    <label>Current Gallery Images</label>

                    <div class="existing-gallery">

                        <?php foreach ($galleryImages as $img) { ?>

                            <div class="existing-gallery-item">

                                <img
                                    src="../../assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                                    alt="Gallery Image">

                                <label class="checkbox delete-checkbox">

                                    <input
                                        type="checkbox"
                                        name="delete_images[]"
                                        value="<?= $img['id']; ?>">

                                    Delete

                                </label>

                            </div>

                        <?php } ?>

                    </div>

                </div>

            <?php } ?>

            <!-- New Gallery -->

            <div class="form-group">

                <label>Add New Gallery Images</label>

                <input
                    type="file"
                    id="galleryImages"
                    name="gallery_images[]"
                    multiple
                    accept="image/*">

                <div
                    id="galleryPreview"
                    class="gallery-preview">

                </div>

            </div>

        </div>

        <!-- ===========================
             BUTTON
        ============================ -->

        <div class="submit-area">

            <a href="list.php" class="btn-cancel">Cancel</a>

            <button
                class="btn-save"
                type="submit">

                <i class="fa-solid fa-floppy-disk"></i>

                Update Property

            </button>

        </div>

    </form>

</div>

<script src="../assets/js/property.js"></script>

<script>
    document.getElementById("cancelBtn").addEventListener("click", function (e) {

        const confirmCancel = confirm("Discard your changes to this property? This cannot be undone.");

        if (!confirmCancel) {
            e.preventDefault();
        }

    });

    /* Live "final price" preview - just a visual reference while
       editing. The real stored value is computed by the database
       itself once saved. */

    function updateDiscountPreview() {

        const price = parseFloat(document.getElementById("priceInput").value) || 0;
        const discount = parseFloat(document.getElementById("discountInput").value) || 0;
        const preview = document.getElementById("discountPreview");

        if (price > 0 && discount > 0) {

            const finalPrice = price - (price * discount / 100);

            preview.textContent = " (≈ ₹" + finalPrice.toLocaleString("en-IN", { maximumFractionDigits: 2 }) + " with your unsaved changes)";

        } else {

            preview.textContent = "";

        }

    }

    document.getElementById("priceInput").addEventListener("input", updateDiscountPreview);
    document.getElementById("discountInput").addEventListener("input", updateDiscountPreview);
</script>

</body>

</html>