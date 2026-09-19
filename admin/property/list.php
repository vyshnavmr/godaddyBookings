<?php

session_start();

if (!isset($_SESSION['admin_id'])) {

    header("Location: ../index.php");
    exit();
}

    require_once "../../config/db.php";
    $currentPage = "properties";
    include "../includes/header.php";

/*=====================================
MANAGER SCOPE
=====================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

/*=====================================
SEARCH & FILTERS
=====================================*/

$search = $_GET['search'] ?? "";

$destination = $_GET['destination'] ?? "";

$type = $_GET['type'] ?? "";

$perPage = 30;

$page = max(1, (int)($_GET['page'] ?? 1));


/*=====================================
LOAD FILTER DATA
=====================================*/

$destinations = mysqli_query(
    $conn,
    "SELECT * FROM destinations ORDER BY destination_name"
);

$types = mysqli_query(
    $conn,
    "SELECT * FROM property_types ORDER BY type_name"
);


/*=====================================
SHARED WHERE CLAUSE
=====================================*/

$whereClause = " WHERE 1=1 ";

if ($isRestrictedManager) {

    if (!empty($managerPropertyIds)) {

        $idList = implode(",", array_map("intval", $managerPropertyIds));

        $whereClause .= " AND p.id IN ($idList)";

    } else {

        $whereClause .= " AND p.id = 0";

    }

}

if ($search != "") {

    $searchEscaped = mysqli_real_escape_string($conn, $search);

    $whereClause .= " AND (

        p.title LIKE '%$searchEscaped%'

        OR

        p.property_code LIKE '%$searchEscaped%'

    )";
}

if ($destination != "") {

    $destination = (int)$destination;

    $whereClause .= " AND p.destination_id=$destination";
}

if ($type != "") {

    $type = (int)$type;

    $whereClause .= " AND p.property_type_id=$type";
}


/*=====================================
COUNT + PAGINATION MATH
=====================================*/

$countSql = "

SELECT COUNT(*) AS total

FROM properties p

INNER JOIN destinations d ON p.destination_id=d.id

INNER JOIN property_types t ON p.property_type_id=t.id

" . $whereClause;

$countResult = mysqli_query($conn, $countSql);

