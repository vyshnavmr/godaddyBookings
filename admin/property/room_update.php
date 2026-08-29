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

$id = (int)($_POST['id'] ?? 0);

$room_name = trim($_POST['room_name'] ?? "");

$description = trim($_POST['description'] ?? "");

$price = (float)($_POST['price'] ?? 0);

$discount_input = trim($_POST['discount_percent'] ?? "");

$discount_percent = ($discount_input === "") ? 0 : (float)$discount_input;

$max_guests = (int)($_POST['max_guests'] ?? 1);

$total_rooms = (int)($_POST['total_rooms'] ?? 1);

$status = in_array($_POST['status'] ?? "", $statusOptions) ? $_POST['status'] : "Available";

$delete_images = $_POST['delete_images'] ?? [];

if ($id <= 0) {

    header("Location: rooms.php");

    exit();

}


/*====================================================
FETCH ROOM + PROPERTY (also confirms room exists)
====================================================*/

$sql = "

SELECT

r.*,

p.title AS property_title,

p.property_code,

p.property_type_id,

p.id AS property_id

FROM property_rooms r

INNER JOIN properties p ON r.property_id = p.id

WHERE r.id = ?

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$existingRoom = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$existingRoom) {

    $_SESSION['errors'][] = "Room not found.";

    header("Location: rooms.php");

    exit();

}

$property_id = (int)$existingRoom['property_id'];


/*====================================================
MANAGER OWNERSHIP CHECK - server-side, never trust the
GET page's check alone
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array($property_id, $managerPropertyIds)) {

        $_SESSION['errors'][] = "You don't have permission to edit this room.";

        header("Location: rooms.php");

        exit();

    }

}


/*====================================================
VALIDATE FIELDS
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

if (count($errors) > 0) {

    $_SESSION['errors'] = $errors;

    header("Location: room_edit.php?id=" . $id);

    exit();

}


/*====================================================
DETERMINE THIS ROOM'S UPLOAD FOLDER
====================================================*/

$sql = "SELECT image_path FROM property_room_images WHERE room_id=? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$existingImageRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($existingImageRow) {

    $room_relative_dir = dirname($existingImageRow['image_path']);

} else {

    /* Fallback: rebuild the same way rooms_save.php does, in case
       this room somehow has no images yet */

    $sql = "SELECT image_path FROM property_images WHERE property_id=? ORDER BY is_cover DESC LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $property_id);

    mysqli_stmt_execute($stmt);

    $baseImageRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($baseImageRow) {

        $base_relative_dir = dirname($baseImageRow['image_path']);

    } else {

        $sql = "SELECT type_name FROM property_types WHERE id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $existingRoom['property_type_id']);

        mysqli_stmt_execute($stmt);

        $type = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        $type_folder = preg_replace('/[^A-Za-z0-9_-]/', '_', $type['type_name']);

        $safe_title = strtolower(trim($existingRoom['property_title']));

        $safe_title = preg_replace('/[^a-z0-9]+/', '-', $safe_title);

        $safe_title = trim($safe_title, '-');

        $folder_name = $property_id . "_" . $existingRoom['property_code'] . "_" . $safe_title;

        $base_relative_dir = "properties/" . $type_folder . "/" . $folder_name;

    }

    $room_relative_dir = $base_relative_dir . "/rooms/" . $id;

}

$room_upload_dir = dirname(__DIR__, 2) . "/assets/uploads/" . $room_relative_dir . "/";

if (!is_dir($room_upload_dir)) {

    mkdir($room_upload_dir, 0777, true);

}


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

try {

    /*====================================================
    UPDATE ROOM DETAILS
    ====================================================*/

    $discounted_price = round($price - ($price * $discount_percent / 100), 2);

    $sql = "

    UPDATE property_rooms SET

    room_name=?,

    description=?,

    price=?,

    discounted_price=?,

    discount_percent=?,

    max_guests=?,

    total_rooms=?,

    status=?

    WHERE id=?

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(

        $stmt,

        "ssdddiisi",

        $room_name,
        $description,
        $price,
        $discounted_price,
        $discount_percent,
        $max_guests,
        $total_rooms,
        $status,
        $id

    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }


    /*====================================================
    REPLACE COVER IMAGE (IF UPLOADED)
    ====================================================*/

    if (!empty($_FILES['cover_image']['name'])) {

        $extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowed)) {

            throw new Exception("Invalid Cover Image type.");

        }

        foreach ($allowed as $ext) {

            $oldCover = $room_upload_dir . "cover." . $ext;

            if (is_file($oldCover)) {
                unlink($oldCover);
            }

        }

        $cover_name = "cover." . $extension;

        $cover_destination = $room_upload_dir . $cover_name;

        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $cover_destination)) {

            throw new Exception("Unable to upload Cover Image.");

        }

        $cover_path = $room_relative_dir . "/" . $cover_name;

        $sql = "SELECT id FROM property_room_images WHERE room_id=? AND is_cover=1 LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $id);

        mysqli_stmt_execute($stmt);

        $coverRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($coverRow) {

            $sql = "UPDATE property_room_images SET image_path=? WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "si", $cover_path, $coverRow['id']);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }

        } else {

            $sql = "INSERT INTO property_room_images(room_id, image_path, is_cover) VALUES(?,?,1)";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "is", $id, $cover_path);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }

        }

    }


    /*====================================================
    DELETE SELECTED GALLERY IMAGES
    ====================================================*/

    if (!empty($delete_images)) {

        foreach ($delete_images as $imageId) {

            $imageId = (int)$imageId;

            $sql = "SELECT image_path FROM property_room_images
                    WHERE id=? AND room_id=? AND is_cover=0";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "ii", $imageId, $id);

            mysqli_stmt_execute($stmt);

            $imgRow = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if ($imgRow) {

                $filePath = dirname(__DIR__, 2) . "/assets/uploads/" . $imgRow['image_path'];

                if (is_file($filePath)) {
                    unlink($filePath);
                }

                $sql = "DELETE FROM property_room_images WHERE id=?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "i", $imageId);

                mysqli_stmt_execute($stmt);

            }

        }

    }


    /*====================================================
    UPLOAD NEW GALLERY IMAGES
    ====================================================*/

    if (!empty($_FILES['gallery_images']['name'][0])) {

        /* Determine the next gallery filename number by looking at
           existing filenames (not a stored order column, since one
           doesn't exist here) - this avoids collisions if an image
           was deleted, leaving a gap in the numbering. */

        $sql = "SELECT image_path FROM property_room_images WHERE room_id=? AND is_cover=0";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $id);

        mysqli_stmt_execute($stmt);

        $existingGalleryResult = mysqli_stmt_get_result($stmt);

        $order = 2;

        while ($row = mysqli_fetch_assoc($existingGalleryResult)) {

            if (preg_match('/gallery_(\d+)\./', basename($row['image_path']), $matches)) {

                $num = (int)$matches[1];

                if ($num >= $order) {
                    $order = $num + 1;
                }

            }

        }

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

                mysqli_stmt_bind_param($stmt, "is", $id, $gallery_path);

                mysqli_stmt_execute($stmt);

                $order++;

            }

        }

    }


    /*====================================================
    COMMIT TRANSACTION
    ====================================================*/

    mysqli_commit($conn);

    $_SESSION['success'] = "Room Type Updated Successfully.";

    header("Location: rooms.php?property_id=" . $property_id);

    exit();

}


/*====================================================
ROLLBACK
====================================================*/

catch (Exception $e) {

    mysqli_rollback($conn);

    $_SESSION['errors'][] = $e->getMessage();

    header("Location: room_edit.php?id=" . $id);

    exit();

}

?>