<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

/* Only the main admin can remove managers - a manager account
   should never reach this even if they guess the URL */

if (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1) {
    header("Location: ../dashboard.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
CHECK REQUEST
====================================================*/

if ($_SERVER["REQUEST_METHOD"] != "POST") {

    header("Location: manager_list.php");

    exit();

}

$admin_id = (int)($_POST['admin_id'] ?? 0);

if ($admin_id <= 0) {

    $_SESSION['manager_error'] = "Invalid manager selected.";

    header("Location: manager_list.php");

    exit();

}


/*====================================================
CONFIRM THIS IS ACTUALLY A MANAGER ACCOUNT - safety check
so this can never be used to accidentally strip property
links from a real administrator account.
====================================================*/

$sql = "SELECT id, name, is_manager FROM admins WHERE id=?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $admin_id);

mysqli_stmt_execute($stmt);

$account = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$account) {

    $_SESSION['manager_error'] = "Manager not found.";

    header("Location: manager_list.php");

    exit();

}

if ((int)$account['is_manager'] !== 1) {

    $_SESSION['manager_error'] = "That account is not a manager and cannot be removed here.";

    header("Location: manager_list.php");

    exit();

}


/*====================================================
UNLINK FROM SELECTED PROPERTIES ONLY - the login account
itself (admins row, password, etc.) is intentionally left
untouched, in case this person manages other properties
in the future or needs to be reassigned later.
====================================================*/

$property_ids = $_POST['property_ids'] ?? [];

if (empty($property_ids)) {

    $_SESSION['manager_error'] = "Please select at least one property to unlink.";

    header("Location: manager_list.php");

    exit();

}

$unlinkedCount = 0;

$sql = "DELETE FROM property_managers WHERE admin_id=? AND property_id=?";

$stmt = mysqli_prepare($conn, $sql);

foreach ($property_ids as $property_id) {

    $property_id = (int)$property_id;

    if ($property_id <= 0) {
        continue;
    }

    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $property_id);

    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {

        $unlinkedCount++;

    }

}

if ($unlinkedCount > 0) {

    $_SESSION['manager_success'] = htmlspecialchars($account['name']) . " was unlinked from " . $unlinkedCount . " propert" . ($unlinkedCount > 1 ? 'ies' : 'y') . ". Their login remains active.";

} else {

    $_SESSION['manager_error'] = "No matching property links were found to remove.";

}

header("Location: manager_list.php");

exit();

?>