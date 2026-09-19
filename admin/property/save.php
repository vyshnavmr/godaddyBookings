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

    header("Location: add.php");

    exit();

}


/*====================================================
READ FORM DATA
====================================================*/

$property_code      = trim($_POST['property_code']);
$title              = trim($_POST['title']);

$destination_id_raw = $_POST['destination_id'] ?? "";
$new_destination_name = trim($_POST['new_destination_name'] ?? "");

$property_type_id_raw = $_POST['property_type_id'] ?? "";
$new_property_type_name = trim($_POST['new_property_type_name'] ?? "");

$description        = trim($_POST['description']);
$address            = trim($_POST['address']);

$google_map_link    = trim($_POST['google_map_link']);

$latitude           = ($_POST['latitude'] != "") ? $_POST['latitude'] : NULL;
$longitude          = ($_POST['longitude'] != "") ? $_POST['longitude'] : NULL;

$price              = (float)$_POST['price'];

$discount_input     = trim($_POST['discount_percent'] ?? "");

$discount_percent   = ($discount_input === "") ? 0 : (float)$discount_input;

$bedrooms           = (int)$_POST['bedrooms'];
$bathrooms          = (int)$_POST['bathrooms'];
$max_guests         = (int)$_POST['max_guests'];

$check_in_time      = $_POST['check_in_time'];
$check_out_time     = $_POST['check_out_time'];

$featured           = (int)$_POST['featured'];

$status             = $_POST['status'];

$amenities          = $_POST['amenities'] ?? [];

$manager_email      = trim($_POST['manager_email'] ?? "");

$manager_phone      = trim($_POST['manager_phone'] ?? "");


/*====================================================
VALIDATION
====================================================*/

$errors = [];

if($property_code=="")
    $errors[]="Property Code is required.";

if($title=="")
    $errors[]="Property Name is required.";

if ($destination_id_raw === "new") {

    if ($new_destination_name === "") {
        $errors[] = "Please enter a name for the new destination.";
    }

} elseif ((int)$destination_id_raw <= 0) {

    $errors[] = "Select Destination.";

}

if ($property_type_id_raw === "new") {

    if ($new_property_type_name === "") {
        $errors[] = "Please enter a name for the new property type.";
    }

} elseif ((int)$property_type_id_raw <= 0) {

    $errors[] = "Select Property Type.";

}

if($price<=0)
    $errors[]="Price must be greater than zero.";

if($discount_percent < 0 || $discount_percent > 100)
    $errors[]="Discount must be between 0 and 100.";

if(empty($_FILES['cover_image']['name']))
    $errors[]="Please select a Cover Image.";

if ($manager_email != "" && !filter_var($manager_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Manager Email is not a valid email address.";
}

if ($manager_phone != "" && !preg_match('/^[0-9]{10}$/', $manager_phone)) {
    $errors[] = "Manager Phone must be a valid 10-digit number.";
}
}

if(count($errors)>0){

    $_SESSION['errors']=$errors;

    header("Location:add.php");

    exit();

}


/*====================================================
RESOLVE DESTINATION - either an existing id from the
dropdown, or a brand-new destination typed in by the admin
====================================================*/

if ($destination_id_raw === "new") {

    /* Normalize before comparing/storing - trims whitespace,
       strips trailing punctuation like a stray comma or period,
       and collapses repeated spaces. This is what prevents
       "Kollam", "Kollam,", and "kollam " from becoming three
       separate rows. */

    $new_destination_name = trim($new_destination_name);

    $new_destination_name = rtrim($new_destination_name, " ,.-");

    $new_destination_name = preg_replace('/\s+/', ' ', $new_destination_name);

    if ($new_destination_name === "") {

        $_SESSION['errors'][] = "Please enter a valid destination name.";

        header("Location:add.php");

        exit();

    }

    $sql = "SELECT id FROM destinations WHERE LOWER(destination_name) = LOWER(?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $new_destination_name);

    mysqli_stmt_execute($stmt);

    $existingDestination = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existingDestination) {

        $destination_id = (int)$existingDestination['id'];

    } else {

        /* Same space-insensitive near-match check as property types */

        $strippedNewName = str_replace(' ', '', strtolower($new_destination_name));

        $sql = "SELECT id, destination_name FROM destinations";

        $allDestResult = mysqli_query($conn, $sql);

        while ($existingRow = mysqli_fetch_assoc($allDestResult)) {

            $strippedExisting = str_replace(' ', '', strtolower($existingRow['destination_name']));

            if ($strippedExisting === $strippedNewName) {

                $_SESSION['errors'][] = "A very similar destination already exists: \"" . htmlspecialchars($existingRow['destination_name']) . "\". Please select it from the dropdown instead of creating a new one.";

                header("Location:add.php");

                exit();

            }

        }

        $sql = "INSERT INTO destinations(destination_name, is_active) VALUES(?, 1)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $new_destination_name);

        if (!mysqli_stmt_execute($stmt)) {

            $_SESSION['errors'][] = "Failed to create the new destination.";

            header("Location:add.php");

            exit();

        }

        $destination_id = mysqli_insert_id($conn);

    }

} else {

    $destination_id = (int)$destination_id_raw;

}

