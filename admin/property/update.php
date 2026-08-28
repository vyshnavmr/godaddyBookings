<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

require_once "../../config/crypto.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: list.php");

    exit();

}


/*====================================================
READ FORM DATA
====================================================*/

$id                  = (int)$_POST['id'];

$property_code       = trim($_POST['property_code']);
$title               = trim($_POST['title']);

$destination_id      = (int)$_POST['destination_id'];
$property_type_id    = (int)$_POST['property_type_id'];

$description         = trim($_POST['description']);
$address             = trim($_POST['address']);

$google_map_link     = trim($_POST['google_map_link']);

$latitude            = ($_POST['latitude'] != "") ? $_POST['latitude'] : NULL;
$longitude           = ($_POST['longitude'] != "") ? $_POST['longitude'] : NULL;

$price               = (float)$_POST['price'];

$discount_input      = trim($_POST['discount_percent'] ?? "");

$discount_percent    = ($discount_input === "") ? 0 : (float)$discount_input;

$bedrooms            = (int)$_POST['bedrooms'];
$bathrooms           = (int)$_POST['bathrooms'];
$max_guests          = (int)$_POST['max_guests'];

$check_in_time       = $_POST['check_in_time'];
$check_out_time      = $_POST['check_out_time'];

$featured            = (int)$_POST['featured'];

$status              = $_POST['status'];

$amenities           = $_POST['amenities'] ?? [];

$delete_images       = $_POST['delete_images'] ?? [];


/*====================================================
VALIDATION
====================================================*/

$errors = [];

if ($id <= 0)
    $errors[] = "Invalid Property.";

if ($property_code == "")
    $errors[] = "Property Code is required.";

if ($title == "")
    $errors[] = "Property Name is required.";

if ($destination_id <= 0)
    $errors[] = "Select Destination.";

if ($property_type_id <= 0)
    $errors[] = "Select Property Type.";

if ($price <= 0)
    $errors[] = "Price must be greater than zero.";

if ($discount_percent < 0 || $discount_percent > 100)
    $errors[] = "Discount must be between 0 and 100.";

if (count($errors) > 0) {

    $_SESSION['errors'] = $errors;

    header("Location: edit.php?id=" . $id);

    exit();

}


/*====================================================
FETCH EXISTING PROPERTY
====================================================*/

$sql = "SELECT * FROM properties WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$existingProperty = mysqli_fetch_assoc($result);

if (!$existingProperty) {

    $_SESSION['errors'][] = "Property not found.";

    header("Location: list.php");

    exit();

}


/*====================================================
CHECK PROPERTY CODE (EXCLUDING SELF)
====================================================*/

$sql = "SELECT id
        FROM properties
        WHERE property_code=?
        AND id!=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $property_code,
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    $_SESSION['errors'][] = "Property Code already exists.";

    header("Location: edit.php?id=" . $id);

    exit();

}


/*====================================================
DETERMINE EXISTING UPLOAD DIRECTORY
====================================================*/

$allowed = [
    "jpg",
    "jpeg",
    "png",
    "webp"
];

$sql = "SELECT image_path
        FROM property_images
        WHERE property_id=?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$imgResult = mysqli_stmt_get_result($stmt);

$existingImageRow = mysqli_fetch_assoc($imgResult);

if ($existingImageRow) {

    /* Reuse existing folder so old files stay valid */

    $relative_dir = dirname($existingImageRow['image_path']);

    $upload_dir = dirname(__DIR__, 2) .
                  "/assets/uploads/" .
                  $relative_dir . "/";

    $path_prefix = $relative_dir . "/";

} else {

    /* Fallback: no images yet, build folder same way save.php does */

    $sql = "SELECT type_name FROM property_types WHERE id=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $property_type_id);

    mysqli_stmt_execute($stmt);

    $typeResult = mysqli_stmt_get_result($stmt);

    $type = mysqli_fetch_assoc($typeResult);

    $type_name = $type['type_name'];

    $type_folder = preg_replace('/[^A-Za-z0-9_-]/', '_', $type_name);

    $safe_title = strtolower(trim($title));

    $safe_title = preg_replace('/[^a-z0-9]+/', '-', $safe_title);

    $safe_title = trim($safe_title, '-');

    $folder_name = $id . "_" . $property_code . "_" . $safe_title;

    $relative_dir = "properties/" . $type_folder . "/" . $folder_name;

    $upload_dir = dirname(__DIR__, 2) .
                  "/assets/uploads/" .
                  $relative_dir . "/";

    $path_prefix = $relative_dir . "/";

    if (!is_dir($upload_dir)) {

        mkdir($upload_dir, 0777, true);

    }

}


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

