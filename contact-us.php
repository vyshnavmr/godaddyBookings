<?php

require_once "config/db.php";

/*====================================================
PROPERTY TYPES - reused for the "What are you interested
in" dropdown, same pattern as the nav dropdown in header.php
====================================================*/

$contactPropertyTypes = [];

$typeResult = mysqli_query($conn, "SELECT * FROM property_types WHERE is_active = 1 ORDER BY type_name ASC");

if ($typeResult) {

    while ($row = mysqli_fetch_assoc($typeResult)) {
        $contactPropertyTypes[] = $row;
    }

}


/*====================================================
CONTACT DETAILS
NOTE: placeholder values - replace with your real
address/hours before going live.
====================================================*/

$contactPhone   = "+91 9895928883";

$contactEmail   = "info@godaddybooking.com";

$contactHours   = "24/7";

$contactAddress = "Godaddy Booking, MG Road, Kochi, Kerala, India.";

// $contactMapUrl  = "https://www.google.com/maps/search/?api=1&query=" . urlencode($contactAddress);

// Digits-only version for the wa.me link (country code + number, no + or spaces)

$whatsappNumber = "919895928883";


/*====================================================
RENDER PAGE
====================================================*/

$pageTitle = "Contact Us - Godaddy Booking";

$metaDescription = "Get in touch with Godaddy Booking for questions about your stay or booking. Call, email, or message us on WhatsApp.";

$currentPage = "contact";

$pageCss = ["assets/css/booking.css", "assets/css/contact.css"];

include "includes/header.php";

?>

<section class="bk-page">

    <div class="container">

        <div class="section-heading">

            <div class="eyebrow">Get in Touch</div>

            <h2>Contact Us</h2>

            <p>Have a question about a booking or a property? We're happy to help.</p>

        </div>

        <div class="cu-layout">

            <!-- ================= CONTACT DETAILS ================= -->

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Contact Details</h2>

                </div>

                <div class="bk-card-body">

                    <div class="cu-detail-list">

                        <div class="cu-detail-item">

                            <div class="cu-detail-icon"><i class="fa-solid fa-phone"></i></div>

                            <div>
                                <h4>Phone</h4>
                                <p><a href="tel:<?= htmlspecialchars(str_replace(' ', '', $contactPhone)); ?>"><?= htmlspecialchars($contactPhone); ?></a></p>
                            </div>

                        </div>

                        <div class="cu-detail-item">

                            <div class="cu-detail-icon"><i class="fa-solid fa-envelope"></i></div>

                            <div>
                                <h4>Email</h4>
                                <p><a href="mailto:<?= htmlspecialchars($contactEmail); ?>"><?= htmlspecialchars($contactEmail); ?></a></p>
                            </div>

                        </div>

                        <div class="cu-detail-item">

                            <div class="cu-detail-icon"><i class="fa-solid fa-clock"></i></div>

                            <div>
                                <h4>Hours</h4>
                                <p><?= htmlspecialchars($contactHours); ?></p>
                            </div>

                        </div>

                        <div class="cu-detail-item">

                            <div class="cu-detail-icon"><i class="fa-solid fa-location-dot"></i></div>

                            <div>
                                <h4>Address</h4>
                                <p><?= htmlspecialchars($contactAddress); ?></p>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ================= CONTACT FORM ================= -->

            <div class="bk-card">

                <div class="bk-card-header">

                    <h2>Send Us a Message</h2>

                </div>

                <div class="bk-card-body">

                    <p class="bk-card-subtitle">Fill this in and it opens WhatsApp with your message ready to send - no waiting for an email reply.</p>

                    <form id="contactWhatsappForm">

                        <div class="bk-form-group" style="margin-bottom:20px;">
                            <label>Name</label>
                            <input type="text" id="cuName" name="name" placeholder="Your full name" required>
                        </div>

                        <div class="bk-form-group" style="margin-bottom:20px;">
                            <label>Phone</label>
                            <input type="tel" id="cuPhone" name="phone" placeholder="Your phone number" required>
                        </div>

                        <div class="bk-form-group" style="margin-bottom:20px;">

                            <label>Property Interest</label>

                            <select id="cuInterest" name="interest">

                                <option value="General enquiry">General enquiry</option>

                                <?php foreach ($contactPropertyTypes as $type) { ?>

                                    <option value="<?= htmlspecialchars($type['type_name']); ?>"><?= htmlspecialchars($type['type_name']); ?></option>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="bk-form-group" style="margin-bottom:20px;">
                            <label>Message</label>
                            <textarea id="cuMessage" name="message" placeholder="How can we help?" required></textarea>
                        </div>

                        <button type="submit" class="bk-submit-btn">
                            <i class="fa-brands fa-whatsapp"></i>
                            Send via WhatsApp
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>

<script>

    (function () {

        const form = document.getElementById("contactWhatsappForm");

        if (!form) return;

        const whatsappNumber = "<?= htmlspecialchars($whatsappNumber); ?>";

        form.addEventListener("submit", function (e) {

            e.preventDefault();

            const name = document.getElementById("cuName").value.trim();
            const phone = document.getElementById("cuPhone").value.trim();
            const interest = document.getElementById("cuInterest").value;
            const message = document.getElementById("cuMessage").value.trim();

            const text =
                "Hi Godaddy Booking, my name is " + name +
                " (Phone: " + phone + ")." +
                "\nI'm interested in: " + interest +
                "\n\nMessage: " + message;

            const waLink = "https://wa.me/" + whatsappNumber + "?text=" + encodeURIComponent(text);

            window.location.href = waLink;

        });

    })();

</script>

<?php include "includes/footer.php"; ?>