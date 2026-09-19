<?php

session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../../config/db.php";

/*====================================================
MANAGER SCOPE - identical to bookings.php
====================================================*/

$isRestrictedManager = isset($_SESSION['is_manager']) && (int)$_SESSION['is_manager'] === 1;

$managerPropertyIds = $_SESSION['manager_property_ids'] ?? [];

$statusOptions = ["Booked", "Pending", "Cancellation Requested", "Cancelled"];


/*====================================================
SAME FILTERS AS bookings.php - the export should reflect
exactly whatever the admin is currently looking at, not
the entire table.
====================================================*/

$searchProperty = trim($_GET['search_property'] ?? "");

$searchCustomer = trim($_GET['search_customer'] ?? "");

$searchDate     = trim($_GET['search_date'] ?? "");

$searchStatus   = trim($_GET['search_status'] ?? "");

$sortBy         = trim($_GET['sort_by'] ?? "check_in_desc");

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
BOOKING QUERY - same construction as bookings.php
====================================================*/

$sql = "

SELECT

b.*,

p.title AS property_title,

p.property_code,

u.email AS customer_email,

u.phone AS customer_phone

FROM bookings b

INNER JOIN properties p

ON b.property_id = p.id

LEFT JOIN users u

ON b.user_id = u.id

WHERE 1=1

";

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

    }

    /* If the date is invalid, silently skip the filter here rather
       than showing an error - this is a download, not a page render,
       so there's nowhere sensible to display a validation message. */

}

if ($searchStatus != "" && in_array($searchStatus, $statusOptions)) {

    $escaped = mysqli_real_escape_string($conn, $searchStatus);

    $sql .= " AND b.booking_status = '$escaped'";

}


/*====================================================
DATE RANGE FILTER - either a quick preset (relative to
today, based on booking date / created_at) or an explicit
custom from/to range. Neither param means "all bookings",
the default, so nothing is added to the query in that case.
====================================================*/

$rangePreset = trim($_GET['range_preset'] ?? "");

$rangeFrom   = trim($_GET['range_from'] ?? "");

$rangeTo     = trim($_GET['range_to'] ?? "");

$allowedPresets = [

    "1month"  => "1 MONTH",
    "6months" => "6 MONTH",
    "1year"   => "1 YEAR",

];

if ($rangePreset !== "" && array_key_exists($rangePreset, $allowedPresets)) {

    $interval = $allowedPresets[$rangePreset];

    $sql .= " AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL $interval)";

} elseif ($rangeFrom !== "" && $rangeTo !== "") {

    /* Basic format guard - expects YYYY-MM-DD from the <input type="date"> */

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeFrom) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rangeTo)) {

        $escapedFrom = mysqli_real_escape_string($conn, $rangeFrom);

        $escapedTo = mysqli_real_escape_string($conn, $rangeTo);

        $sql .= " AND b.created_at BETWEEN '$escapedFrom 00:00:00' AND DATE_ADD('$escapedTo', INTERVAL 1 DAY)";

    }

}

$sql .= " ORDER BY " . $allowedSorts[$sortBy];

$result = mysqli_query($conn, $sql);


/*====================================================
SANITIZE FOR CSV - prevents formula injection. If a cell's
value starts with =, +, -, or @, Excel/Sheets may try to
execute it as a formula when opened. Prefixing with a
single quote neutralizes that while keeping the visible
text unchanged.
====================================================*/

function csvSafe($value) {

    $value = (string) $value;

    if (preg_match('/^[=+\-@]/', $value)) {

        return "'" . $value;

    }

    return $value;

}


/*====================================================
OUTPUT CSV
====================================================*/

$filename = "bookings_export_" . date("Y-m-d_His") . ".csv";

header("Content-Type: text/csv; charset=utf-8");

header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");

/* UTF-8 BOM so Excel correctly displays the ₹ symbol and
   any non-ASCII characters instead of showing garbled text */

fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

fputcsv($output, [

    "Booking ID",
    "Customer",
    "Email",
    "Phone",
    "Property",
    "Property Code",
    "Booking Date",
    "Check In",
    "Check Out",
    "Nights",
    "Guests",
    "Rooms",
    "Total Price",
    "Payment Status",
    "Booking Status"

]);

while ($booking = mysqli_fetch_assoc($result)) {

    $inDate = DateTime::createFromFormat("Y-m-d", $booking['check_in']);

    $outDate = DateTime::createFromFormat("Y-m-d", $booking['check_out']);

    $nights = ($inDate && $outDate) ? $outDate->diff($inDate)->days : 0;

    fputcsv($output, [

    $booking['id'],
        csvSafe($booking['user_name']),
        csvSafe($booking['customer_email'] ?? ''),
        csvSafe($booking['customer_phone'] ?? ''),
        csvSafe($booking['property_title']),
        csvSafe($booking['property_code']),
        "'" . date("d M Y", strtotime($booking['created_at'])),
        "'" . date("d M Y", strtotime($booking['check_in'])),
        "'" . date("d M Y", strtotime($booking['check_out'])),
        $nights,
        (int)$booking['guests'],
        (int)$booking['rooms'],
        number_format($booking['total_price'], 2, '.', ''),
        $booking['payment_status'],
        $booking['booking_status']

    ]);

}

fclose($output);

exit();

?>