/*====================================================
RESOLVE PROPERTY TYPE - either an existing id from the
dropdown, or a brand-new type typed in by the admin
====================================================*/

if ($property_type_id_raw === "new") {

    $new_property_type_name = trim($new_property_type_name);

    $new_property_type_name = rtrim($new_property_type_name, " ,.-");

    $new_property_type_name = preg_replace('/\s+/', ' ', $new_property_type_name);

    if ($new_property_type_name === "") {

        $_SESSION['errors'][] = "Please enter a valid property type name.";

        header("Location:add.php");

        exit();

    }

    $sql = "SELECT id FROM property_types WHERE LOWER(type_name) = LOWER(?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $new_property_type_name);

    mysqli_stmt_execute($stmt);

    $existingType = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($existingType) {

        $property_type_id = (int)$existingType['id'];

    } else {

        /* No exact match - but check for a space-insensitive near-match
           too (e.g. "Beach House" vs "Beachhouse"), which the exact
           check above can't catch since they're genuinely different
           strings, not just messy whitespace around the same one.
           Block and point at the existing name rather than silently
           guessing they're the same thing. */

        $strippedNewName = str_replace(' ', '', strtolower($new_property_type_name));

        $sql = "SELECT id, type_name FROM property_types";

        $allTypesResult = mysqli_query($conn, $sql);

        while ($existingRow = mysqli_fetch_assoc($allTypesResult)) {

            $strippedExisting = str_replace(' ', '', strtolower($existingRow['type_name']));

            if ($strippedExisting === $strippedNewName) {

                $_SESSION['errors'][] = "A very similar property type already exists: \"" . htmlspecialchars($existingRow['type_name']) . "\". Please select it from the dropdown instead of creating a new one.";

                header("Location:add.php");

                exit();

            }

        }

        $sql = "INSERT INTO property_types(type_name, is_active) VALUES(?, 1)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "s", $new_property_type_name);

        if (!mysqli_stmt_execute($stmt)) {

            $_SESSION['errors'][] = "Failed to create the new property type.";

            header("Location:add.php");

            exit();

        }

        $property_type_id = mysqli_insert_id($conn);

    }

} else {

    $property_type_id = (int)$property_type_id_raw;

}

/*====================================================
CHECK PROPERTY CODE
====================================================*/

$sql="SELECT id
      FROM properties
      WHERE property_code=?";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $property_code
);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)>0){

    $_SESSION['errors'][]="Property Code already exists.";

    header("Location:add.php");

    exit();

}


/*====================================================
CHECK MANAGER EMAIL (IF PROVIDED)
====================================================*/

/* If this email already belongs to a manager account, we'll reuse that
   account (same login, same password) and just link it to this new
   property too - one person CAN manage multiple properties.
   If it belongs to a non-manager (super admin) account, that's a real
   conflict and we still block it. */

$existing_manager_id = null;

if ($manager_email != "") {

    $sql = "SELECT id, is_manager FROM admins WHERE email=?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "s", $manager_email);

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $existingAccount = mysqli_fetch_assoc($result);

    if ($existingAccount) {

        if ((int)$existingAccount['is_manager'] === 1) {

            $existing_manager_id = (int)$existingAccount['id'];

        } else {

            $_SESSION['errors'][] = "That email belongs to an administrator account and can't be used as a Manager Email.";

            header("Location:add.php");

            exit();

        }

    }

}


/*====================================================
START TRANSACTION
====================================================*/

mysqli_begin_transaction($conn);

try{

/*====================================================
INSERT PROPERTY
====================================================*/

$sql="

INSERT INTO properties(

property_code,

destination_id,

property_type_id,

title,

description,

address,

google_map_link,

latitude,

longitude,

price,

discount_percent,

bedrooms,

bathrooms,

max_guests,

check_in_time,

check_out_time,

featured,

status

)

VALUES(

?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?

)

";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(

    $stmt,

    "siissssddddiiiisis",

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
    $status

);

if(!mysqli_stmt_execute($stmt)){

    throw new Exception(mysqli_error($conn));

}


/*====================================================
NEW PROPERTY ID
====================================================*/

$property_id=mysqli_insert_id($conn);


/*====================================================
GET PROPERTY TYPE NAME
====================================================*/

$sql="SELECT type_name
      FROM property_types
      WHERE id=?";

$stmt=mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $property_type_id
);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

$type=mysqli_fetch_assoc($result);

$type_name=$type['type_name'];


/*

PART 2

Create Folder

Upload Cover Image

Upload Gallery Images

*/

/*====================================================
CREATE PROPERTY FOLDER
====================================================*/

$type_folder = preg_replace('/[^A-Za-z0-9_-]/', '_', $type_name);

/* Create Safe Folder Name */

$safe_title = strtolower(trim($title));

