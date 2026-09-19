<?php

$pageTitle = "Account Recovery - Godaddy Booking";

$metaDescription = "Need help accessing your account? Contact our team for assistance.";

$currentPage = "";

$pageCss = "assets/css/booking.css";

require_once "config/db.php";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="bk-auth-wrap">

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Forgot Your Password?</h2>

                </div>

                <div class="bk-card-body" style="text-align:center;">

                    <p class="bk-card-subtitle">

                        We're not able to reset passwords automatically just yet. Please reach out to our team directly and we'll help you regain access to your account.

                    </p>

                    <div style="display:flex; flex-direction:column; gap:12px; margin-top:24px;">

                        <a href="tel:+919895928883" class="bk-submit-btn" style="display:inline-flex; align-items:center; justify-content:center; gap:10px; text-decoration:none; width:auto; padding:14px 32px; margin:0 auto;">

                            <i class="fa-solid fa-phone"></i>

                            Call +91 98959 28883

                        </a>

                        <a href="https://wa.me/919895928883?text=Hi%2C%20I%20need%20help%20accessing%20my%20account." target="_blank" rel="noopener" class="bk-submit-btn" style="display:inline-flex; align-items:center; justify-content:center; gap:10px; text-decoration:none; width:auto; padding:14px 32px; margin:0 auto; background:#25D366;">

                            <i class="fa-brands fa-whatsapp"></i>

                            Message us on WhatsApp

                        </a>

                        <a href="contact-us.php" style="color:var(--red); font-weight:600; font-size:14px; margin-top:8px;">

                            Or visit our Contact Us page

                        </a>

                    </div>

                    <p class="bk-login-prompt bk-no-border" style="margin-top:28px;">

                        Remembered your password? <a href="login.php">Log in</a>

                    </p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>