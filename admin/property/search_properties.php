<?php

session_start();

header("Content-Type: application/json");

if (!isset($_SESSION['admin_id'])) {

    http_response_code(403);

    echo json_encode([]);

    exit();

}

require_once "../../config/db.php";


/*====================================================
READ QUERY
====================================================*/

$q = trim($_GET['q'] ?? "");

if ($q === "") {

    echo json_encode([]);

    exit();

}


/*====================================================
SEARCH PROPERTIES
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

/* Managers only ever get results from their own properties */

if ($isRestrictedManager && empty($managerPropertyIds)) {

    echo json_encode([]);

    exit();

}

$like = "%" . $q . "%";

$sql = "

SELECT

p.id,

p.title,

p.property_code,

d.destination_name

FROM properties p

INNER JOIN destinations d

ON p.destination_id = d.id

WHERE (p.title LIKE ?

OR p.property_code LIKE ?)

";

if ($isRestrictedManager) {

    $idList = implode(",", array_map("intval", $managerPropertyIds));

    $sql .= " AND p.id IN ($idList)";

}

$sql .= " ORDER BY p.title ASC LIMIT 10";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "ss", $like, $like);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$properties = [];

while ($row = mysqli_fetch_assoc($result)) {

    $properties[] = $row;

}

echo json_encode($properties);

?>