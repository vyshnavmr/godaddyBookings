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
   SEARCH BUTTON
========================================== */

const searchButton=document.querySelector(".search-box button");

if(searchButton){

searchButton.addEventListener("click",()=>{

    alert("Property search will be connected to the database later.");

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

const pdBookingAmount = document.querySelector(".pd-booking-amount");
const pdBookingFrom = document.querySelector(".pd-booking-from");

function updateSelectedRoomCard() {

    pdRoomRadios.forEach(function (radio) {

        const card = radio.closest(".pd-room-card");

        if (radio.checked) {

            card.classList.add("selected");

            if (pdBookingAmount) {

                const price = parseFloat(radio.dataset.price) || 0;

                pdBookingAmount.textContent = "₹" + price.toLocaleString("en-IN", { maximumFractionDigits: 0 });

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

    /* Reflect whichever room is checked by default on page load */

    updateSelectedRoomCard();

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
