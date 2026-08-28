<?php

session_start();
if (!isset($_SESSION['admin_id'])) {
    
    header("Location: index.php");
    exit();
    }
    
    require_once "../config/db.php";
    $currentPage = "dashboard";
    include "includes/header.php";

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

if ($isRestrictedManager) {

    /*====================================================
    MANAGER DASHBOARD - SCOPED TO THEIR OWN PROPERTIES ONLY
    A manager can be linked to more than one property.
    ====================================================*/

    $managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

    $properties = [];

    $totalBookings = 0;

    if (!empty($managerPropertyIds)) {

        $idList = implode(",", array_map("intval", $managerPropertyIds));

        $sql = "SELECT p.*, d.destination_name, t.type_name,
                (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) AS booking_count
                FROM properties p
                INNER JOIN destinations d ON p.destination_id = d.id
                INNER JOIN property_types t ON p.property_type_id = t.id
                WHERE p.id IN ($idList)
                ORDER BY p.title ASC";

        $result = mysqli_query($conn, $sql);

        while ($row = mysqli_fetch_assoc($result)) {

            $properties[] = $row;

            $totalBookings += (int)$row['booking_count'];

        }

    }

    ?>

    <style>
        .manager-summary-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .manager-summary-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .manager-summary-card h3 {
            margin: 0 0 6px 0;
            font-size: 13px;
            color: #888;
            font-weight: 600;
        }

        .manager-summary-card h2 {
            margin: 0;
            font-size: 26px;
            color: #333;
        }

        .manager-property-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
        }

        .manager-property-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 16px;
        }

        .manager-property-header h2 {
            margin: 0 0 8px 0;
            font-size: 20px;
        }

        .manager-property-meta .badge {
            display: inline-block;
            margin-right: 8px;
            padding: 5px 12px;
            border-radius: 20px;
            background: #f0f1f5;
            color: #444;
            font-size: 12px;
            font-weight: 600;
        }

        .manager-property-meta .status-active {
            background: #e7f8ee;
            color: #1e7e42;
        }

        .manager-property-meta .status-inactive {
            background: #fdeceb;
            color: #c0392b;
        }

        .manager-property-meta .status-booked {
            background: #fff4e0;
            color: #b8790a;
        }

        .manager-property-stats {
            display: flex;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .manager-property-stats div {
            font-size: 13px;
            color: #666;
        }

        .manager-property-stats strong {
            display: block;
            font-size: 17px;
            color: #333;
        }

        .manager-property-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-manager-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            background: #4a6cf7;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .btn-manager-action:hover {
            background: #3a5ce0;
        }

        .manager-empty-state {
            text-align: center;
            padding: 70px 20px;
            color: #888;
            background: #fff;
            border-radius: 14px;
        }

        .manager-empty-state i {
            font-size: 42px;
            margin-bottom: 14px;
            color: #ccc;
        }

        .manager-empty-state h3 {
            color: #333;
            margin-bottom: 6px;
        }
    </style>

    <div class="container">

        <h1>Dashboard</h1>

        <?php if (!empty($properties)) { ?>

            <div class="manager-summary-row">

                <div class="manager-summary-card">

                    <h3>Properties You Manage</h3>

                    <h2><?= count($properties); ?></h2>

                </div>

                <div class="manager-summary-card">

                    <h3>Total Bookings</h3>

                    <h2><?= $totalBookings; ?></h2>

                </div>

            </div>

            <?php foreach ($properties as $property) { ?>

                <div class="manager-property-card">

                    <div class="manager-property-header">

                        <div>

                            <h2><?= htmlspecialchars($property['title']); ?></h2>

                            <p class="manager-property-meta">

                                <span class="badge"><?= htmlspecialchars($property['property_code']); ?></span>

                                <span class="badge"><?= htmlspecialchars($property['destination_name']); ?></span>

                                <span class="badge"><?= htmlspecialchars($property['type_name']); ?></span>

                                <span class="badge status-<?= strtolower($property['status']) == 'available' ? 'active' : (strtolower($property['status']) == 'booked' ? 'booked' : 'inactive'); ?>">

                                    <?= htmlspecialchars($property['status']); ?>

                                </span>

                            </p>

                            <div class="manager-property-stats">

                                <div>
                                    Bookings
                                    <strong><?= (int)$property['booking_count']; ?></strong>
                                </div>

                                <div>
                                    Price / Night
                                    <strong>₹<?= number_format($property['price'], 0); ?></strong>
                                </div>

                                <div>
                                    Bedrooms
                                    <strong><?= (int)$property['bedrooms']; ?></strong>
                                </div>

                                <div>
                                    Max Guests
                                    <strong><?= (int)$property['max_guests']; ?></strong>
                                </div>

                            </div>

                        </div>

                        <div class="manager-property-actions">

                            <a href="property/edit.php?id=<?= $property['id']; ?>" class="btn-manager-action">

                                <i class="fa-solid fa-pen"></i> Edit Property

                            </a>

                            <a href="property/rooms.php?property_id=<?= $property['id']; ?>" class="btn-manager-action">

                                <i class="fa-solid fa-bed"></i> Manage Rooms

                            </a>

                        </div>

                    </div>

                </div>

            <?php } ?>

        <?php } else { ?>

            <div class="manager-empty-state">

                <i class="fa-solid fa-house-circle-xmark"></i>

                <h3>No Properties Assigned</h3>

                <p>You don't have any properties assigned to your account yet. Contact an administrator.</p>

            </div>

        <?php } ?>

    </div>

    <script src="assets/js/admin.js"></script>

    </body>

    </html>

    <?php

    exit();

}


/* Dashboard Counts */

$propertyCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM properties"
))['total'];

$destinationCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM destinations"
))['total'];

$typeCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM property_types"
))['total'];

$amenityCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM amenities"
))['total'];

$bookingCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM bookings"
))['total'];

$userCount = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COUNT(*) total FROM users"
))['total'];

// $messageCount=mysqli_fetch_assoc(mysqli_query($conn,
// "SELECT COUNT(*) total FROM contacts"))['total'];

?>



    <!-- =======================================
CONTENT
======================================= -->

    <div class="container">

        <h1>

            Dashboard

        </h1>

        <div class="cards">

            <div class="card">

                <i class="fa-solid fa-hotel"></i>

                <h3>

                    Properties

                </h3>

                <h2>

                    <?php echo $propertyCount; ?>

                </h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-location-dot"></i>

                <h3>

                    Destinations

                </h3>

                <h2>

                    <?php echo $destinationCount; ?>

                </h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-building"></i>

                <h3>

                    Property Types

                </h3>

                <h2>

                    <?php echo $typeCount; ?>

                </h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-wifi"></i>

                <h3>

                    Amenities

                </h3>

                <h2>

                    <?php echo $amenityCount; ?>

                </h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-calendar-check"></i>

                <h3>

                    Bookings

                </h3>

                <h2>

                    <?php echo $bookingCount; ?>

                </h2>

            </div>

            <div class="card">

                <i class="fa-solid fa-users"></i>

                <h3>

                    Users

                </h3>

                <h2>

                    <?php echo $userCount; ?>

                </h2>

            </div>

            <!-- <div class="card">

                <i class="fa-solid fa-envelope"></i>

                <h3>

                    Messages

                </h3>

                <h2>

                    <?php echo $messageCount; ?>

                </h2>

            </div> -->

        </div>

        <!--
Part 2 starts here

Recent Properties

Recent Bookings

Analytics

-->

    </div>

    <script src="assets/js/admin.js"></script>

</body>

</html>