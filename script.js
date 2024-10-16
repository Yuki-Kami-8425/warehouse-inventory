// Toggle Sidebar
function toggleSidebar() {
    let sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("collapsed");
}

// Show sections
function showHome() {
    document.getElementById("home-section").classList.add("active");
    document.getElementById("dashboard-section").classList.remove("active");
    document.getElementById("edit-warehouse-section").classList.remove("active");
}

function showEditWarehouse() {
    document.getElementById("home-section").classList.remove("active");
    document.getElementById("dashboard-section").classList.remove("active");
    document.getElementById("edit-warehouse-section").classList.add("active");
}

// Slideshow logic
let slideIndex = 0;
showSlides();

function showSlides() {
    let i;
    let slides = document.getElementsByClassName("mySlides");
    let dots = document.getElementsByClassName("dot");
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    slideIndex++;
    if (slideIndex > slides.length) {slideIndex = 1}
    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }
    slides[slideIndex-1].style.display = "block";
    dots[slideIndex-1].className += " active";
    setTimeout(showSlides, 5000); // Change image every 5 seconds
}
