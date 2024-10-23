<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        
        <ul>
            <li><a href="#" onclick="showPage('home');" class="main-link"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
            <li>
                <a href="#" onclick="toggleStations(); showPage('dashboard');" class="main-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text"> Dashboard</span>
                </a>
                <ul class="station-list">
                    <li><a href="#" onclick="loadStationData('A'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 1</span></a></li>
                    <li><a href="#" onclick="loadStationData('B'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 2</span></a></li>
                    <li><a href="#" onclick="loadStationData('C'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 3</span></a></li>
                    <li><a href="#" onclick="loadStationData('D'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 4</span></a></li>
                    <li><a href="#" onclick="loadStationData('E'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 5</span></a></li>
                    <li><a href="#" onclick="loadStationData('F'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 6</span></a></li>
                    <li><a href="#" onclick="loadStationData('G'); showPage('stationData');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 7</span></a></li>
                </ul>
            </li>
            <li><a href="#" onclick="showPage('edit-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> Edit</span></a></li>
        </ul>

        <div id="datetime" class="datetime"></div>
    </div>

    <div class="content">
        <div id="home" class="page">
            <div class="slideshow-container">
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 1</h2>
                    <img class="slide-image" src="Picture1.png" alt="Slide 1">
                </div>
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 2</h2>
                    <img class="slide-image" src="Picture2.png" alt="Slide 2">
                </div>
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 3</h2>
                    <img class="slide-image" src="Picture3.png" alt="Slide 3">
                </div>
            
                <div class="dots">
                    <span class="dot" onclick="showSlide(1)"></span>
                    <span class="dot" onclick="showSlide(2)"></span>
                    <span class="dot" onclick="showSlide(3)"></span>
                </div>
            </div>
            
        </div>

        <div id="dashboard" class="page" style="display:none;">Dashboard will be here.</div>
        <div id="edit-warehouse" class="page" style="display:none;">Edit Warehouse will be here.</div>
        <div id="all" class="page" style="display:none;">All stations content will be here.</div>
        <div id="stationData" class="page" style="display:none;"></div> <!-- Chỗ để hiển thị dữ liệu của các trạm -->
    </div>

    <script>
        function showPage(page) {
            const pages = document.querySelectorAll('.page');
            pages.forEach(p => p.style.display = 'none');
            document.getElementById(page).style.display = 'block';
        }

        function toggleStations() {
            const stationList = document.querySelector('.station-list');
            stationList.style.display = stationList.style.display === 'none' ? 'block' : 'none';
        }

        function loadStationData(station) {
            const stationDataDiv = document.getElementById('stationData');
            stationDataDiv.style.display = 'block'; // Hiển thị phần dữ liệu trạm
            
            // Gửi yêu cầu AJAX để lấy dữ liệu cho trạm
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'A-G.php?station=' + station, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    stationDataDiv.innerHTML = xhr.responseText; // Chèn dữ liệu vào phần stationData
                } else {
                    console.error('Error loading station data: ' + xhr.status);
                }
            };
            xhr.send();
        }
    </script>
</body>
</html>
