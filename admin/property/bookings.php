<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
MANAGER SCOPE
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

/* Allowed status values for each editable field */

$statusOptions = ["Booked", "Pending", "Cancellation Requested", "Cancelled"];

$paymentOptions = ["Paid", "Unpaid", "Refunded"];


/*====================================================
SEARCH & FILTERS
====================================================*/

$searchProperty = trim($_GET['search_property'] ?? "");

$searchCustomer = trim($_GET['search_customer'] ?? "");

$searchDate     = trim($_GET['search_date'] ?? "");

$searchStatus   = trim($_GET['search_status'] ?? "");

$dateError = "";


/*====================================================
BOOKING QUERY
====================================================*/

$sql = "

SELECT

b.*,

p.title AS property_title,

p.property_code

FROM bookings b

INNER JOIN properties p

ON b.property_id = p.id

WHERE 1=1

";


/* Managers only ever see bookings for their own properties */

if ($isRestrictedManager) {

    if (!empty($managerPropertyIds)) {

        $idList = implode(",", array_map("intval", $managerPropertyIds));

        $sql .= " AND b.property_id IN ($idList)";

    } else {

        $sql .= " AND b.property_id = 0";

    }

}


if ($searchProperty != "") {

    $escaped = mysqli_real_escape_string($conn, $searchProperty);

    $sql .= " AND (p.title LIKE '%$escaped%' OR p.property_code LIKE '%$escaped%')";

}


if ($searchCustomer != "") {

    $escaped = mysqli_real_escape_string($conn, $searchCustomer);

    $sql .= " AND b.user_name LIKE '%$escaped%'";

}


if ($searchDate != "") {

    $parts = explode("/", $searchDate);

    if (count($parts) === 3 && checkdate((int)$parts[1], (int)$parts[0], (int)$parts[2])) {

        $sqlDate = sprintf("%04d-%02d-%02d", (int)$parts[2], (int)$parts[1], (int)$parts[0]);

        $sql .= " AND ('$sqlDate' BETWEEN b.check_in AND b.check_out)";

    } else {

        $dateError = "Date must be in DD/MM/YYYY format.";

    }

}


if ($searchStatus != "" && in_array($searchStatus, $statusOptions)) {

    $escaped = mysqli_real_escape_string($conn, $searchStatus);

    $sql .= " AND b.booking_status = '$escaped'";

}


$sql .= " ORDER BY b.check_in DESC";

$result = mysqli_query($conn, $sql);


/*====================================================
RENDER PAGE
====================================================*/

$currentPage = "bookings";

include "../includes/header.php";

?>

<link rel="stylesheet" href="../assets/css/property-list.css">

