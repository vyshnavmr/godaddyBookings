<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
GET ROOM ID
====================================================*/

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: rooms.php");
    exit();
}


/*====================================================
FETCH ROOM + PROPERTY
====================================================*/

$sql = "

SELECT

r.*,

p.title AS property_title,

p.property_code,

p.id AS property_id

FROM property_rooms r

INNER JOIN properties p ON r.property_id = p.id

WHERE r.id = ?

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$room) {

    $_SESSION['errors'][] = "Room not found.";

    header("Location: rooms.php");

    exit();

}


/*====================================================
MANAGER OWNERSHIP CHECK
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$accessDenied = false;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array((int)$room['property_id'], $managerPropertyIds)) {

        $accessDenied = true;

    }

}


/*====================================================
FETCH ROOM IMAGES
====================================================*/

$coverImage = null;

$galleryImages = [];

if (!$accessDenied) {

    $sql = "SELECT * FROM property_room_images WHERE room_id=? ORDER BY is_cover DESC, id ASC";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    $imgResult = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($imgResult)) {

        if ($row['is_cover']) {
            $coverImage = $row;
        } else {
            $galleryImages[] = $row;
        }

    }

}

$statusOptions = ["Available", "Unavailable"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Room Type</title>

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

        <a href="rooms.php?property_id=<?= (int)$room['property_id']; ?>" class="btn-back">

            <i class="fa-solid fa-arrow-left"></i>

        </a>

        <h1>Edit Room Type</h1>

    </div>

    <?php if ($accessDenied) { ?>

        <div class="access-denied">

            <i class="fa-solid fa-lock"></i>

            <h2>Access Denied</h2>

            <p>This room isn't part of one of your properties.</p>

            <a href="rooms.php" class="btn-back-link">Back to Rooms</a>

        </div>

    <?php } else { ?>

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

    <div class="card property-summary-card">

        <h2><?= htmlspecialchars($room['property_title']); ?></h2>

        <p>

            <span class="badge normal"><?= htmlspecialchars($room['property_code']); ?></span>

        </p>

    </div>

    <form
        action="room_update.php"
        method="POST"
        enctype="multipart/form-data"
        id="roomEditForm">

        <input type="hidden" name="id" value="<?= (int)$room['id']; ?>">

        <div class="card">

            <h2>Room Details</h2>

            <div class="row">

                <div class="form-group">

                    <label>Room Name</label>

                    <input
                        type="text"
                        name="room_name"
                        value="<?= htmlspecialchars($room['room_name']); ?>"
                        required>

                </div>

                <div class="form-group">

                    <label>Status</label>

                    <select name="status">

                        <?php foreach ($statusOptions as $status) { ?>

                            <option value="<?= $status; ?>" <?= ($room['status'] == $status) ? 'selected' : ''; ?>>

                                <?= $status; ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

            </div>

            <div class="form-group">

                <label>Description</label>

                <textarea name="description" rows="3"><?= htmlspecialchars($room['description'] ?? ''); ?></textarea>

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
                        value="<?= htmlspecialchars($room['price']); ?>"
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
                        value="<?= htmlspecialchars($room['discount_percent'] ?? '0'); ?>"
                        placeholder="0">

                    <small class="hint">
                        Leave blank for no discount.
                        Price after discount (reference only):
                        <strong>₹<?= number_format($room['discounted_price'] ?? $room['price'], 2); ?></strong>
                        <span id="roomDiscountPreview"></span>
                    </small>

                </div>

            </div>

            <div class="row">

                <div class="form-group">

                    <label>Max Guests</label>

                    <input
                        type="number"
                        name="max_guests"
                        min="1"
                        value="<?= (int)$room['max_guests']; ?>">

                </div>

                <div class="form-group">

                    <label>Total Rooms of This Type</label>

                    <input
                        type="number"
                        name="total_rooms"
                        min="1"
                        value="<?= (int)$room['total_rooms']; ?>">

                    <small class="hint">How many physical rooms of this type exist (for inventory).</small>

                </div>

            </div>

        </div>

        <div class="card">

            <h2>Room Images</h2>

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
                    name="cover_image"
                    accept="image/*">

                <small class="hint">Leave empty to keep the current cover image.</small>

            </div>

            <?php if (!empty($galleryImages)) { ?>

                <div class="form-group">

                    <label>Current Gallery Images</label>

                    <div class="existing-gallery">

                        <?php foreach ($galleryImages as $img) { ?>

                            <div class="existing-gallery-item">

                                <img
                                    src="../../assets/uploads/<?= htmlspecialchars($img['image_path']); ?>"
                                    alt="Room Image">

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

            <div class="form-group">

                <label>Add More Images</label>

                <input
                    type="file"
                    name="gallery_images[]"
                    multiple
                    accept="image/*">

            </div>

        </div>

        <div class="submit-area">

            <a
                href="rooms.php?property_id=<?= (int)$room['property_id']; ?>"
                class="btn-cancel"
                id="cancelBtn">

                <i class="fa-solid fa-xmark"></i>

                Cancel

            </a>

            <button
                class="btn-save"
                type="submit">

                <i class="fa-solid fa-floppy-disk"></i>

                Update Room Type

            </button>

        </div>

    </form>

    <?php } ?>

</div>

<script src="../assets/js/property.js"></script>

<?php if (!$accessDenied) { ?>

<script>

    document.getElementById("cancelBtn").addEventListener("click", function (e) {

        const confirmCancel = confirm("Discard your changes to this room type?");

        if (!confirmCancel) {
            e.preventDefault();
        }

    });

    const roomPriceInput = document.getElementById("roomPriceInput");
    const roomDiscountInput = document.getElementById("roomDiscountInput");
    const roomDiscountPreview = document.getElementById("roomDiscountPreview");

    function updateRoomDiscountPreview() {

        const price = parseFloat(roomPriceInput.value) || 0;
        const discount = parseFloat(roomDiscountInput.value) || 0;

        if (price > 0 && discount > 0) {

            const finalPrice = price - (price * discount / 100);

            roomDiscountPreview.textContent = " (≈ ₹" + finalPrice.toLocaleString("en-IN", { maximumFractionDigits: 2 }) + " with your unsaved changes)";

        } else {

            roomDiscountPreview.textContent = "";

        }

    }

    roomPriceInput.addEventListener("input", updateRoomDiscountPreview);
    roomDiscountInput.addEventListener("input", updateRoomDiscountPreview);

</script>

<?php } ?>

</body>

</html>