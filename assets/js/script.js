/* ==========================================
   Godaddy Booking JAVASCRIPT
========================================== */

/* ==========================================
   HERO SLIDER
========================================== */

const slides = document.querySelectorAll(".slide");

let currentSlide = 0;

function showSlide(index){

    slides.forEach(slide => {

        slide.classList.remove("active");

    });

    slides[index].classList.add("active");

}

setInterval(() => {

    currentSlide++;

    if(currentSlide >= slides.length){

        currentSlide = 0;

    }

    showSlide(currentSlide);

},5000);

/* ==========================================
   STICKY NAVBAR
========================================== */

const header = document.querySelector("header");

const heroSection = document.querySelector(".hero");

/* Only pages with a hero image need the transparent-over-hero /
   solid-on-scroll behavior. Pages without one (like properties.php)
   keep whatever the CSS already set (header-solid) permanently -
   otherwise this listener would fight that CSS on every scroll. */

if (header && heroSection) {

window.addEventListener("scroll",()=>{

    if(window.scrollY > 80){

        header.style.position = "fixed";

        header.style.background = "rgba(33,29,26,.96)";

        header.style.backdropFilter = "blur(10px)";

        header.style.boxShadow = "0 10px 30px rgba(0,0,0,.15)";

    }

    else{

        header.style.position = "absolute";

        header.style.background = "transparent";

        header.style.boxShadow = "none";

    }

});

}

/* ==========================================
   FADE ANIMATION
========================================== */

const observer = new IntersectionObserver((entries)=>{

    entries.forEach(entry=>{

        if(entry.isIntersecting){

            entry.target.classList.add("show");

        }

    });

},{
    threshold:.15
});

const hiddenElements = document.querySelectorAll(

".property-card,.feature,.testimonial,.destination"

);

hiddenElements.forEach(el=>{

    el.classList.add("hidden");

    observer.observe(el);

});

/* ==========================================
   SMOOTH SCROLL
========================================== */

document.querySelectorAll('a[href^="#"]').forEach(anchor=>{

    anchor.addEventListener("click",function(e){

        e.preventDefault();

        const target=document.querySelector(this.getAttribute("href"));

        if(target){

            target.scrollIntoView({

                behavior:"smooth"

            });

        }

    });

});


/* ==========================================
   HERO SEARCH - redirects to properties.php
   with whatever filters were filled in. Dates
   and guests are optional; only non-empty
   values are added to the query string, so an
   empty search still lands on properties.php
   showing everything.
========================================== */

const heroSearchBtn = document.getElementById("heroSearchBtn");

if (heroSearchBtn) {

    heroSearchBtn.addEventListener("click", function () {

        const destination = document.getElementById("heroDestination").value.trim();
        const checkIn = document.getElementById("heroCheckIn").value;
        const checkOut = document.getElementById("heroCheckOut").value;
        const guests = document.getElementById("heroGuests").value;

        const params = new URLSearchParams();

        if (destination) params.set("search", destination);
        if (checkIn) params.set("check_in", checkIn);
        if (checkOut) params.set("check_out", checkOut);
        if (guests) params.set("guests", guests);

        const queryString = params.toString();

        window.location.href = "properties.php" + (queryString ? "?" + queryString : "");

    });

}

/* ==========================================
   PROPERTY CARD HOVER
========================================== */

document.querySelectorAll(".property-card").forEach(card=>{

    card.addEventListener("mouseenter",()=>{

        card.style.transform="translateY(-10px) scale(1.02)";

    });

    card.addEventListener("mouseleave",()=>{

        card.style.transform="translateY(0) scale(1)";

    });

});

/* ==========================================
   ACTIVE NAV LINK
========================================== */

const navLinks=document.querySelectorAll("nav a");

navLinks.forEach(link=>{

    link.addEventListener("click",()=>{

        navLinks.forEach(nav=>{

            nav.classList.remove("active");

        });

        link.classList.add("active");

    });

});

console.log("Godaddy Booking Loaded Successfully");






/* ==========================================
   PROPERTY DETAILS - GALLERY THUMBNAILS
========================================== */

