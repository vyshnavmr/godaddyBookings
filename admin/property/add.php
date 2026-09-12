<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/* Fetch Property Types */
$typeQuery = mysqli_query($conn, "SELECT * FROM property_types ORDER BY type_name ASC");

/* Fetch Destinations */
$destinationQuery = mysqli_query($conn, "SELECT * FROM destinations ORDER BY destination_name ASC");

/* Fetch Amenities */
$amenityQuery = mysqli_query($conn, "SELECT * FROM amenities ORDER BY amenity_name ASC");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Property</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/property.css">

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

        .hint {
            display: block;
            color: #888;
            font-size: 12px;
            margin-top: 4px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert.error {
            background: #fdeceb;
            color: #c0392b;
        }

        .alert ul {
            margin: 0;
            padding-left: 18px;
        }
    </style>

</head>

<body>

<div class="container">

    <h1>Add New Property</h1>

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
        action="save.php"
        method="POST"
        enctype="multipart/form-data"
        id="addPropertyForm">

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
                        placeholder="Example : RES001"
                        required>

                </div>

                <div class="form-group">

                    <label>Property Name</label>

                    <input
                        type="text"
                        name="title"
                        required>

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Destination</label>

                    <select
                        name="destination_id"
                        id="destinationSelect"
                        required>

                        <option value="">Choose Destination</option>

                        <?php while($destination = mysqli_fetch_assoc($destinationQuery)){ ?>

                            <option
                                value="<?= $destination['id']; ?>">

                                <?= htmlspecialchars($destination['destination_name']); ?>

                            </option>

                        <?php } ?>

                        <option value="new">+ Add New Destination</option>

                    </select>

                    <div id="newDestinationWrap" style="display:none; margin-top:10px;">

                        <input
                            type="text"
                            name="new_destination_name"
                            id="newDestinationInput"
                            placeholder="Enter new destination name">

                        <small class="hint">This will be added to the destinations list automatically when you save.</small>

                    </div>

                </div>

                <div class="form-group">

                    <label>Property Type</label>

                    <select
                        name="property_type_id"
                        required>

                        <option value="">Choose Property Type</option>

                        <?php while($type = mysqli_fetch_assoc($typeQuery)){ ?>

                            <option
                                value="<?= $type['id']; ?>">

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
                    rows="6"></textarea>

            </div>

            <div class="form-group">

                <label>Address</label>

                <textarea
                    name="address"
                    rows="3"></textarea>

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
                    name="google_map_link">

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Latitude</label>

                    <input
                        type="text"
                        name="latitude">

                </div>

                <div class="form-group">

                    <label>Longitude</label>

                    <input
                        type="text"
                        name="longitude">

                </div>

            </div>

        </div>

        <!-- Part 2 continues from here -->
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
                        placeholder="0">

                    <small class="hint">Leave blank for no discount. <span id="discountPreview"></span></small>

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Bedrooms</label>

                    <input
                        type="number"
                        name="bedrooms"
                        min="1"
                        value="1">

                </div>

                <div class="form-group">

                    <label>Bathrooms</label>

                    <input
                        type="number"
                        name="bathrooms"
                        min="1"
                        value="1">

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Maximum Guests</label>

                    <input
                        type="number"
                        name="max_guests"
                        min="1"
                        value="2">

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
                        name="check_in_time">

                </div>

                <div class="form-group">

                    <label>Check Out Time</label>

                    <input
                        type="time"
                        name="check_out_time">

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

                    <select
                        name="featured">

                        <option value="1">Yes</option>

                        <option value="0" selected>No</option>

                    </select>

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">
                        <option value="Available">
                            Available
                        </option>
                        <option value="Inactive">
                            Inactive
                        </option>
                    </select>

                </div>

            </div>

        </div>



        <!-- ===========================
             PROPERTY MANAGER
        ============================ -->

        <div class="card">

            <h2>Property Manager (Optional)</h2>

            <div class="form-group">

                <label>Manager Email</label>

                <input
                    type="email"
                    name="manager_email"
                    placeholder="manager@example.com">

                <small class="hint">If provided, a manager login will be created automatically with a random password. You can leave this blank and add it later.</small>

            </div>

        </div>



        <!-- ===========================
             AMENITIES
        ============================ -->

        <div class="card">

            <h2>Amenities</h2>

            <div class="amenities-grid">

                <?php while($amenity=mysqli_fetch_assoc($amenityQuery)){ ?>

                    <label class="checkbox">

                        <input
                            type="checkbox"
                            name="amenities[]"
                            value="<?= $amenity['id']; ?>">

                        <i class="<?= htmlspecialchars($amenity['icon'] ?: 'fa-solid fa-circle-check'); ?>"></i>

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

                <input
                    type="file"
                    id="coverImage"
                    name="cover_image"
                    accept="image/*"
                    required>

                <div class="image-preview">

                    <img
                        id="coverPreview"
                        src=""
                        alt="Cover Preview">

                </div>

            </div>

            <!-- Gallery -->

            <div class="form-group">

                <label>Gallery Images</label>

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

            <a
                href="list.php"
                class="btn-cancel"
                id="cancelBtn">

                <i class="fa-solid fa-xmark"></i>

                Cancel

            </a>

            <button
                class="btn-save"
                type="submit">

                <i class="fa-solid fa-floppy-disk"></i>

                Save Property

            </button>

        </div>

    </form>

</div>

<script src="../assets/js/property.js"></script>

<script>
    document.getElementById("cancelBtn").addEventListener("click", function (e) {

        const confirmCancel = confirm("Discard this new property and its selected images? This cannot be undone.");

        if (!confirmCancel) {
            e.preventDefault();
        }

    });

    /* Live "final price" preview - just a visual reference while
       filling the form. The real stored value is computed by the
       database itself once saved. */

    function updateDiscountPreview() {

        const price = parseFloat(document.getElementById("priceInput").value) || 0;
        const discount = parseFloat(document.getElementById("discountInput").value) || 0;
        const preview = document.getElementById("discountPreview");

        if (price > 0 && discount > 0) {

            const finalPrice = price - (price * discount / 100);

            preview.textContent = "≈ ₹" + finalPrice.toLocaleString("en-IN", { maximumFractionDigits: 2 }) + " after discount.";

        } else {

            preview.textContent = "";

        }

    }

    document.getElementById("priceInput").addEventListener("input", updateDiscountPreview);
    document.getElementById("discountInput").addEventListener("input", updateDiscountPreview);

        /* Show/hide the "new destination" text field based on the
       dropdown selection, and toggle its required attribute so
       the browser enforces it only when actually needed. */

    const destinationSelect = document.getElementById("destinationSelect");
    const newDestinationWrap = document.getElementById("newDestinationWrap");
    const newDestinationInput = document.getElementById("newDestinationInput");

    destinationSelect.addEventListener("change", function () {

        if (this.value === "new") {

            newDestinationWrap.style.display = "block";
            newDestinationInput.required = true;

        } else {

            newDestinationWrap.style.display = "none";
            newDestinationInput.required = false;
            newDestinationInput.value = "";

        }

    });

</script>

</body>

</html>