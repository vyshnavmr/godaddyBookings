<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['admin_id']) || (isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1)) {

    http_response_code(403);

    echo json_encode(["success" => false, "message" => "Not authorized."]);

    exit();

}

require_once "../../config/db.php";

$action = trim($_POST['action'] ?? "");


/*====================================================
RENAME
====================================================*/

if ($action === "rename") {

    $id = (int)($_POST['id'] ?? 0);

    $name = trim($_POST['name'] ?? "");

    $name = rtrim($name, " ,.-");

    $name = preg_replace('/\s+/', ' ', $name);

    if ($id <= 0 || $name === "") {

        echo json_encode(["success" => false, "message" => "Invalid name."]);

        exit();

    }

    /* Same near-duplicate protection as the property add/edit forms */

    $strippedNewName = str_replace(' ', '', strtolower($name));

    $checkResult = mysqli_query($conn, "SELECT id, destination_name FROM destinations WHERE id != $id");

    while ($row = mysqli_fetch_assoc($checkResult)) {

        if (str_replace(' ', '', strtolower($row['destination_name'])) === $strippedNewName) {

            echo json_encode(["success" => false, "message" => "A very similar destination already exists: \"" . $row['destination_name'] . "\". Use Merge instead."]);

            exit();

        }

    }

    $sql = "UPDATE destinations SET destination_name = ? WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "si", $name, $id);

    echo json_encode(["success" => mysqli_stmt_execute($stmt)]);

    exit();

}


/*====================================================
TOGGLE ACTIVE
====================================================*/

if ($action === "toggle_active") {

    $id = (int)($_POST['id'] ?? 0);

    $isActive = ((int)($_POST['is_active'] ?? 0) === 1) ? 1 : 0;

    $sql = "UPDATE destinations SET is_active = ? WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param($stmt, "ii", $isActive, $id);

    echo json_encode(["success" => mysqli_stmt_execute($stmt)]);

    exit();

}


/*====================================================
MERGE - move every property from source to target, then
delete the now-empty source. Wrapped in a transaction so
a failure partway through can't leave properties pointing
at a half-deleted destination.
====================================================*/

if ($action === "merge") {

    $sourceId = (int)($_POST['source_id'] ?? 0);

    $targetId = (int)($_POST['target_id'] ?? 0);

    if ($sourceId <= 0 || $targetId <= 0 || $sourceId === $targetId) {

        echo json_encode(["success" => false, "message" => "Invalid merge request."]);

        exit();

    }

    mysqli_begin_transaction($conn);

    try {

        $sql = "UPDATE properties SET destination_id = ? WHERE destination_id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "ii", $targetId, $sourceId);

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception("Failed to move properties.");

        }

        $sql = "DELETE FROM destinations WHERE id = ?";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param($stmt, "i", $sourceId);

        if (!mysqli_stmt_execute($stmt)) {

            throw new Exception("Failed to delete duplicate.");

        }

        mysqli_commit($conn);

        echo json_encode(["success" => true]);

    } catch (Exception $e) {

        mysqli_rollback($conn);

        echo json_encode(["success" => false, "message" => $e->getMessage()]);

    }

    exit();

}

echo json_encode(["success" => false, "message" => "Unknown action."]);

?>