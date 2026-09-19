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

$sortBy         = trim($_GET['sort_by'] ?? "check_in_desc");

$perPage        = 50;

$page           = max(1, (int)($_GET['page'] ?? 1));

$dateError = "";

$allowedSorts = [

    "created_at_desc" => "b.created_at DESC",
    "created_at_asc"  => "b.created_at ASC",

    "check_in_desc"   => "b.check_in DESC",
    "check_in_asc"    => "b.check_in ASC",

    "check_out_desc"  => "b.check_out DESC",
    "check_out_asc"   => "b.check_out ASC",

];

if (!array_key_exists($sortBy, $allowedSorts)) {

    $sortBy = "check_in_desc";

}


/*====================================================
BOOKING QUERY - WHERE clause built once, reused for both
the COUNT (pagination math) and the main SELECT
====================================================*/

$whereClause = " WHERE 1=1 ";


/* Managers only ever see bookings for their own properties */

if ($isRestrictedManager) {

    if (!empty($managerPropertyIds)) {

        $idList = implode(",", array_map("intval", $managerPropertyIds));

        $whereClause .= " AND b.property_id IN ($idList)";

    } else {

        $whereClause .= " AND b.property_id = 0";

    }

}


if ($searchProperty != "") {

    $escaped = mysqli_real_escape_string($conn, $searchProperty);

    $whereClause .= " AND (p.title LIKE '%$escaped%' OR p.property_code LIKE '%$escaped%')";

}


if ($searchCustomer != "") {

    $escaped = mysqli_real_escape_string($conn, $searchCustomer);

    $whereClause .= " AND b.user_name LIKE '%$escaped%'";

}


$isDateFilterActive = false;

if ($searchDate != "") {

    $parts = explode("/", $searchDate);

    if (count($parts) === 3 && checkdate((int)$parts[1], (int)$parts[0], (int)$parts[2])) {

        $sqlDate = sprintf("%04d-%02d-%02d", (int)$parts[2], (int)$parts[1], (int)$parts[0]);

        $whereClause .= " AND ('$sqlDate' BETWEEN b.check_in AND b.check_out)";

        $isDateFilterActive = true;

    } else {

        $dateError = "Date must be in DD/MM/YYYY format.";

    }

}


if ($searchStatus != "" && in_array($searchStatus, $statusOptions)) {

    $escaped = mysqli_real_escape_string($conn, $searchStatus);

    $whereClause .= " AND b.booking_status = '$escaped'";

}


/*====================================================
COUNT + PAGINATION MATH
====================================================*/

$countSql = "

SELECT COUNT(*) AS total

FROM bookings b

INNER JOIN properties p ON b.property_id = p.id

LEFT JOIN users u ON b.user_id = u.id

" . $whereClause;

$countResult = mysqli_query($conn, $countSql);

