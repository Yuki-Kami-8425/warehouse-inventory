let slideIndex = 0;
showSlides();

function showSlides() {
    let slides = document.querySelectorAll('.slide');
    let dots = document.querySelectorAll('.dot');
    
    slides.forEach((slide, index) => {
        slide.style.display = 'none';
        dots[index].className = dots[index].className.replace(" active", "");
    });

    slideIndex++;
    if (slideIndex > slides.length) {
        slideIndex = 1;
    }

    slides[slideIndex - 1].style.display = 'block';
    dots[slideIndex - 1].className += " active";
    
    setTimeout(showSlides, 10000); // Change slide every 10 seconds
}

function showSlide(index) {
    slideIndex = index;
    showSlides();
}

function toggleSidebar() {
    let sidebar = document.getElementById('sidebar');
    let content = document.querySelector('.content');

    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed');
        content.classList.remove('collapsed');
    } else {
        sidebar.classList.add('collapsed');
        content.classList.add('collapsed');
    }
}

function showPage(pageId) {
    let pages = document.querySelectorAll('.page');
    
    pages.forEach(page => {
        page.style.display = 'none';
    });
    
    document.getElementById(pageId).style.display = 'block';
}