const pdMainImage = document.getElementById("pdMainImage");

const pdThumbs = document.querySelectorAll(".pd-thumb");

if (pdMainImage && pdThumbs.length > 0) {

    pdThumbs.forEach(function (thumb) {

        thumb.addEventListener("click", function () {

            pdMainImage.src = this.dataset.full;

            pdThumbs.forEach(function (t) {
                t.classList.remove("active");
            });

            this.classList.add("active");

        });

    });

}


/* ==========================================
   PROPERTY DETAILS - ROOM SELECTION
========================================== */

const pdRoomRadios = document.querySelectorAll(".pd-room-radio");
const pdRoomsCount = document.getElementById("pdRoomsCount");

const pdBookingAmount = document.querySelector(".pd-booking-amount");
const pdBookingFrom = document.querySelector(".pd-booking-from");

function pdGetRoomsCount() {

    return pdRoomsCount ? (parseInt(pdRoomsCount.value, 10) || 1) : 1;

}

function updateSelectedRoomCard() {

    pdRoomRadios.forEach(function (radio) {

        const card = radio.closest(".pd-room-card");

        if (radio.checked) {

            card.classList.add("selected");

            if (pdBookingAmount) {

                const unitPrice = parseFloat(radio.dataset.price) || 0;

                const total = unitPrice * pdGetRoomsCount();

                pdBookingAmount.textContent = "₹" + total.toLocaleString("en-IN", { maximumFractionDigits: 0 });

                if (pdBookingFrom) {
                    pdBookingFrom.textContent = "Selected Room";
                }

            }

        } else {

            card.classList.remove("selected");

        }

    });

}

if (pdRoomRadios.length > 0) {

    pdRoomRadios.forEach(function (radio) {
        radio.addEventListener("change", updateSelectedRoomCard);
    });

    if (pdRoomsCount) {
        pdRoomsCount.addEventListener("change", updateSelectedRoomCard);
    }

    /* Reflect whichever room is checked, and the default room
       count, on page load */

    updateSelectedRoomCard();

}


/* ==========================================
   REQUIRE DATES BEFORE SUBMITTING A BOOKING
   FORM (property-details.php, room-details.php)

   Uses bookingForm.elements instead of querySelector,
   since property-details.php's date fields live outside
   the <form> tag and are linked back to it via the
   form="bookingForm" attribute - querySelector only
   searches DOM descendants and would miss them.
========================================== */

const bookingForm = document.getElementById("bookingForm");

if (bookingForm) {

    bookingForm.addEventListener("submit", function (e) {

        const checkIn = bookingForm.elements["check_in"];
        const checkOut = bookingForm.elements["check_out"];

        let hasError = false;

        [checkIn, checkOut].forEach(function (field) {

            if (field) {
                field.classList.remove("pd-field-error", "rd-field-error");
            }

        });

        if (!checkIn || !checkIn.value || !checkOut || !checkOut.value) {

            hasError = true;

            if (checkIn && !checkIn.value) checkIn.classList.add("pd-field-error", "rd-field-error");

            if (checkOut && !checkOut.value) checkOut.classList.add("pd-field-error", "rd-field-error");

        } else if (checkOut.value <= checkIn.value) {

            hasError = true;

            checkOut.classList.add("pd-field-error", "rd-field-error");

        }

        if (hasError) {

            e.preventDefault();

            alert("Please select your check-in and check-out dates to continue.");

            if (checkIn && !checkIn.value) {
                checkIn.focus();
            } else if (checkOut) {
                checkOut.focus();
            }

        }

    });

}



/* ==========================================
   ROOM DETAILS - GALLERY THUMBNAILS
========================================== */

const rdMainImage = document.getElementById("rdMainImage");

const rdThumbs = document.querySelectorAll(".rd-thumb");

if (rdMainImage && rdThumbs.length > 0) {

    rdThumbs.forEach(function (thumb) {

        thumb.addEventListener("click", function () {

            rdMainImage.src = this.dataset.full;

            rdThumbs.forEach(function (t) {
                t.classList.remove("active");
            });

            this.classList.add("active");

        });

    });

}