try {

    /*====================================================
    UPDATE PROPERTY
    ====================================================*/

    $sql = "

    UPDATE properties SET

    property_code=?,

    destination_id=?,

    property_type_id=?,

    title=?,

    description=?,

    address=?,

    google_map_link=?,

    latitude=?,

    longitude=?,

    price=?,

    discount_percent=?,

    bedrooms=?,

    bathrooms=?,

    max_guests=?,

    check_in_time=?,

    check_out_time=?,

    featured=?,

    status=?

    WHERE id=?

    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(

        $stmt,

        "siissssddddiiiisisi",

        $property_code,
        $destination_id,
        $property_type_id,
        $title,
        $description,
        $address,
        $google_map_link,
        $latitude,
        $longitude,
        $price,
        $discount_percent,
        $bedrooms,
        $bathrooms,
        $max_guests,
        $check_in_time,
        $check_out_time,
        $featured,
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

        $extension = strtolower(
            pathinfo(
                $_FILES['cover_image']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($extension, $allowed)) {

            throw new Exception("Invalid Cover Image.");

        }

        /* Remove any existing cover.* files so old extension doesn't linger */

        foreach ($allowed as $ext) {

            $oldCover = $upload_dir . "cover." . $ext;

            if (is_file($oldCover)) {

                unlink($oldCover);

            }

        }

        $cover_name = "cover." . $extension;

        $cover_destination = $upload_dir . $cover_name;

        if (!move_uploaded_file(
                $_FILES['cover_image']['tmp_name'],
                $cover_destination
            )) {

            throw new Exception("Unable to upload Cover Image.");

        }

        $cover_path = $path_prefix . $cover_name;

        $sql = "SELECT id FROM property_images WHERE property_id=? AND is_cover=1 LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $id);

        mysqli_stmt_execute($stmt);

        $coverRowResult = mysqli_stmt_get_result($stmt);

        $coverRow = mysqli_fetch_assoc($coverRowResult);

        if ($coverRow) {

            $sql = "UPDATE property_images SET image_path=? WHERE id=?";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "si", $cover_path, $coverRow['id']);

            if (!mysqli_stmt_execute($stmt)) {

                throw new Exception(mysqli_error($conn));

            }

        } else {

            $sql = "INSERT INTO property_images(property_id, image_path, is_cover, display_order) VALUES(?,?,1,1)";

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

            $sql = "SELECT image_path
                    FROM property_images
                    WHERE id=?
                    AND property_id=?
                    AND is_cover=0";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "ii", $imageId, $id);

            mysqli_stmt_execute($stmt);

            $delResult = mysqli_stmt_get_result($stmt);

            $imgRow = mysqli_fetch_assoc($delResult);

            if ($imgRow) {

                $filePath = dirname(__DIR__, 2) . "/assets/uploads/" . $imgRow['image_path'];

                if (is_file($filePath)) {

                    unlink($filePath);

                }

                $sql = "DELETE FROM property_images WHERE id=?";

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

        $sql = "SELECT MAX(display_order) AS max_order
                FROM property_images
                WHERE property_id=?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $id);

        mysqli_stmt_execute($stmt);

        $orderResult = mysqli_stmt_get_result($stmt);

        $orderRow = mysqli_fetch_assoc($orderResult);

        $order = ((int)$orderRow['max_order']) + 1;

        if ($order < 2) {
            $order = 2;
        }

        foreach ($_FILES['gallery_images']['name'] as $key => $name) {

            if ($name == "")
                continue;

            $extension = strtolower(
                pathinfo(
                    $name,
                    PATHINFO_EXTENSION
                )
            );

            if (!in_array($extension, $allowed)) {

                continue;

            }

            $gallery_name = "gallery_" . $order . "." . $extension;

            $destination = $upload_dir . $gallery_name;

            if (
                move_uploaded_file(

                    $_FILES['gallery_images']['tmp_name'][$key],

                    $destination

                )
            ) {

                $gallery_path = $path_prefix . $gallery_name;

                $sql = "INSERT INTO property_images(property_id, image_path, is_cover, display_order)
                        VALUES(?,?,0,?)";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param(

                    $stmt,

                    "isi",

                    $id,

                    $gallery_path,

                    $order

                );

                mysqli_stmt_execute($stmt);

                $order++;

            }

        }

    }


    /*====================================================
    SYNC AMENITIES
    ====================================================*/

    $sql = "DELETE FROM property_amenities WHERE property_id=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "i", $id);

    mysqli_stmt_execute($stmt);

    if (!empty($amenities)) {

        $sql = "INSERT INTO property_amenities(property_id, amenity_id) VALUES(?,?)";

        $stmt = mysqli_prepare($conn, $sql);

        foreach ($amenities as $amenity_id) {

            $amenity_id = (int)$amenity_id;

            mysqli_stmt_bind_param(

                $stmt,

                "ii",

                $id,

                $amenity_id

            );

            if (!mysqli_stmt_execute($stmt)) {

                throw new Exception(mysqli_error($conn));

            }

        }

    }


    /*====================================================
    SYNC PROPERTY MANAGER (ADMINS ONLY - is_manager = 0)
    ====================================================*/

    $isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

    if (!$isRestrictedManager) {

        $manager_email = trim($_POST['manager_email'] ?? "");

        if ($manager_email != "" && !filter_var($manager_email, FILTER_VALIDATE_EMAIL)) {

            throw new Exception("Manager Email is not a valid email address.");

        }

        if ($manager_email != "") {

            /* Who currently manages THIS property, if anyone */

            $sql = "SELECT a.id, a.email
                    FROM property_managers pm
                    INNER JOIN admins a ON pm.admin_id = a.id
                    WHERE pm.property_id = ?
                    LIMIT 1";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param($stmt, "i", $id);

            mysqli_stmt_execute($stmt);

            $managerResult = mysqli_stmt_get_result($stmt);

            $currentManager = mysqli_fetch_assoc($managerResult);

            /* Only touch anything if the email actually changed */

            if (!$currentManager || $currentManager['email'] !== $manager_email) {

                /* Does an account with this email already exist? */

                $sql = "SELECT id, is_manager FROM admins WHERE email=?";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "s", $manager_email);

                mysqli_stmt_execute($stmt);

                $existingResult = mysqli_stmt_get_result($stmt);

                $existingAccount = mysqli_fetch_assoc($existingResult);

                if ($existingAccount && (int)$existingAccount['is_manager'] === 0) {

                    throw new Exception("That email belongs to an administrator account and can't be used as a Manager Email.");

                }

                if ($existingAccount) {

                    /* Reuse the existing manager account - same login, same password */

                    $target_manager_id = (int)$existingAccount['id'];

                } else {

                    /* Brand new manager - create the account */

                    $manager_name = $title . " Manager";

                    $plain_password = (string) random_int(10000000, 99999999);

                    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

                    $encrypted_password = encryptManagerPassword($plain_password);

                    $sql = "INSERT INTO admins(name, email, password, encrypted_password, is_manager)
                            VALUES(?,?,?,?,1)";

                    $stmt = mysqli_prepare($conn, $sql);

                    mysqli_stmt_bind_param(

                        $stmt,

                        "ssss",

                        $manager_name,

                        $manager_email,

                        $hashed_password,

                        $encrypted_password

                    );

                    if (!mysqli_stmt_execute($stmt)) {

                        throw new Exception(mysqli_error($conn));

                    }

                    $target_manager_id = mysqli_insert_id($conn);

                }

                /* Remove this property's old manager link (if any), then
                   attach the new one. This only unlinks them from THIS
                   property - if they manage other properties too, those
                   links are untouched. */

                if ($currentManager) {

                    $sql = "DELETE FROM property_managers WHERE property_id=? AND admin_id=?";

                    $stmt = mysqli_prepare($conn, $sql);

                    mysqli_stmt_bind_param($stmt, "ii", $id, $currentManager['id']);

                    mysqli_stmt_execute($stmt);

                }

                $sql = "INSERT IGNORE INTO property_managers(property_id, admin_id) VALUES(?,?)";

                $stmt = mysqli_prepare($conn, $sql);

                mysqli_stmt_bind_param($stmt, "ii", $id, $target_manager_id);

                if (!mysqli_stmt_execute($stmt)) {

                    throw new Exception(mysqli_error($conn));

                }

            }

        }

        /* If manager_email was left blank, we intentionally leave any
           existing manager link untouched - blank does not remove it. */

    }


    /*====================================================
    COMMIT TRANSACTION
    ====================================================*/

    mysqli_commit($conn);

    $_SESSION['success'] = "Property Updated Successfully.";

    header("Location: list.php");

    exit();

}


/*====================================================
ROLLBACK
====================================================*/

catch (Exception $e) {

    mysqli_rollback($conn);

    $_SESSION['errors'][] = $e->getMessage();

    header("Location: edit.php?id=" . $id);

    exit();

}

?>