<style>
    .badge.status-confirmed {
        background: #e7f8ee;
        color: #1e7e42;
    }

    .badge.status-pending {
        background: #fff4e0;
        color: #b8790a;
    }

    .badge.status-cancelled {
        background: #fdeceb;
        color: #c0392b;
    }

    .status-select {
        padding: 6px 10px;
        border-radius: 6px;
        border: 1px solid #ddd;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: #fff;
    }

    .status-save-note {
        font-size: 11px;
        color: #1e7e42;
        margin-left: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .status-save-note.show {
        opacity: 1;
    }

    .filters {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
    }

    .filters input,
    .filters select {
        font-family: 'Poppins', sans-serif;
    }

    .filters .search-customer {
        flex: 2 1 240px;
    }

    .filters .search-property {
        flex: 1 1 180px;
    }

    .filters .search-date {
        flex: 0 1 150px;
    }

    .filters .search-status {
        flex: 0 1 160px;
    }

    .filters .btn-search {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: auto;
        padding: 12px 28px;
        border: none;
        border-radius: 8px;
        background: #4a6cf7;
        color: #fff;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .filters .btn-search:hover {
        background: #3a5ce0;
    }

    .customer-cell {
        font-weight: 600;
        color: #333;
    }

    .date-error {
        color: #c0392b;
        font-size: 12px;
        margin-top: -8px;
        margin-bottom: 12px;
    }
</style>

<div class="container">

    <div class="page-header">

        <h1>Bookings</h1>

    </div>

    <form method="GET" class="filters">

        <input
            type="text"
            name="search_customer"
            class="search-customer"
            placeholder="Search Customer..."
            value="<?= htmlspecialchars($searchCustomer); ?>">

        <input
            type="text"
            name="search_property"
            class="search-property"
            placeholder="Search Property..."
            value="<?= htmlspecialchars($searchProperty); ?>">

        <input
            type="text"
            name="search_date"
            class="search-date"
            placeholder="DD/MM/YYYY"
            value="<?= htmlspecialchars($searchDate); ?>">

        <select name="search_status" class="search-status">

            <option value="">All Statuses</option>

            <?php foreach ($statusOptions as $status) { ?>

                <option value="<?= $status; ?>" <?= ($searchStatus == $status) ? 'selected' : ''; ?>>

                    <?= $status; ?>

                </option>

            <?php } ?>

        </select>

        <button type="submit" class="btn-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>

    </form>

    <?php if ($dateError != "") { ?>

        <div class="date-error"><?= htmlspecialchars($dateError); ?></div>

    <?php } ?>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Customer</th>

                    <th>Property</th>

                    <th>Check In</th>

                    <th>Check Out</th>

                    <th>Guests</th>

                    <th>Total Price</th>

                    <th>Payment</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($result) > 0) { ?>

                    <?php while ($booking = mysqli_fetch_assoc($result)) { ?>

                         <?php $isCancellationRequest = ($booking['booking_status'] === 'Cancellation Requested'); ?>

                        <tr class="<?= $isCancellationRequest ? 'row-cancellation-requested' : ''; ?>">

                            <td class="customer-cell"><?= htmlspecialchars($booking['user_name']); ?></td>

                            <td>

                                <div class="property-info">

                                    <h4><?= htmlspecialchars($booking['property_title']); ?></h4>

                                    <small><?= htmlspecialchars($booking['property_code']); ?></small>

                                </div>

                            </td>

                            <td><?= date("d M Y", strtotime($booking['check_in'])); ?></td>

                            <td><?= date("d M Y", strtotime($booking['check_out'])); ?></td>

                            <td><?= (int)$booking['guests']; ?></td>

                            <td>₹<?= number_format($booking['total_price'], 2); ?></td>

                            <td>

                                <select
                                    class="status-select"
                                    data-booking-id="<?= $booking['id']; ?>"
                                    data-field="payment_status">

                                    <?php foreach ($paymentOptions as $payment) { ?>

                                        <option value="<?= $payment; ?>" <?= ($booking['payment_status'] == $payment) ? 'selected' : ''; ?>>

                                            <?= $payment; ?>

                                        </option>

                                    <?php } ?>

                                </select>

                                <span class="status-save-note">Updated</span>

                            </td>

                            <td>

                                <select
                                    class="status-select <?= $isCancellationRequest ? 'select-cancellation-requested' : ''; ?>"
                                    data-booking-id="<?= $booking['id']; ?>"
                                    data-field="booking_status">

                                    <?php foreach ($statusOptions as $status) { ?>

                                        <option value="<?= $status; ?>" <?= ($booking['booking_status'] == $status) ? 'selected' : ''; ?>>

                                            <?= $status; ?>

                                        </option>

                                    <?php } ?>

                                </select>

                                <span class="status-save-note">Updated</span>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="8">

                            <div class="empty-state">

                                <i class="fa-solid fa-calendar-xmark"></i>

                                <h3>No Bookings Found</h3>

                                <p>Try adjusting your search filters.</p>

                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<script>
    document.querySelectorAll(".status-select").forEach(function (select) {

        select.addEventListener("change", function () {

            const bookingId = this.dataset.bookingId;
            const field = this.dataset.field;
            const newValue = this.value;
            const note = this.nextElementSibling;

            this.disabled = true;

            fetch("update_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "booking_id=" + encodeURIComponent(bookingId) +
                      "&field=" + encodeURIComponent(field) +
                      "&value=" + encodeURIComponent(newValue)
            })

                .then(function (res) { return res.json(); })

                .then(function (data) {

                    select.disabled = false;

                    if (data.success) {

                        note.classList.add("show");

                        setTimeout(function () {
                            note.classList.remove("show");
                        }, 2000);

                    } else {

                        alert(data.message || "Failed to update status.");

                    }

                })

                .catch(function () {

                    select.disabled = false;

                    alert("Failed to update status. Please try again.");

                });

        });

    });
</script>

</body>

</html>