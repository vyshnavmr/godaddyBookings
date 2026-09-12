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



const pdRoomRadios = document.querySelectorAll(".pd-room-radio");
const pdRoomsCount = document.getElementById("pdRoomsCount");

const pdBookingAmount = document.querySelector(".pd-booking-amount");
const pdBookingFrom = document.querySelector(".pd-booking-from");

function pdRebuildRoomsDropdown(maxRooms) {

    if (!pdRoomsCount) return;

    const previousValue = parseInt(pdRoomsCount.value, 10) || 1;

    pdRoomsCount.innerHTML = "";

    for (let r = 1; r <= maxRooms; r++) {

        const opt = document.createElement("option");
        opt.value = r;
        opt.textContent = r + (r > 1 ? " Rooms" : " Room");
        pdRoomsCount.appendChild(opt);

    }

    pdRoomsCount.value = previousValue <= maxRooms ? previousValue : maxRooms;

}

const pdGuestsCount = document.getElementById("pdGuestsCount");

function pdRebuildGuestsDropdown(maxGuests) {

    if (!pdGuestsCount) return;

    const previousValue = parseInt(pdGuestsCount.value, 10) || 1;

    pdGuestsCount.innerHTML = "";

    for (let g = 1; g <= maxGuests; g++) {

        const opt = document.createElement("option");
        opt.value = g;
        opt.textContent = g + (g > 1 ? " Guests" : " Guest");
        pdGuestsCount.appendChild(opt);

    }

    pdGuestsCount.value = previousValue <= maxGuests ? previousValue : maxGuests;

}

function pdGetRoomsCount() {

    return pdRoomsCount ? (parseInt(pdRoomsCount.value, 10) || 1) : 1;

}

function updateSelectedRoomCard() {

    pdRoomRadios.forEach(function (radio) {

        const card = radio.closest(".pd-room-card");

        if (radio.checked) {

            card.classList.add("selected");

            const maxRooms = parseInt(radio.dataset.maxRooms, 10) || 1;

            pdRebuildRoomsDropdown(maxRooms);

            const pdRoomsHint = document.getElementById("pdRoomsHint");

            if (pdRoomsHint) {

                pdRoomsHint.textContent = maxRooms <= 3
                    ? "Only " + maxRooms + " room" + (maxRooms > 1 ? "s" : "") + " left of this type."
                    : "";

            }

            const maxGuestsPerRoom = parseInt(radio.dataset.maxGuests, 10) || 1;

            const selectedRoomsCount = pdGetRoomsCount();

            pdRebuildGuestsDropdown(maxGuestsPerRoom * selectedRoomsCount);

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
   ROOM DETAILS - GUESTS SCALE WITH ROOMS
   (only one room on this page, so guests max
   is simply this room's base capacity times
   however many rooms are selected)
========================================== */

const rdRoomsCount = document.getElementById("rdRoomsCount");
const rdGuestsCount = document.getElementById("rdGuestsCount");
const rdBaseMaxGuestsInput = document.getElementById("rdBaseMaxGuests");

function rdRebuildGuestsDropdown(maxGuests) {

    if (!rdGuestsCount) return;

    const previousValue = parseInt(rdGuestsCount.value, 10) || 1;

    rdGuestsCount.innerHTML = "";

    for (let g = 1; g <= maxGuests; g++) {

        const opt = document.createElement("option");
        opt.value = g;
        opt.textContent = g + (g > 1 ? " Guests" : " Guest");
        rdGuestsCount.appendChild(opt);

    }

    rdGuestsCount.value = previousValue <= maxGuests ? previousValue : maxGuests;

}

function rdUpdateGuestsForRoomsCount() {

    if (!rdRoomsCount || !rdBaseMaxGuestsInput) return;

    const roomsCount = parseInt(rdRoomsCount.value, 10) || 1;

    const baseMaxGuests = parseInt(rdBaseMaxGuestsInput.value, 10) || 1;

    rdRebuildGuestsDropdown(baseMaxGuests * roomsCount);

}

if (rdRoomsCount) {

    rdRoomsCount.addEventListener("change", rdUpdateGuestsForRoomsCount);

    rdUpdateGuestsForRoomsCount();

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

/* ==========================================
   BOOKING CONFIRMATION MODAL
========================================== */

const bkcOverlay = document.getElementById("bkcOverlay");
const bkcClose = document.getElementById("bkcClose");

function closeBkcModal() {

    if (!bkcOverlay) return;

    bkcOverlay.remove();

    /* Clean the ?booking_confirmed=1 out of the URL so a page
       refresh, or the person navigating back later, doesn't
       re-trigger the modal for a booking that's already been seen. */

    const url = new URL(window.location.href);

    url.searchParams.delete("booking_confirmed");

    window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);

}

if (bkcOverlay) {

    if (bkcClose) {
        bkcClose.addEventListener("click", closeBkcModal);
    }

    bkcOverlay.addEventListener("click", function (e) {

        if (e.target === bkcOverlay) {
            closeBkcModal();
        }

    });

    document.addEventListener("keydown", function (e) {

        if (e.key === "Escape") {
            closeBkcModal();
        }

    });

}

/* ==========================================
   FAQ ACCORDION
========================================== */

document.querySelectorAll(".faq-question").forEach(function (button) {

    button.addEventListener("click", function () {

        const item = button.closest(".faq-item");
        const answer = item.querySelector(".faq-answer");
        const isOpen = item.classList.contains("open");

        // Close every other open item, so only one is expanded at a time

        document.querySelectorAll(".faq-item.open").forEach(function (openItem) {

            if (openItem !== item) {

                openItem.classList.remove("open");
                openItem.querySelector(".faq-answer").style.maxHeight = null;

            }

        });

        if (isOpen) {

            item.classList.remove("open");
            answer.style.maxHeight = null;

        } else {

            item.classList.add("open");
            answer.style.maxHeight = answer.scrollHeight + "px";

        }

    });

});