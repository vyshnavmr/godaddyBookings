<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: rooms.php");

    exit();

}

$allowed = ["jpg", "jpeg", "png", "webp"];

$statusOptions = ["Available", "Unavailable"];


/*====================================================
READ FORM DATA
====================================================*/

$property_id = (int)($_POST['property_id'] ?? 0);

$room_name = trim($_POST['room_name'] ?? "");

$description = trim($_POST['description'] ?? "");

$price = (float)($_POST['price'] ?? 0);

$discount_input = trim($_POST['discount_percent'] ?? "");

$discount_percent = ($discount_input === "") ? 0 : (float)$discount_input;

$max_guests = (int)($_POST['max_guests'] ?? 1);

$total_rooms = (int)($_POST['total_rooms'] ?? 1);

$status = in_array($_POST['status'] ?? "", $statusOptions) ? $_POST['status'] : "Available";


/*====================================================
VALIDATE PROPERTY + OWNERSHIP
====================================================*/

if ($property_id <= 0) {

    $_SESSION['errors'][] = "Invalid property.";

    header("Location: rooms.php");

    exit();

}

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array($property_id, $managerPropertyIds)) {

        $_SESSION['errors'][] = "You don't have permission to manage rooms for this property.";

        header("Location: rooms.php");

        exit();

    }

}

$sql = "SELECT * FROM properties WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $property_id);

mysqli_stmt_execute($stmt);

$property = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$property) {

    $_SESSION['errors'][] = "Property not found.";

    header("Location: rooms.php");

    exit();

}


/*====================================================
VALIDATE ROOM FIELDS
====================================================*/

$errors = [];

if ($room_name == "")
    $errors[] = "Room Name is required.";

if ($price <= 0)
    $errors[] = "Price must be greater than zero.";

if ($discount_percent < 0 || $discount_percent > 100)
    $errors[] = "Discount must be between 0 and 100.";

if ($max_guests < 1)
    $errors[] = "Max Guests must be at least 1.";

if ($total_rooms < 1)
    $errors[] = "Total Rooms must be at least 1.";

if (empty($_FILES['cover_image']['name']))
    $errors[] = "Please select a Cover Image for this room.";

if (count($errors) > 0) {

    $_SESSION['errors'] = $errors;

    header("Location: rooms.php?property_id=" . $property_id);

    exit();

}


/*====================================================
DETERMINE PROPERTY'S BASE UPLOAD FOLDER

Rooms live in a subfolder of the property's existing upload
folder, found the same way update.php locates it - via any
existing property_images row for this property.
====================================================*/

$sql = "SELECT image_path
        FROM property_images
        WHERE property_id=?
        ORDER BY is_cover DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $property_id);

mysqli_stmt_execute($stmt);

$baseImageRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($baseImageRow) {

    $base_relative_dir = dirname($baseImageRow['image_path']);

} else {

    /* Fallback: property has no images yet - build the folder the
       same way save.php does when a property is first created */

    $sql = "SELECT type_name FROM property_types WHERE id=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $property['property_type_id']);

    mysqli_stmt_execute($stmt);

    $type = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $type_folder = preg_replace('/[^A-Za-z0-9_-]/', '_', $type['type_name']);

    $safe_title = strtolower(trim($property['title']));

    $safe_title = preg_replace('/[^a-z0-9]+/', '-', $safe_title);

    $safe_title = trim($safe_title, '-');

    $folder_name = $property_id . "_" . $property['property_code'] . "_" . $safe_title;

    $base_relative_dir = "properties/" . $type_folder . "/" . $folder_name;

}

$base_upload_dir = dirname(__DIR__, 2) . "/assets/uploads/" . $base_relative_dir . "/";


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

$room_upload_dir = null;

try {

    /*====================================================
    COMPUTE DISCOUNTED PRICE

    Unlike properties.discounted_price, property_rooms.discounted_price
    is a plain column (not database-generated), so it must be
    calculated and stored explicitly here.
    ====================================================*/

    $discounted_price = round($price - ($price * $discount_percent / 100), 2);

    /*====================================================
    INSERT ROOM TYPE
    ====================================================*/

    $sql = "

    INSERT INTO property_rooms(

    property_id,

    room_name,

    description,

    price,

    discounted_price,

    discount_percent,

    max_guests,

    total_rooms,

    status

    )

    VALUES(

    ?,?,?,?,?,?,?,?,?

    )

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(

        $stmt,

        "issdddiis",

        $property_id,
        $room_name,
        $description,
        $price,
        $discounted_price,
        $discount_percent,
        $max_guests,
        $total_rooms,
        $status

    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }

    $room_id = mysqli_insert_id($conn);


    /*====================================================
    CREATE THIS ROOM'S IMAGE FOLDER
    ====================================================*/

    $room_relative_dir = $base_relative_dir . "/rooms/" . $room_id;

    $room_upload_dir = dirname(__DIR__, 2) . "/assets/uploads/" . $room_relative_dir . "/";

    if (!is_dir($room_upload_dir)) {

        if (!mkdir($room_upload_dir, 0777, true)) {

            throw new Exception("Unable to create room image folder.");

        }

    }


    /*====================================================
    UPLOAD COVER IMAGE
    ====================================================*/

    $extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowed)) {

        throw new Exception("Invalid Cover Image type.");

    }

    $cover_name = "cover." . $extension;

    $cover_destination = $room_upload_dir . $cover_name;

    if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_destination)) {

        throw new Exception("Unable to upload Cover Image.");

    }

    $cover_path = $room_relative_dir . "/" . $cover_name;

    $sql = "INSERT INTO property_room_images(room_id, image_path, is_cover)
            VALUES(?,?,1)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "is", $room_id, $cover_path);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }


    /*====================================================
    UPLOAD GALLERY IMAGES
    ====================================================*/

    $order = 2;

    if (!empty($_FILES['gallery_images']['name'][0])) {

        foreach ($_FILES['gallery_images']['name'] as $key => $name) {

            if ($name == "")
                continue;

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowed)) {
                continue;
            }

            $gallery_name = "gallery_" . $order . "." . $extension;

            $destination = $room_upload_dir . $gallery_name;

            if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$key], $destination)) {

                $gallery_path = $room_relative_dir . "/" . $gallery_name;

                $sql = "INSERT INTO property_room_images(room_id, image_path, is_cover)
                        VALUES(?,?,0)";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "is", $room_id, $gallery_path);

                mysqli_stmt_execute($stmt);

                $order++;

            }

        }

    }


    /*====================================================
    COMMIT TRANSACTION
    ====================================================*/

    mysqli_commit($conn);

    $_SESSION['success'] = "Room Type Added Successfully.";

    header("Location: rooms.php?property_id=" . $property_id);

    exit();

}


/*====================================================
ROLLBACK
====================================================*/

catch (Exception $e) {

    mysqli_rollback($conn);

    /* Clean up any uploaded files for this room */

    if ($room_upload_dir && is_dir($room_upload_dir)) {

        $files = array_diff(scandir($room_upload_dir), ['.', '..']);

        foreach ($files as $file) {
            unlink($room_upload_dir . $file);
        }

        rmdir($room_upload_dir);

    }

    $_SESSION['errors'][] = $e->getMessage();

    header("Location: rooms.php?property_id=" . $property_id);

    exit();

}

?>