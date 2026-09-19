<?php
/*====================================================
BOOKING RECEIPT — PRINT / SAVE AS PDF
GODADDYBOOKING.com

ZERO-DEPENDENCY VERSION.
No Composer, no vendor/, no Dompdf. This just renders
the same branded receipt as a normal HTML page and opens
the browser's print dialog — the user picks "Save as PDF"
as the destination/printer to get a PDF file, instead of
the server generating one directly.

This is a separate file from your on-screen receipt page
(current-booking-details.php) — that one is untouched.

Folder layout assumed:
    /config/db.php
    /assets/images/logo-mb.png
====================================================*/

require_once "config/db.php";

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$booking_id = (int)($_GET['id'] ?? 0);
if ($booking_id <= 0) {
    header("Location: current-booking.php");
    exit();
}

/*====================================================
FETCH BOOKING - same ownership rule as the on-screen
receipt: user_id is enforced in the WHERE clause, not
just booking_id.

NOTE: adjust the "u.name, u.email, u.phone" join below
if your users table uses different column names.
====================================================*/

$sql = "
SELECT
b.*,
p.title AS property_title,
p.property_code,
p.address AS property_address,
d.destination_name,
r.room_name,
u.name AS guest_name,
u.email AS guest_email,
u.phone AS guest_phone
FROM bookings b
LEFT JOIN properties p ON b.property_id = p.id
LEFT JOIN destinations d ON p.destination_id = d.id
LEFT JOIN property_rooms r ON b.room_id = r.id
LEFT JOIN users u ON b.user_id = u.id
WHERE b.id = ?
AND b.user_id = ?
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header("Location: current-booking.php");
    exit();
}
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$booking) {
    header("Location: current-booking.php");
    exit();
}

$inDate  = DateTime::createFromFormat("Y-m-d", $booking['check_in']);
$outDate = DateTime::createFromFormat("Y-m-d", $booking['check_out']);
$nights  = ($inDate && $outDate) ? $outDate->diff($inDate)->days : 0;
$rooms   = (int)($booking['rooms'] ?? 1);
$totalPrice = (float)($booking['total_price'] ?? 0);

$bookedOn = DateTime::createFromFormat("Y-m-d H:i:s", $booking['created_at'])
    ?: DateTime::createFromFormat("Y-m-d", $booking['created_at']);

function money($n) {
    return "₹" . number_format((float)$n, 0);
}

$statusLower = strtolower($booking['booking_status'] ?? '');
$paymentLower = strtolower($booking['payment_status'] ?? '');

