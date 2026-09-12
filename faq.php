<?php

$pageTitle = "FAQ - Godaddy Booking";

$metaDescription = "Answers to common questions about booking, payment, and cancellations with Godaddy Booking.";

$currentPage = "";

$pageCss = ["assets/css/booking.css", "assets/css/faq.css"];

require_once "config/db.php";

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="section-heading" style="margin-bottom:35px;">

            <div class="eyebrow">Need Help?</div>

            <h2>Frequently Asked Questions</h2>

            <p>Everything you need to know about booking with us. Can't find your answer? <a href="contact-us.php">Contact us</a>.</p>

        </div>

        <div class="faq-wrap">

            <div class="faq-item">

                <button type="button" class="faq-question">

                    How do I book a property?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Browse our properties, choose your check-in and check-out dates, select a room type and the number of rooms and guests, then submit your booking request. If you don't already have an account, one is created automatically using the details you provide.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    Do I need to pay when I submit a booking?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>No. Submitting a booking on our website does not charge you anything. After you book, a representative will call you to confirm the details of your stay.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    How do I actually pay for my booking?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Payment is completed via GPay (Google Pay), arranged directly with our representative during the confirmation call. We don't collect card, bank, or UPI details through this website.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    Can I cancel a booking?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Yes. Go to <a href="current-booking.php">My Bookings</a> and select "Request Cancellation" on any eligible booking. Cancellation requests can be made up until 24 hours before your check-in date.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    What if my check-in is less than 24 hours away and I need to cancel?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Self-service cancellation isn't available inside that 24-hour window. Please <a href="contact-us.php">contact us</a> directly, or call us, and our team will assist you.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    Will I get a refund if I cancel?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Submitting a cancellation request doesn't automatically guarantee a refund. Our team will confirm refund eligibility with you based on your specific booking once your request is received.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    Do I need to create an account before booking?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>No. You can book as a guest and an account is created for you automatically using the name, email, phone, and password you provide during checkout. You can also <a href="signup.php">sign up</a> in advance if you'd prefer.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    How can I see my past and current bookings?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Log in and visit <a href="current-booking.php">My Bookings</a> to see every booking on your account, along with its current status.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    Can I book more than one room at a time?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Yes. On a property's page, choose how many rooms of that type you'd like. The maximum number of guests allowed adjusts automatically to match how many rooms you select.</p>

                </div>

            </div>

            <div class="faq-item">

                <button type="button" class="faq-question">

                    I forgot my password. What do I do?

                    <i class="fa-solid fa-chevron-down"></i>

                </button>

                <div class="faq-answer">

                    <p>Click "Forgot your password?" on the <a href="login.php">Log In</a> page to reset it.</p>

                </div>

            </div>

        </div>

    </div>

</section>

<?php include "includes/footer.php"; ?>