$safe_title = preg_replace('/[^a-z0-9]+/', '-', $safe_title);

$safe_title = trim($safe_title, '-');

$folder_name = $property_id .
               "_" .
               $property_code .
               "_" .
               $safe_title;


/* Upload Folder */

$upload_dir = dirname(__DIR__,2) .
              "/assets/uploads/properties/" .
              $type_folder . "/" .
              $folder_name . "/";

if (!is_dir($upload_dir)) {

    if (!mkdir($upload_dir, 0777, true)) {

        throw new Exception("Unable to create property folder.");

    }

}


/*====================================================
UPLOAD COVER IMAGE
====================================================*/

$allowed = [

    "jpg",
    "jpeg",
    "png",
    "webp"

];

$cover_path = "";

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

    $cover_name = "cover." . $extension;

    $cover_destination = $upload_dir . $cover_name;

    if (!move_uploaded_file(
            $_FILES['cover_image']['tmp_name'],
            $cover_destination
        )) {

        throw new Exception("Unable to upload Cover Image.");

    }

    $cover_path =
        "properties/" .
        $type_folder . "/" .
        $folder_name . "/" .
        $cover_name;

}


/*====================================================
INSERT COVER IMAGE
====================================================*/

$sql = "

INSERT INTO property_images(

property_id,

image_path,

is_cover,

display_order

)

VALUES(

?,?,1,1

)

";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(

$stmt,

"is",

$property_id,

$cover_path

);

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

        $extension = strtolower(
            pathinfo(
                $name,
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($extension, $allowed)) {

            continue;

        }

        $gallery_name =
            "gallery_" .
            $order .
            "." .
            $extension;

        $destination =
            $upload_dir .
            $gallery_name;

        if (
            move_uploaded_file(

                $_FILES['gallery_images']['tmp_name'][$key],

                $destination

            )
        ) {

            $gallery_path =
                "properties/" .
                $type_folder . "/" .
                $folder_name . "/" .
                $gallery_name;

            $sql = "

            INSERT INTO property_images(

            property_id,

            image_path,

            is_cover,

            display_order

            )

            VALUES(

            ?,?,0,?

            )

            ";

            $stmt = mysqli_prepare($conn, $sql);

            mysqli_stmt_bind_param(

                $stmt,

                "isi",

                $property_id,

                $gallery_path,

                $order

            );

            mysqli_stmt_execute($stmt);

            $order++;

        }

    }

}


/*

PART 3

Insert Amenities

Create Manager Account (if email provided)

Commit Transaction

Rollback on Error

Redirect

*/

/*====================================================
SAVE AMENITIES
====================================================*/

if (!empty($amenities)) {

    $sql = "

    INSERT INTO property_amenities(

    property_id,

    amenity_id

    )

    VALUES(

    ?,?

    )

    ";

    $stmt = mysqli_prepare($conn, $sql);

    foreach ($amenities as $amenity_id) {

        $amenity_id = (int)$amenity_id;

        mysqli_stmt_bind_param(

            $stmt,

            "ii",

            $property_id,

            $amenity_id

        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(mysqli_error($conn));

        }

    }

}


/*====================================================
LINK MANAGER TO THIS PROPERTY (IF EMAIL PROVIDED)
====================================================*/

if ($manager_email != "") {

    if ($existing_manager_id) {

        /* Reuse the existing manager account - same login, same password.
           Just attach them to this additional property. */

        $manager_id = $existing_manager_id;

    } else {

        /* Brand new manager - create the account */

        $manager_name = $title . " Manager";

        $plain_password = (string) random_int(10000000, 99999999);

        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        $encrypted_password = encryptManagerPassword($plain_password);

    $sql = "

        INSERT INTO admins(

        name,

        email,

        phone,

        password,

        encrypted_password,

        is_manager

        )

        VALUES(

        ?,?,?,?,?,1

        )

        ";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(

            $stmt,

            "sssss",

            $manager_name,

            $manager_email,

            $manager_phone,

            $hashed_password,

            $encrypted_password

        );

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception(mysqli_error($conn));

        }

        $manager_id = mysqli_insert_id($conn);

    }

    $sql = "INSERT IGNORE INTO property_managers(property_id, admin_id) VALUES(?,?)";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $property_id, $manager_id);

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(mysqli_error($conn));

    }

}


/*====================================================
COMMIT TRANSACTION
====================================================*/

mysqli_commit($conn);

$_SESSION['success'] = "Property Added Successfully.";

header("Location: list.php");

exit();

}


/*====================================================
ROLLBACK
====================================================*/

catch(Exception $e){

    mysqli_rollback($conn);

    /* Delete Uploaded Folder */

    if(isset($upload_dir) && is_dir($upload_dir)){

        $files = array_diff(scandir($upload_dir), array('.', '..'));

        foreach($files as $file){

            unlink($upload_dir . $file);

        }

        rmdir($upload_dir);

    }

    $_SESSION['errors'][] = $e->getMessage();

    header("Location: add.php");

    exit();

}

?>