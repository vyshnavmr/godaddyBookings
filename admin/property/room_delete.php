<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
READ ROOM ID
====================================================*/

$id = (int)($_GET['id'] ?? 0);

$redirect_property_id = (int)($_GET['property_id'] ?? 0);

if ($id <= 0) {

    header("Location: rooms.php");

    exit();

}


/*====================================================
FETCH ROOM + VERIFY IT EXISTS
====================================================*/

$sql = "SELECT * FROM property_rooms WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$room) {

    $_SESSION['errors'][] = "Room not found.";

    header("Location: rooms.php" . ($redirect_property_id > 0 ? "?property_id=" . $redirect_property_id : ""));

    exit();

}

$property_id = (int)$room['property_id'];


/*====================================================
MANAGER OWNERSHIP CHECK
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    if (!in_array($property_id, $managerPropertyIds)) {

        $_SESSION['errors'][] = "You don't have permission to delete this room.";

        header("Location: rooms.php");

        exit();

    }

}


/*====================================================
DELETE IMAGE FILES FROM DISK

The database rows in property_room_images will be removed
automatically via ON DELETE CASCADE when the room row is
deleted below - but that only removes DB records, not the
actual files on disk. Those have to be cleaned up here first,
while we still have their paths.
====================================================*/

$sql = "SELECT image_path FROM property_room_images WHERE room_id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$imagesResult = mysqli_stmt_get_result($stmt);

$roomFolderPath = null;

while ($img = mysqli_fetch_assoc($imagesResult)) {

    $filePath = dirname(__DIR__, 2) . "/assets/uploads/" . $img['image_path'];

    if (is_file($filePath)) {
        unlink($filePath);
    }

    if ($roomFolderPath === null) {
        $roomFolderPath = dirname($filePath);
    }

}

/* Remove the now-empty room folder itself, if we found one and
   nothing unexpected is left inside it */

if ($roomFolderPath && is_dir($roomFolderPath)) {

    $remaining = array_diff(scandir($roomFolderPath), ['.', '..']);

    if (empty($remaining)) {
        rmdir($roomFolderPath);
    }

}


/*====================================================
DELETE THE ROOM (cascades to property_room_images rows)
====================================================*/

$sql = "DELETE FROM property_rooms WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    $_SESSION['success'] = "Room Type Deleted Successfully.";

} else {

    $_SESSION['errors'][] = "Unable to delete this room: " . mysqli_error($conn);

}

header("Location: rooms.php?property_id=" . $property_id);

exit();

?>