$totalProperties = (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalProperties / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

function buildPropertyPageUrl($pageNum) {

    $params = $_GET;

    $params['page'] = $pageNum;

    return "list.php?" . http_build_query($params);

}


/*=====================================
PROPERTY QUERY - this page's rows only
=====================================*/

$sql = "

SELECT

p.*,

d.destination_name,

t.type_name,

(

SELECT image_path

FROM property_images

WHERE property_id=p.id

AND is_cover=1

LIMIT 1

) AS cover_image

FROM properties p

INNER JOIN destinations d

ON p.destination_id=d.id

INNER JOIN property_types t

ON p.property_type_id=t.id

" . $whereClause . "

ORDER BY p.created_at DESC

LIMIT $perPage OFFSET $offset

";

$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>

        Property List

    </title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link
        rel="stylesheet"
        href="../assets/css/property-list.css">

</head>

<body>

    <div class="container">

        <div class="page-header">

        <h1>Properties</h1>

        <?php if (!$isRestrictedManager) { ?>

            <a href="add.php" class="btn-add">

                <i class="fa-solid fa-plus"></i>

                Add Property

            </a>

        <?php } ?>

    </div>


        <form
            method="GET"
            class="filters">

            <input

                type="text"

                name="search"

                placeholder="Search Property..."

                value="<?= htmlspecialchars($search); ?>">

            <select
                name="destination">

                <option value="">

                    All Destinations

                </option>

                <?php while ($row = mysqli_fetch_assoc($destinations)) { ?>

                    <option

                        value="<?= $row['id']; ?>"

                        <?= ($destination == $row['id']) ? 'selected' : ''; ?>>

                        <?= $row['destination_name']; ?>

                    </option>

                <?php } ?>

            </select>


            <select
                name="type">

                <option value="">

                    All Types

                </option>

                <?php while ($row = mysqli_fetch_assoc($types)) { ?>

                    <option

                        value="<?= $row['id']; ?>"

                        <?= ($type == $row['id']) ? 'selected' : ''; ?>>

                        <?= $row['type_name']; ?>

                    </option>

                <?php } ?>

            </select>

            <button
                type="submit">

                <i class="fa-solid fa-magnifying-glass"></i>

                Search

            </button>

        </form>

        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Image</th>

                        <th>Property</th>

                        <th>Destination</th>

                        <th>Type</th>

                        <th>Price</th>

                        <th>Discount</th>

                        <th>Final Price</th>

                        <th>Status</th>

                        <th>Featured</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php

                    if (mysqli_num_rows($result) > 0) {

                        while ($property = mysqli_fetch_assoc($result)) {

                    ?>

                            <tr>

                                <td>

                                    <?php

                                    $image = "../../assets/uploads/no-image.png";

                                    if (!empty($property['cover_image'])) {

                                        $image = "../../assets/uploads/" . $property['cover_image'];
                                    }

                                    ?>

                                    <img

                                        src="<?= $image; ?>"

                                        class="property-image"

                                        alt="Property">

                                </td>

                                <td>

                                    <div class="property-info">

                                        <h4>

                                            <?= htmlspecialchars($property['title']); ?>

                                        </h4>

                                        <small>

                                            <?= htmlspecialchars($property['property_code']); ?>

                                        </small>

                                    </div>

                                </td>

                                <td>

                                    <?= htmlspecialchars($property['destination_name']); ?>

                                </td>

                                <td>

                                    <?= htmlspecialchars($property['type_name']); ?>

                                </td>

                                <td>

                                    ₹<?= number_format($property['price'], 2); ?>

                                </td>

                                <td>

                                    <?php if ((float)($property['discount_percent'] ?? 0) > 0) { ?>

                                        <span class="badge featured"><?= rtrim(rtrim(number_format($property['discount_percent'], 2), '0'), '.'); ?>%</span>

                                    <?php } else { ?>

                                        <span class="badge normal">—</span>

                                    <?php } ?>

                                </td>

                                <td>

                                    ₹<?= number_format($property['discounted_price'] ?? $property['price'], 2); ?>

                                </td>

                                <td>

                                    <?php

                                    $statusClass = "status-active";

                                    if ($property['status'] == "Inactive") {

                                        $statusClass = "status-inactive";
                                    }

                                    if ($property['status'] == "Booked") {

                                        $statusClass = "status-booked";
                                    }

                                    ?>

                                    <span class="badge <?= $statusClass; ?>">

                                        <?= $property['status']; ?>

                                    </span>

                                </td>

                                <td>

                                    <?php if ($property['featured']) { ?>

                                        <span class="badge featured">

                                            Featured

                                        </span>

                                    <?php } else { ?>

                                        <span class="badge normal">

                                            No

                                        </span>

                                    <?php } ?>

                                </td>

                                <td>

                                    <div class="actions">

                                        
                                        <a
                                            href="edit.php?id=<?= $property['id']; ?>"
                                            
                                            class="btn-edit">
                                            
                                            <i class="fa-solid fa-pen"></i>
                                            
                                        </a>
                                        
                                        <?php if (!$isRestrictedManager) { ?>
                                        
                                        
                                        
                                            <a
                                                href="delete.php?id=<?= $property['id']; ?>"

                                                class="btn-delete"

                                                onclick="return confirm('Delete this property?')">

                                                <i class="fa-solid fa-trash"></i>

                                            </a>

                                        <?php } ?>

                                    </div>

                                </td>

                            </tr>

                        <?php

                        }
                    } else {

                        ?>

                        <tr>

                            <td colspan="10">

                                <div class="empty-state">

                                    <i class="fa-solid fa-house-circle-xmark"></i>

                                    <?php if ($isRestrictedManager) { ?>

                                        <h3>

                                            No Property Assigned

                                        </h3>

                                        <p>

                                            You don't have a property assigned to your account yet. Contact an administrator.

                                        </p>

                                    <?php } else { ?>

                                        <h3>

                                            No Properties Found

                                        </h3>

                                        <p>

                                            Click

                                            <strong>

                                                Add Property

                                            </strong>

                                            to create your first listing.

                                        </p>

                                        

                                            href="add.php"

                                            class="btn-add">

                                            <i class="fa-solid fa-plus"></i>

                                            Add Property

                                        </a>

                                    <?php } ?>

                                </div>

                            </td>

                        </tr>

                    <?php

                    }

                    ?>

                </tbody>

            </table>

        </div>

        <?php if ($totalPages > 1) { ?>

            <div class="admin-pagination">

                <?php if ($page > 1) { ?>

                    <a href="<?= buildPropertyPageUrl($page - 1); ?>" class="admin-pagination-btn">

                        <i class="fa-solid fa-chevron-left"></i> Previous

                    </a>

                <?php } else { ?>

                    <span class="admin-pagination-btn admin-pagination-disabled">

                        <i class="fa-solid fa-chevron-left"></i> Previous

                    </span>

                <?php } ?>

                <div class="admin-pagination-numbers">

                    <?php

                    $windowStart = max(1, $page - 2);

                    $windowEnd = min($totalPages, $page + 2);

                    if ($windowStart > 1) { ?>

                        <a href="<?= buildPropertyPageUrl(1); ?>" class="admin-pagination-number">1</a>

                        <?php if ($windowStart > 2) { ?>
                            <span class="admin-pagination-ellipsis">…</span>
                        <?php } ?>

                    <?php } ?>

                    <?php for ($p = $windowStart; $p <= $windowEnd; $p++) { ?>

                        <a href="<?= buildPropertyPageUrl($p); ?>" class="admin-pagination-number <?= ($p == $page) ? 'active' : ''; ?>"><?= $p; ?></a>

                    <?php } ?>

                    <?php if ($windowEnd < $totalPages) { ?>

                        <?php if ($windowEnd < $totalPages - 1) { ?>
                            <span class="admin-pagination-ellipsis">…</span>
                        <?php } ?>

                        <a href="<?= buildPropertyPageUrl($totalPages); ?>" class="admin-pagination-number"><?= $totalPages; ?></a>

                    <?php } ?>

                </div>

                <?php if ($page < $totalPages) { ?>

                    <a href="<?= buildPropertyPageUrl($page + 1); ?>" class="admin-pagination-btn">

                        Next <i class="fa-solid fa-chevron-right"></i>

                    </a>

                <?php } else { ?>

                    <span class="admin-pagination-btn admin-pagination-disabled">

                        Next <i class="fa-solid fa-chevron-right"></i>

                    </span>

                <?php } ?>

            </div>

            <p class="admin-pagination-info">Page <?= $page; ?> of <?= $totalPages; ?> (<?= $totalProperties; ?> total properties)</p>

        <?php } ?>

        <?php if (isset($_SESSION['success'])) { ?>

            <div class="alert success">

                <i class="fa-solid fa-circle-check"></i>

                <?= $_SESSION['success']; ?>

            </div>

            <?php unset($_SESSION['success']); ?>

        <?php } ?>


        <?php if (isset($_SESSION['errors'])) { ?>

            <div class="alert error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <ul>

                    <?php foreach ($_SESSION['errors'] as $error) { ?>

                        <li><?= $error; ?></li>

                    <?php } ?>

                </ul>

            </div>

            <?php unset($_SESSION['errors']); ?>

        <?php } ?>


    </div>

    <script>
        setTimeout(function() {

            let alerts = document.querySelectorAll(".alert");

            alerts.forEach(function(alert) {

                alert.style.opacity = "0";

                setTimeout(function() {

                    alert.remove();

                }, 500);

            });

        }, 3000);
    </script>

</body>

</html>