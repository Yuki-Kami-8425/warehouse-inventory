// Slideshow functionality
let slideIndex = 1;
showSlides(slideIndex);

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("slides");
    let dots = document.getElementsByClassName("dot");

    if (n > slides.length) { slideIndex = 1 }
    if (n < 1) { slideIndex = slides.length }

    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }

    for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
    }

    slides[slideIndex - 1].style.display = "block";
    dots[slideIndex - 1].className += " active";
}

// Sidebar navigation functionality
document.getElementById('homeLink').addEventListener('click', function() {
    showContent('home');
});

document.getElementById('dashboardLink').addEventListener('click', function() {
    showContent('dashboard');
});

document.getElementById('editWarehouseLink').addEventListener('click', function() {
    showContent('editWarehouse');
});

function showContent(contentId) {
    let contents = document.getElementsByClassName('content');
    for (let i = 0; i < contents.length; i++) {
        contents[i].classList.remove('active');
    }
    document.getElementById(contentId).classList.add('active');
}
