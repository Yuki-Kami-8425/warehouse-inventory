<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: url('warehouse-bg.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            height: 100%;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 255, 0.5); /* Semi-transparent blue overlay */
            z-index: -1;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: #333;
            padding-top: 60px;
            transition: width 0.3s;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 15px;
            text-align: center;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar ul li a i {
            margin-right: 10px;
        }

        .toggle-btn {
            position: absolute;
            top: 15px;
            left: 10px;
            font-size: 30px;
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        }

        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
            padding: 20px 0;
            text-align: center;
        }

        .slideshow {
            display: flex;
            overflow: hidden;
        }

        .slide {
            display: none;
            width: 100%;
            height: 400px;
            object-fit: cover;
        }

        .dots {
            text-align: center;
            margin-top: 15px;
        }

        .dot {
            height: 15px;
            width: 15px;
            margin: 0 5px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            cursor: pointer;
        }

        .active {
            background-color: #717171;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .page {
            margin-top: 20px;
            font-size: 24px;
            color: white;
        }

        /* Hidden sidebar */
        .sidebar.collapsed {
            width: 70px;
        }

        .content.collapsed {
            margin-left: 70px;
        }

        .sidebar.collapsed ul li a {
            justify-content: center;
        }

        .sidebar.collapsed ul li a span {
            display: none;
        }

        .sidebar ul li a i {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">&#9776;</button>
        <ul>
            <li><a href="#" onclick="showPage('home')"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
            <li><a href="#" onclick="showPage('dashboard')"><i class="fas fa-tachometer-alt"></i><span class="link-text"> Dashboard</span></a></li>
            <li><a href="#" onclick="showPage('edit-warehouse')"><i class="fas fa-edit"></i><span class="link-text"> Edit Warehouse</span></a></li>
        </ul>
    </div>

    <div class="content">
        <div id="home" class="page">
            <div class="slideshow-container">
                <div class="slideshow">
                    <img class="slide" src="warehouse1.jpg" alt="Warehouse 1">
                    <img class="slide" src="warehouse2.jpg" alt="Warehouse 2">
                    <img class="slide" src="warehouse3.jpg" alt="Warehouse 3">
                </div>
                <div class="dots">
                    <span class="dot" onclick="showSlide(0)"></span>
                    <span class="dot" onclick="showSlide(1)"></span>
                    <span class="dot" onclick="showSlide(2)"></span>
                </div>
            </div>
        </div>

        <div id="dashboard" class="page" style="display:none;">Dashboard will be here.</div>
        <div id="edit-warehouse" class="page" style="display:none;">Edit Warehouse will be here.</div>
    </div>

    <script>
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

            setTimeout(showSlides, 5000); // Change slide every 5 seconds
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
    </script>
</body>
</html>
