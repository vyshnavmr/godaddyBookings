<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: ../index.php");
    exit();

}

require_once "../../config/db.php";


/* ==========================================================
   CHECK PROPERTY ID
========================================================== */

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

    header("Location: list.php");
    exit();

}

$property_id = (int) $_GET['id'];


/* ==========================================================
   CHECK IF PROPERTY EXISTS
========================================================== */

$propertyQuery = mysqli_prepare(
    $conn,
    "SELECT id, title
     FROM properties
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $propertyQuery,
    "i",
    $property_id
);

mysqli_stmt_execute($propertyQuery);

$propertyResult = mysqli_stmt_get_result($propertyQuery);

$property = mysqli_fetch_assoc($propertyResult);

mysqli_stmt_close($propertyQuery);


if (!$property) {

    header("Location: list.php");
    exit();

}


/* ==========================================================
   CHECK FOR BOOKINGS
========================================================== */

$bookingQuery = mysqli_prepare(
    $conn,
    "SELECT COUNT(*) AS total
     FROM bookings
     WHERE property_id = ?"
);

mysqli_stmt_bind_param(
    $bookingQuery,
    "i",
    $property_id
);

mysqli_stmt_execute($bookingQuery);

$bookingResult = mysqli_stmt_get_result($bookingQuery);

$bookingCount = mysqli_fetch_assoc($bookingResult)['total'];

mysqli_stmt_close($bookingQuery);


if ($bookingCount > 0) {

    header("Location: list.php?error=booking_exists");
    exit();

}


/* ==========================================================
   GET PROPERTY IMAGES
========================================================== */

$imageQuery = mysqli_prepare(
    $conn,
    "SELECT image_path
     FROM property_images
     WHERE property_id = ?"
);

mysqli_stmt_bind_param(
    $imageQuery,
    "i",
    $property_id
);

mysqli_stmt_execute($imageQuery);

$imageResult = mysqli_stmt_get_result($imageQuery);

$imagePaths = [];

while ($image = mysqli_fetch_assoc($imageResult)) {

    $imagePaths[] = $image['image_path'];

}

mysqli_stmt_close($imageQuery);


/* ==========================================================
   START TRANSACTION
========================================================== */

mysqli_begin_transaction($conn);

try {


    /* ======================================================
       DELETE PROPERTY AMENITIES
    ======================================================= */

    $deleteAmenities = mysqli_prepare(
        $conn,
        "DELETE FROM property_amenities
         WHERE property_id = ?"
    );

    mysqli_stmt_bind_param(
        $deleteAmenities,
        "i",
        $property_id
    );

    if (!mysqli_stmt_execute($deleteAmenities)) {

        throw new Exception(
            mysqli_stmt_error($deleteAmenities)
        );

    }

    mysqli_stmt_close($deleteAmenities);


    /* ======================================================
       DELETE PROPERTY IMAGES FROM DATABASE
    ======================================================= */

    $deleteImages = mysqli_prepare(
        $conn,
        "DELETE FROM property_images
         WHERE property_id = ?"
    );

    mysqli_stmt_bind_param(
        $deleteImages,
        "i",
        $property_id
    );

    if (!mysqli_stmt_execute($deleteImages)) {

        throw new Exception(
            mysqli_stmt_error($deleteImages)
        );

    }

    mysqli_stmt_close($deleteImages);


    /* ======================================================
       DELETE PROPERTY
    ======================================================= */

    $deleteProperty = mysqli_prepare(
        $conn,
        "DELETE FROM properties
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $deleteProperty,
        "i",
        $property_id
    );

    if (!mysqli_stmt_execute($deleteProperty)) {

        throw new Exception(
            mysqli_stmt_error($deleteProperty)
        );

    }

    mysqli_stmt_close($deleteProperty);


    /* ======================================================
       COMMIT DATABASE CHANGES
    ======================================================= */

    mysqli_commit($conn);


    /* ======================================================
       DELETE PHYSICAL IMAGE FILES
       
       Database deletion has already succeeded.
       Now remove the actual files.
    ======================================================= */

    foreach ($imagePaths as $imagePath) {

        /*
         * image_path examples:
         *
         * properties/Apartment/1/cover.jpg
         * properties/Apartment/1/gallery_2.jpg
         *
         * Actual location:
         *
         * WEBSITE/uploads/properties/Apartment/1/cover.jpg
         */

        $filePath = __DIR__ . "/../../uploads/" . $imagePath;


        /*
         * Security check:
         * Prevent paths such as ../../something
         */

        $realUploadsPath = realpath(
            __DIR__ . "/../../uploads"
        );

        $realFilePath = realpath($filePath);


        if (
            $realFilePath !== false &&
            $realUploadsPath !== false &&
            strpos(
                $realFilePath,
                $realUploadsPath
            ) === 0 &&
            is_file($realFilePath)
        ) {

            unlink($realFilePath);

        }

    }


    /* ======================================================
       REDIRECT SUCCESS
    ======================================================= */

    header("Location: list.php?deleted=1");
    exit();


} catch (Exception $e) {


    /* ======================================================
       ROLLBACK DATABASE CHANGES
    ======================================================= */

    mysqli_rollback($conn);


    /*
     * During development, you can use:
     *
     * $_SESSION['delete_error'] = $e->getMessage();
     *
     * For now, redirect with a generic error.
     */

    header("Location: list.php?error=delete_failed");
    exit();

}