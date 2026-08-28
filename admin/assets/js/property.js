/* ===========================
   COVER IMAGE
=========================== */

const coverInput=document.getElementById("coverImage");

const coverPreview=document.getElementById("coverPreview");

coverInput.addEventListener("change",function(){

    const file=this.files[0];

    if(!file) return;

    coverPreview.src=URL.createObjectURL(file);

    coverPreview.style.display="block";

});


/* ===========================
   GALLERY
=========================== */

const galleryInput=document.getElementById("galleryImages");

const galleryPreview=document.getElementById("galleryPreview");

galleryInput.addEventListener("change",function(){

    galleryPreview.innerHTML="";

    [...this.files].forEach(file=>{

        const img=document.createElement("img");

        img.src=URL.createObjectURL(file);

        galleryPreview.appendChild(img);

    });

});