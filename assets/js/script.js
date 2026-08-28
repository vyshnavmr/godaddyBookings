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