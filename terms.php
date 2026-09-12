<?php

$pageTitle = "Terms & Conditions - Godaddy Booking";

$metaDescription = "Read the terms and conditions for booking a stay through Godaddy Booking.";

$currentPage = "";

$pageCss = "assets/css/booking.css";

require_once "config/db.php";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap" style="max-width:760px;">

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Terms &amp; Conditions</h2>

                </div>

                <div class="bk-card-body legal-content">

                    <p class="legal-updated">Last updated: <?= date("F Y"); ?></p>

                    <h3>1. Acceptance of Terms</h3>

                    <p>By creating an account or making a booking through Godaddy Booking, you agree to these Terms &amp; Conditions. If you do not agree, please do not use this site.</p>

                    <h3>2. Account Registration</h3>

                    <p>To make a booking, you'll need to provide accurate personal details (name, email, phone number) and create a password. You are responsible for keeping your login credentials confidential and for all activity under your account.</p>

                    <h3>3. Bookings</h3>

                    <p>Submitting a booking through this site is a request to book a stay, not a guaranteed confirmation. Our team will follow up to confirm the details of your booking. Prices shown at the time of booking, including any discounts applied, are honored for that booking unless a pricing error is identified.</p>

                    <h3>4. Cancellations</h3>

                    <p>You may request cancellation of a booking through your <a href="current-booking.php">My Bookings</a> page up until 24 hours before the scheduled check-in date. Cancellation requests made within 24 hours of check-in are not eligible for self-service cancellation and should be discussed directly with our team via the <a href="contact-us.php">Contact Us</a> page.</p>

                    <p>Submitting a cancellation request does not guarantee a refund. Refund eligibility depends on the specific property's cancellation policy and will be confirmed by our team.</p>

                    <h3>5. Confirmation and Payment</h3>

                    <p>Submitting a booking on this site does not process any payment. After you book, a representative from our team will contact you by phone to confirm the details of your stay and coordinate next steps.</p>

                    <p>Payment is currently accepted via GPay (Google Pay) and is arranged directly with our representative during this call. We do not collect card, bank, or UPI details through this website itself.</p>

                    <h3>6. Property Information</h3>

                    <p>We make reasonable efforts to ensure property descriptions, images, pricing, and availability shown on this site are accurate and up to date. However, availability and pricing can change, and we do not guarantee that every detail is error-free at all times.</p>

                    <h3>7. User Conduct</h3>

                    <p>You agree not to misuse this site, including attempting to access other users' accounts or bookings, submitting false information, or using the site for any unlawful purpose.</p>

                    <h3>8. Limitation of Liability</h3>

                    <p>Godaddy Booking acts as a platform connecting guests with properties. We are not liable for the condition of a property, actions of a property owner or manager, or any loss or damage arising from your stay, except where required by applicable law.</p>

                    <h3>9. Changes to These Terms</h3>

                    <p>We may update these Terms &amp; Conditions from time to time. Continued use of the site after changes are posted constitutes acceptance of the updated terms.</p>

                    <h3>10. Contact Us</h3>

                    <p>Questions about these terms can be directed to us via our <a href="contact-us.php">Contact Us</a> page.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>