$statusClass = 'badge-default';
if ($statusLower === 'booked') $statusClass = 'badge-confirmed';
elseif ($statusLower === 'pending') $statusClass = 'badge-pending';
elseif (in_array($statusLower, ['cancelled', 'cancellation requested'])) $statusClass = 'badge-cancelled';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Booking Receipt #<?= (int)$booking['id']; ?> - GODADDYBOOKING.com</title>
<style>
    * { box-sizing: border-box; }
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #1b2135;
        margin: 0;
        padding: 0;
        background: #e9e9e9;
        font-size: 13px;
    }

    .sheet {
        max-width: 800px;
        margin: 24px auto;
        background: #ffffff;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    }

    /* ---------- TOOLBAR (screen only) ---------- */
    .toolbar {
        max-width: 800px;
        margin: 0 auto;
        padding: 14px 0;
        display: flex;
        justify-content: space-between;
        gap: 10px;
    }
    .print-btn {
        background: #1b2135;
        color: #ffffff;
        border: none;
        padding: 12px 26px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

        .close-btn {
        background: #ffffff;
        color: #1b2135;
        border: 1px solid #d1d5db;
        padding: 12px 22px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 10px;
    }
    .close-btn:hover { background: #f3f4f6; }

    .print-btn:hover { background: #2a3252; }

    /* ---------- HEADER ---------- */
    .header-band {
        background-color: #1b2135;
        padding: 30px 40px;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .brand-row { display: flex; align-items: center; }
    .header-band img.logo { width: 46px; height: auto; display: block; }
    .brand-text { padding-left: 14px; }
    .brand-name { font-size: 20px; font-weight: bold; letter-spacing: 0.5px; color: #ffffff; }
    .brand-sub { font-size: 10px; color: #e0954b; letter-spacing: 1px; margin-top: 2px; }
    .header-meta { text-align: right; font-size: 12px; color: #d8dae2; line-height: 1.7; }
    .header-meta strong { color: #ffffff; }
    .accent-strip { background-color: #e0954b; height: 6px; }

    /* ---------- BODY ---------- */
    .content { padding: 30px 40px 20px 40px; }
    h1.doc-title { font-size: 24px; letter-spacing: 1px; margin: 0 0 22px 0; color: #1b2135; }

    .bill-to-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .bill-to-label { color: #e0954b; font-size: 11px; font-weight: bold; letter-spacing: 1px; margin-bottom: 6px; }
    .bill-to-value { font-size: 13px; line-height: 1.6; color: #1b2135; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 22px; }
    table.items thead td {
        background-color: #1b2135; color: #ffffff; font-size: 11px;
        letter-spacing: 0.5px; padding: 10px 12px; font-weight: bold;
    }
    table.items tbody td { padding: 12px; border-bottom: 1px solid #e6e6e6; font-size: 13px; }
    table.items td.num { text-align: right; }

    .totals { width: 260px; margin-left: auto; margin-bottom: 26px; }
    .totals-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; }
    .totals-row .label { color: #6b7280; }
    .totals-row .val { font-weight: bold; }
    .totals-grand {
        display: flex; justify-content: space-between;
        background-color: #e0954b; color: #ffffff;
        font-weight: bold; padding: 12px 14px; border-radius: 4px; margin-top: 4px;
    }

    .status-row {
        display: flex; justify-content: space-between;
        padding-top: 16px; border-top: 1px solid #e6e6e6; margin-bottom: 26px; font-size: 13px;
    }
    .badge {
        display: inline-block; padding: 4px 12px; border-radius: 3px;
        font-size: 11px; font-weight: bold; color: #ffffff;
    }
    .badge-confirmed { background-color: #2e8b57; }
    .badge-pending { background-color: #d9a017; }
    .badge-cancelled { background-color: #b23b3b; }
    .badge-default { background-color: #6b7280; }
    .badge-paid { background-color: #2e8b57; }
    .badge-unpaid { background-color: #b23b3b; }

    .terms-title { font-size: 12px; font-weight: bold; letter-spacing: 0.5px; color: #1b2135; margin-bottom: 6px; }
    .terms-text { font-size: 11px; color: #6b7280; line-height: 1.6; }

    .footer-band {
        background-color: #1b2135; color: #ffffff;
        padding: 16px 40px; font-size: 11px;
        display: flex; justify-content: space-between;
    }

    /* ---------- PRINT ---------- */
        @media print {
        body { background: #ffffff; }
        .toolbar { display: none !important; }
        .sheet { box-shadow: none; margin: 0; max-width: 100%; }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
    }

    @media (max-width: 600px) {
        .content { padding: 20px; }
        .header-band { padding: 20px; flex-direction: column; align-items: flex-start; gap: 14px; }
        .header-meta { text-align: left; }
        .bill-to-grid { grid-template-columns: 1fr; }
        .footer-band { flex-direction: column; gap: 4px; padding: 16px 20px; }
    }
</style>
</head>
<body>

    <div class="toolbar">
        <button class="close-btn" onclick="closeReceiptWindow()">
            ✕ Close
        </button>
        <button class="print-btn" onclick="window.print()">
            🖨 Print / Save as PDF
        </button>
    </div>

    <div class="sheet">

        <div class="header-band">
            <div class="brand-row">
                <img class="logo" src="assets/images/logo-mb.png" alt="GODADDYBOOKING.com">
                <div class="brand-text">
                    <div class="brand-name">GODADDYBOOKING</div>
                    <div class="brand-sub">STAYS &amp; BOOKINGS</div>
                </div>
            </div>
            <div class="header-meta">
                <div><strong>RECEIPT #</strong> <?= (int)$booking['id']; ?></div>
                <div><strong>DATE</strong> <?= $bookedOn ? $bookedOn->format("d/m/y") : ''; ?></div>
            </div>
        </div>
        <div class="accent-strip"></div>

        <div class="content">
            <h1 class="doc-title">BOOKING RECEIPT</h1>

            <div class="bill-to-grid">
                <div>
                    <div class="bill-to-label">BILLED TO</div>
                    <div class="bill-to-value">
                        <?= htmlspecialchars($booking['guest_name'] ?? 'Guest'); ?><br>
                        <?= htmlspecialchars($booking['guest_email'] ?? ''); ?><br>
                        <?php if (!empty($booking['guest_phone'])) { ?>
                            <?= htmlspecialchars($booking['guest_phone']); ?>
                        <?php } ?>
                    </div>
                </div>
                <div>
                    <div class="bill-to-label">PROPERTY</div>
                    <div class="bill-to-value">
                        <?= htmlspecialchars($booking['property_title'] ?? 'Property no longer available'); ?><br>
                        <?php if (!empty($booking['destination_name'])) { ?>
                            <?= htmlspecialchars($booking['destination_name']); ?><br>
                        <?php } ?>
                        <?php if (!empty($booking['property_address'])) { ?>
                            <?= nl2br(htmlspecialchars($booking['property_address'])); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <table class="items">
                <thead>
                    <tr>
                        <td style="width:50%;">DESCRIPTION</td>
                        <td class="num" style="width:15%;">NIGHTS</td>
                        <td class="num" style="width:15%;">ROOMS</td>
                        <td class="num" style="width:20%;">TOTAL</td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <?= htmlspecialchars($booking['room_name'] ?? 'Room'); ?><br>
                            <span style="color:#6b7280;font-size:11px;">
                                <?= $inDate ? $inDate->format("d M Y") : ''; ?>
                                &rarr;
                                <?= $outDate ? $outDate->format("d M Y") : ''; ?>
                            </span>
                        </td>
                        <td class="num"><?= $nights; ?></td>
                        <td class="num"><?= $rooms; ?></td>
                        <td class="num"><?= money($totalPrice); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-row">
                    <span class="label">Subtotal</span>
                    <span class="val"><?= money($totalPrice); ?></span>
                </div>
                <div class="totals-grand">
                    <span>TOTAL PAID</span>
                    <span><?= money($totalPrice); ?></span>
                </div>
            </div>

            <div class="status-row">
                <div>
                    Booking Status:
                    <span class="badge <?= $statusClass; ?>">
                        <?= htmlspecialchars($booking['booking_status'] ?? 'Unknown'); ?>
                    </span>
                </div>
                <div>
                    Payment Status:
                    <span class="badge <?= $paymentLower === 'paid' ? 'badge-paid' : 'badge-unpaid'; ?>">
                        <?= htmlspecialchars($booking['payment_status'] ?? 'Unknown'); ?>
                    </span>
                </div>
            </div>

            <div class="terms-title">TERMS &amp; CONDITIONS</div>
            <div class="terms-text">
                This receipt confirms the booking detailed above made through godaddybooking.com.
                Cancellation and refund eligibility follow the policy shown on the property listing
                at the time of booking. Please retain this receipt for your records and present your
                booking reference at check-in.
            </div>
        </div>

        <div class="footer-band">
            <span>www.godaddybooking.com</span>
            <span>godaddybookings@gmail.com</span>
            <span>9895928883 / 7306453884</span>
        </div>

    </div>
<script>

    function closeReceiptWindow() { 
        if (window.history.length > 1) {
             window.history.back(); 
        }else { 
            window.close(); }
    }
</script>

</body>
</html>