$totalBookings = (int)(mysqli_fetch_assoc($countResult)['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalBookings / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

function buildBookingPageUrl($pageNum) {

    $params = $_GET;

    $params['page'] = $pageNum;

    return "bookings.php?" . http_build_query($params);

}

$sql = "

SELECT

b.*,

p.title AS property_title,

p.property_code,

u.email AS customer_email,

u.phone AS customer_phone

FROM bookings b

INNER JOIN properties p ON b.property_id = p.id

LEFT JOIN users u ON b.user_id = u.id

" . $whereClause . "

ORDER BY " . $allowedSorts[$sortBy] . "

LIMIT $perPage OFFSET $offset

";

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

        .filters .search-sort {
        flex: 0 1 220px;
        padding: 12px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        font-size: 14px;
    }
    
    .status-save-note {
        font-size: 11px;
        color: #1e7e42;
        margin-left: 8px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

        tr.row-present-on-date {
        background: #eef6ff;
        border-left: 3px solid #4a6cf7;
    }

    tr.row-present-on-date:hover {
        background: #e2eeff;
    }

    .date-filter-note {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #eef6ff;
        color: #2952b3;
        padding: 10px 16px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 16px;
    }

    .date-filter-note i {
        color: #4a6cf7;
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

        @media (max-width: 768px) {

        .filters {
            flex-direction: column;
            align-items: stretch;
        }

        .filters .search-customer,
        .filters .search-property,
        .filters .search-date,
        .filters .search-status,
        .filters .search-sort,
        .filters .btn-search {
            flex: 1 1 auto;
            width: 100%;
        }

    }

    tr.row-cancellation-requested {
        background: #fff8e6;
    }

    tr.row-cancellation-requested td:first-child {
        border-left: 4px solid #d97706;
    }

    .status-select.select-cancellation-requested {
        border-color: #d97706;
        font-weight: 600;
        color: #b45309;
    }

        .btn-export-csv {
        flex: 0 0 auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 8px;
        background: #1e7e42;
        color: #fff;
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none;
        transition: background 0.15s ease;
    }

    .btn-export-csv:hover {
        background: #166534;
    }

        @media (max-width: 768px) {

        .filters {
            flex-direction: column;
            align-items: stretch;
        }

        .filters .search-customer,
        .filters .search-property,
        .filters .search-date,
        .filters .search-status,
        .filters .search-sort,
        .filters .btn-search,
        .filters .btn-export-csv {
            flex: 1 1 auto;
            width: 100%;
        }

    }

        .export-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .export-modal {
        position: relative;
        background: #fff;
        border-radius: 14px;
        padding: 30px;
        max-width: 380px;
        width: 100%;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    }

    .export-modal h3 {
        font-size: 19px;
        margin-bottom: 6px;
        color: #333;
    }

    .export-modal-subtitle {
        font-size: 13px;
        color: #888;
        margin-bottom: 20px;
    }

    .export-modal-close {
        position: absolute;
        top: 14px;
        right: 14px;
        background: none;
        border: none;
        color: #999;
        font-size: 16px;
        cursor: pointer;
        padding: 6px;
    }

    .export-modal-close:hover {
        color: #333;
    }

    .export-range-options {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 18px;
    }

    .export-range-option {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        color: #444;
        cursor: pointer;
        padding: 10px 12px;
        border: 1px solid #eee;
        border-radius: 8px;
        transition: border-color 0.15s ease;
    }

    .export-range-option:hover {
        border-color: #4a6cf7;
    }

    .export-custom-range {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
    }

    .export-custom-field {
        flex: 1;
    }

    .export-custom-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        margin-bottom: 6px;
    }

    .export-custom-field input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: 'Poppins', sans-serif;
        font-size: 13px;
    }

    .btn-export-confirm {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px;
        border: none;
        border-radius: 8px;
        background: #1e7e42;
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .btn-export-confirm:hover {
        background: #166534;
    }

        .copyable-value {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-copy {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 4px;
        font-size: 13px;
        transition: color 0.15s ease;
    }

    .btn-copy:hover {
        color: #4a6cf7;
    }

    .btn-copy.copied {
        color: #1e7e42;
    }

    .no-value {
        color: #bbb;
    }

        /* Compact the table so Status is comfortably visible again */

    .table-container table th,
    .table-container table td {
        padding: 10px 12px;
        font-size: 13px;
    }

    .property-info h4 {
        font-size: 13px;
    }

    .property-info small {
        font-size: 11px;
    }

    .customer-name-trigger {
        background: none;
        border: none;
        font-weight: 600;
        color: #333;
        font-size: 13px;
        cursor: pointer;
        padding: 0;
        text-align: left;
    }

    .customer-name-trigger:hover {
        color: #4a6cf7;
        text-decoration: underline;
    }

    .user-detail-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2000;
        padding: 20px;
    }

    .user-detail-modal {
        position: relative;
        background: #fff;
        border-radius: 14px;
        padding: 30px;
        max-width: 340px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
    }

    .user-detail-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #eef2ff;
        color: #4a6cf7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin: 0 auto 16px;
    }

    .user-detail-modal h3 {
        font-size: 18px;
        margin-bottom: 18px;
        color: #333;
    }

    .user-detail-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 14px;
        color: #555;
        padding: 10px 0;
        border-top: 1px solid #eee;
    }

    .user-detail-row i {
        color: #4a6cf7;
        width: 16px;
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

        <select name="sort_by" class="search-sort">

            <option value="created_at_desc" <?= ($sortBy == 'created_at_desc') ? 'selected' : ''; ?>>Booking Date: Newest First</option>

            <option value="created_at_asc" <?= ($sortBy == 'created_at_asc') ? 'selected' : ''; ?>>Booking Date: Oldest First</option>

            <option value="check_in_desc" <?= ($sortBy == 'check_in_desc') ? 'selected' : ''; ?>>Check-In: Latest First</option>

            <option value="check_in_asc" <?= ($sortBy == 'check_in_asc') ? 'selected' : ''; ?>>Check-In: Earliest First</option>

            <option value="check_out_desc" <?= ($sortBy == 'check_out_desc') ? 'selected' : ''; ?>>Check-Out: Latest First</option>

            <option value="check_out_asc" <?= ($sortBy == 'check_out_asc') ? 'selected' : ''; ?>>Check-Out: Earliest First</option>

        </select>

        <button type="submit" class="btn-search">

            <i class="fa-solid fa-magnifying-glass"></i>

            Search

        </button>

        <button type="button" class="btn-export-csv" id="openExportModalBtn">

            <i class="fa-solid fa-download"></i>

            Download CSV

        </button>

    </form>

    <!-- ================= EXPORT MODAL ================= -->

    <div class="export-modal-overlay" id="exportModalOverlay" style="display:none;">

        <div class="export-modal">

            <button type="button" class="export-modal-close" id="exportModalClose" aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

            <h3>Download Bookings CSV</h3>

            <p class="export-modal-subtitle">Choose which bookings to include, based on their check-in date.</p>

            <div class="export-range-options">

                <label class="export-range-option">
                    <input type="radio" name="export_range" value="all" checked>
                    All Bookings
                </label>

                <label class="export-range-option">
                    <input type="radio" name="export_range" value="1month">
                    Past 1 Month
                </label>

                <label class="export-range-option">
                    <input type="radio" name="export_range" value="6months">
                    Past 6 Months
                </label>

                <label class="export-range-option">
                    <input type="radio" name="export_range" value="1year">
                    Past 1 Year
                </label>

                <label class="export-range-option">
                    <input type="radio" name="export_range" value="custom">
                    Custom Range
                </label>

            </div>

            <div class="export-custom-range" id="exportCustomRange" style="display:none;">

                <div class="export-custom-field">
                    <label>From</label>
                    <input type="date" id="exportDateFrom">
                </div>

                <div class="export-custom-field">
                    <label>To</label>
                    <input type="date" id="exportDateTo">
                </div>

            </div>

            <button type="button" class="btn-export-confirm" id="exportConfirmBtn">

                <i class="fa-solid fa-download"></i>

                Download

            </button>

        </div>

    </div>

    <?php if ($dateError != "") { ?>

        <div class="date-error"><?= htmlspecialchars($dateError); ?></div>

    <?php } ?>

    <?php if ($isDateFilterActive) { ?>

        <div class="date-filter-note">

            <i class="fa-solid fa-calendar-check"></i>

            Showing guests present on <?= htmlspecialchars($searchDate); ?>

        </div>

    <?php } ?>

    <div class="table-container">

        <table>

            <thead>

                <tr>

                    <th>Customer</th>

                    <th>Phone</th>

                    <th>Property</th>

                    <th>Booking Date</th>

                    <th>Check In</th>

                    <th>Check Out</th>

                    <th>Nights</th>

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

                        <?php

                        $rowClasses = [];

                        if ($isCancellationRequest) {
                            $rowClasses[] = 'row-cancellation-requested';
                        }

                        if ($isDateFilterActive) {
                            $rowClasses[] = 'row-present-on-date';
                        }

                        ?>

                        <tr class="<?= implode(' ', $rowClasses); ?>">

                            <td>

                                <button type="button" class="customer-cell customer-name-trigger"

                                    data-user-name="<?= htmlspecialchars($booking['user_name']); ?>"

                                    data-user-email="<?= htmlspecialchars($booking['customer_email'] ?? ''); ?>"

                                    data-user-phone="<?= htmlspecialchars($booking['customer_phone'] ?? ''); ?>">

                                    <?= htmlspecialchars($booking['user_name']); ?>

                                </button>

                            </td>

                            <td>

                                <?php if (!empty($booking['customer_phone'])) { ?>

                                    <span class="copyable-value">

                                        <?= htmlspecialchars($booking['customer_phone']); ?>

                                        <button type="button" class="btn-copy" data-copy-value="<?= htmlspecialchars($booking['customer_phone']); ?>" aria-label="Copy phone number">

                                            <i class="fa-regular fa-copy"></i>

                                        </button>

                                    </span>

                                <?php } else { ?>

                                    <span class="no-value">—</span>

                                <?php } ?>

                            </td>

                            <td>

                                <div class="property-info">

                                    <h4><?= htmlspecialchars($booking['property_title']); ?></h4>

                                    <small><?= htmlspecialchars($booking['property_code']); ?></small>

                                </div>

                            </td>

                            <td><?= date("d M Y", strtotime($booking['created_at'])); ?></td>

                            <td><?= date("d M Y", strtotime($booking['check_in'])); ?></td>

                            <td><?= date("d M Y", strtotime($booking['check_out'])); ?></td>

                            <?php

                            $bkInDate = DateTime::createFromFormat("Y-m-d", $booking['check_in']);

                            $bkOutDate = DateTime::createFromFormat("Y-m-d", $booking['check_out']);

                            $bkNights = ($bkInDate && $bkOutDate) ? $bkOutDate->diff($bkInDate)->days : 0;

                            ?>

                            <td><?= $bkNights; ?></td>

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

                        <td colspan="11">

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

    <?php if ($totalPages > 1) { ?>

        <div class="admin-pagination">

            <?php if ($page > 1) { ?>

                <a href="<?= buildBookingPageUrl($page - 1); ?>" class="admin-pagination-btn">

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

                    <a href="<?= buildBookingPageUrl(1); ?>" class="admin-pagination-number">1</a>

                    <?php if ($windowStart > 2) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                <?php } ?>

                <?php for ($p = $windowStart; $p <= $windowEnd; $p++) { ?>

                    <a href="<?= buildBookingPageUrl($p); ?>" class="admin-pagination-number <?= ($p == $page) ? 'active' : ''; ?>"><?= $p; ?></a>

                <?php } ?>

                <?php if ($windowEnd < $totalPages) { ?>

                    <?php if ($windowEnd < $totalPages - 1) { ?>
                        <span class="admin-pagination-ellipsis">…</span>
                    <?php } ?>

                    <a href="<?= buildBookingPageUrl($totalPages); ?>" class="admin-pagination-number"><?= $totalPages; ?></a>

                <?php } ?>

            </div>

            <?php if ($page < $totalPages) { ?>

                <a href="<?= buildBookingPageUrl($page + 1); ?>" class="admin-pagination-btn">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </a>

            <?php } else { ?>

                <span class="admin-pagination-btn admin-pagination-disabled">

                    Next <i class="fa-solid fa-chevron-right"></i>

                </span>

            <?php } ?>

        </div>

        <p class="admin-pagination-info">Page <?= $page; ?> of <?= $totalPages; ?> (<?= $totalBookings; ?> total bookings)</p>

    <?php } ?>


    <!-- ================= CUSTOMER DETAIL MODAL ================= -->

    <div class="user-detail-overlay" id="userDetailOverlay" style="display:none;">

        <div class="user-detail-modal">

            <button type="button" class="export-modal-close" id="userDetailClose" aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

            <div class="user-detail-avatar">

                <i class="fa-solid fa-user"></i>

            </div>

            <h3 id="userDetailName"></h3>

            <div class="user-detail-row">

                <i class="fa-regular fa-envelope"></i>

                <span id="userDetailEmail"></span>

            </div>

            <div class="user-detail-row">

                <i class="fa-solid fa-phone"></i>

                <span id="userDetailPhone"></span>

            </div>

        </div>

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

        /* ==========================================
       EXPORT MODAL
    ========================================== */

    const openExportModalBtn = document.getElementById("openExportModalBtn");
    const exportModalOverlay = document.getElementById("exportModalOverlay");
    const exportModalClose = document.getElementById("exportModalClose");
    const exportCustomRange = document.getElementById("exportCustomRange");
    const exportConfirmBtn = document.getElementById("exportConfirmBtn");

    if (openExportModalBtn) {

        openExportModalBtn.addEventListener("click", function () {
            exportModalOverlay.style.display = "flex";
        });

        exportModalClose.addEventListener("click", function () {
            exportModalOverlay.style.display = "none";
        });

        exportModalOverlay.addEventListener("click", function (e) {

            if (e.target === exportModalOverlay) {
                exportModalOverlay.style.display = "none";
            }

        });

        document.querySelectorAll('input[name="export_range"]').forEach(function (radio) {

            radio.addEventListener("change", function () {

                exportCustomRange.style.display = (this.value === "custom") ? "flex" : "none";

            });

        });

        exportConfirmBtn.addEventListener("click", function () {

            const selectedRange = document.querySelector('input[name="export_range"]:checked').value;

            const currentParams = new URLSearchParams(window.location.search);

            const exportParams = new URLSearchParams();

            /* Carry over the page's existing filters (customer, property,
               status, date, sort) so the export still respects whatever
               the admin is currently searching/viewing */

            currentParams.forEach(function (value, key) {
                exportParams.set(key, value);
            });

            if (selectedRange === "custom") {

                const fromValue = document.getElementById("exportDateFrom").value;
                const toValue = document.getElementById("exportDateTo").value;

                if (!fromValue || !toValue) {

                    alert("Please choose both a from and to date.");

                    return;

                }

                exportParams.set("range_from", fromValue);
                exportParams.set("range_to", toValue);

            } else if (selectedRange !== "all") {

                exportParams.set("range_preset", selectedRange);

            }

            window.location.href = "bookings_export.php?" + exportParams.toString();

        });

    }

        document.querySelectorAll(".btn-copy").forEach(function (button) {

        button.addEventListener("click", function () {

            const value = this.dataset.copyValue;

            navigator.clipboard.writeText(value).then(() => {

                const icon = this.querySelector("i");

                this.classList.add("copied");

                icon.classList.remove("fa-copy");
                icon.classList.add("fa-check");

                setTimeout(() => {

                    this.classList.remove("copied");
                    icon.classList.remove("fa-check");
                    icon.classList.add("fa-copy");

                }, 1500);

            }).catch(() => {

                alert("Couldn't copy to clipboard. Please copy manually.");

            });

        });

    });

        document.querySelectorAll(".customer-name-trigger").forEach(function (button) {

        button.addEventListener("click", function () {

            document.getElementById("userDetailName").textContent = this.dataset.userName || "—";

            document.getElementById("userDetailEmail").textContent = this.dataset.userEmail || "No email on file";

            document.getElementById("userDetailPhone").textContent = this.dataset.userPhone || "No phone on file";

            document.getElementById("userDetailOverlay").style.display = "flex";

        });

    });

    document.getElementById("userDetailClose").addEventListener("click", function () {

        document.getElementById("userDetailOverlay").style.display = "none";

    });

    document.getElementById("userDetailOverlay").addEventListener("click", function (e) {

        if (e.target === this) {
            this.style.display = "none";
        }

    });

</script>

</body>

</html>