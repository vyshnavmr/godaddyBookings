<?php

$pageTitle = "Privacy Policy - Godaddy Booking";

$metaDescription = "Read Godaddy Booking's privacy policy to understand what information we collect and how it's used.";

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

                    <h2>Privacy Policy</h2>

                </div>

                <div class="bk-card-body legal-content">

                    <p class="legal-updated">Last updated: <?= date("F Y"); ?></p>

                    <h3>1. Information We Collect</h3>

                    <p>When you create an account or make a booking on Godaddy Booking, we collect the following information:</p>

                    <ul>
                        <li>Full name</li>
                        <li>Email address</li>
                        <li>Mobile phone number</li>
                        <li>A password, which we store in encrypted (hashed) form and never in plain text</li>
                        <li>Booking details, including check-in/check-out dates, number of guests and rooms, and the property or room selected</li>
                    </ul>

                    <h3>2. How We Use Your Information</h3>

                    <p>We use the information you provide to:</p>

                    <ul>
                        <li>Create and manage your account</li>
                        <li>Process and confirm your bookings</li>
                        <li>Have a representative contact you by phone to confirm your booking and arrange payment</li>
                        <li>Provide customer support</li>
                    </ul>

                    <p>We do not sell or rent your personal information to third parties.</p>

                    <h3>3. Payment Information</h3>

                    <p>Godaddy Booking does not process or collect any payment information through this website. Once you submit a booking, a representative will contact you by phone to confirm your stay, and payment is completed separately via GPay (Google Pay) at that time. Any payment status shown on your booking (such as Paid, Unpaid, or Refunded) reflects information recorded by our staff based on this offline payment, not a transaction processed by this site.</p>

                    <h3>4. Cookies and Sessions</h3>

                    <p>We use a session cookie solely to keep you logged in while you browse and book on our site. We do not currently use tracking or advertising cookies.</p>

                    <h3>5. Data Sharing</h3>

                    <p>Your booking details may be shared with the specific property or property manager you've booked with, solely for the purpose of fulfilling your stay.</p>

                    <h3>6. Data Security</h3>

                    <p>We take reasonable technical measures to protect your information, including encrypting passwords and using secure database practices. However, no method of transmission or storage is 100% secure, and we cannot guarantee absolute security.</p>

                    <h3>7. Your Rights</h3>

                    <p>You can view your booking history at any time by logging into your account. To request access to, correction of, or deletion of your personal data, please contact us using the details on our <a href="contact-us.php">Contact Us</a> page.</p>

                    <h3>8. Changes to This Policy</h3>

                    <p>We may update this Privacy Policy from time to time. Continued use of the site after changes are posted constitutes acceptance of the updated policy.</p>

                    <h3>9. Contact Us</h3>

                    <p>If you have questions about this Privacy Policy, please reach out via our <a href="contact-us.php">Contact Us</a> page.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>