<?php 
// Thông tin kết nối cơ sở dữ liệu Azure SQL
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

// Kết nối đến cơ sở dữ liệu
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Kiểm tra kết nối
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Lấy trạm từ query string, mặc định là 'home'
$station = isset($_GET['station']) ? $_GET['station'] : 'home';

// Lấy dữ liệu từ bảng cho tất cả các trạm nếu là 'all', ngược lại lấy theo trạm
if ($station === 'all') {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse";
} elseif ($station === 'list') {
    // Truy vấn tất cả dữ liệu từ bảng nếu là List
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse";
    $params = null; // Không cần tham số
} elseif (in_array($station, ['A', 'B', 'C', 'D', 'E', 'F', 'G'])) {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE ?";
    $params = array($station . '%');
}

$stmt = sqlsrv_query($conn, $sql, $params ?? null); 

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu
$data = [];
$customers = [];
$highlighted = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
    $customers[$row['MAKH']][] = $row['RFID']; // Lưu danh sách RFID cho mỗi khách hàng
    $highlighted[] = trim($row['RFID']); // Dùng trim để loại bỏ khoảng trắng
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - <?= $station === 'all' ? 'All Stations' : 'Station ' . $station ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #001F3F; /* Xanh đậm */
            color: white; /* Màu chữ trắng */
            display: flex;
        }
        /* Sidebar styling */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            background-color: #2c3e50; /* Màu nền thanh bên */
            padding-top: 60px;
            transition: width 0.3s; /* Hiệu ứng chuyển đổi khi thu gọn */
        }
        .sidebar.collapsed {
            width: 60px; /* Kích thước sidebar khi thu gọn */
        }
        .sidebar.collapsed .link-text {
            display: none; /* Ẩn chữ khi sidebar thu gọn */
        }
        .sidebar a i {
            margin-right: 0; /* Bỏ khoảng cách giữa icon và chữ */
        }
        .sidebar.collapsed a i {
            margin-right: 0; /* Đảm bảo không có khoảng cách khi chỉ có icon */
        }
        .sidebar a, .dropdown-btn {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: flex;
            align-items: center;
            background-color: transparent;
        }
        .sidebar a i, .dropdown-btn i {
            margin-right: 10px; /* Khoảng cách giữa icon và chữ */
        }
        .dropdown-container {
            display: none;
            background-color: #262626;
        }
        .dropdown-container a {
            padding-left: 30px;
        }
        .dropdown-container.show {
            display: block; /* Hiển thị dropdown khi có class 'show' */
        }
        /* Main content styling */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: 100%;
            transition: margin-left 0.3s; /* Hiệu ứng chuyển đổi khi sidebar thu gọn */
        }
        .main-content.collapsed {
            margin-left: 60px; /* Kích thước margin khi sidebar thu gọn */
        }
        h2 {
            text-align: center;
        }
        .container {
            display: flex; 
            justify-content: space-around; 
            margin: 20px;
        }
        table {
            width: 30%;
            border-collapse: collapse;
            font-size: 8px;
        }
        th, td {
            border: 2px solid white;
            padding: 5px;
            text-align: center;
        }
        td.highlight {
            background-color: #32CD32;
        }
        .chart-container {
            width: 40%; 
            margin: 20px;
        }
        .charts {
            display: flex; 
            justify-content: space-around; 
        }
        .toggle-btn {
            position: absolute;
            top: 15px;
            left: 10px;
            font-size: 24px; /* Điều chỉnh kích thước của biểu tượng */
            color: white; /* Màu của biểu tượng */
            background: none;
            border: none;
            cursor: pointer;
        }
        .toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1); /* Màu nền khi hover */
            border-radius: 5px; /* Bo góc một chút */
            transform: scale(1.1); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }
        .home-container {
            display: flex; /* Sử dụng flexbox để căn chỉnh */
            flex-direction: column; /* Đặt chiều dọc */
            align-items: center; /* Căn giữa */
        }
        .sidebar a {
            display: block;
            color: #fff; /* Màu chữ */
            text-decoration: none;
            padding: 10px;
            transition: background-color 0.3s ease;
            background-color: none;
        }
        /* Hiệu ứng hover cho toàn bộ chữ và icon trong sidebar */
        .sidebar a:hover, .dropdown-btn:hover {
            color: #32CD32; /* Màu chữ khi hover */
            background-color: rgba(255, 255, 255, 0.1); /* Nền khi hover */
        }
        /* Đảm bảo icon cũng thay đổi màu sắc khi hover */
        .sidebar a:hover i, .dropdown-btn:hover i {
            color: #32CD32; /* Màu icon khi hover */
        }
        .sidebar a, .dropdown-btn {
            position: relative;
            border: none; /* Xóa viền */
            outline: none; /* Xóa outline khi nhấn */
            box-shadow: none; /* Xóa hiệu ứng bóng */
        }
        .sidebar a.selected {
            color: #00BFFF; /* Màu xanh lam khi được chọn */
        }
        /* Hiệu ứng tooltip */
        .sidebar a:hover::after, .dropdown-btn:hover::after {
            content: attr(data-tooltip); /* Lấy nội dung từ thuộc tính data-tooltip */
            position: absolute;
            left: 100%; /* Hiển thị tooltip bên phải của phần tử */
            top: 50%;
            transform: translateY(-50%);
            background-color: #000; /* Màu nền tooltip */
            color: #fff; /* Màu chữ tooltip */
            padding: 5px 10px;
            border-radius: 5px;
            white-space: nowrap;
            z-index: 1;
            opacity: 0.8; /* Độ mờ của tooltip */
            margin-left: 10px; /* Khoảng cách giữa tooltip và phần tử */
        }
        .sidebar a.dashboard {
            background-color: transparent; /* Đảm bảo nền của nút Dashboard trong suốt */
        }
        .sidebar a.active, .dropdown-btn.active {
            color: #00aaff; /* Màu xanh lam tươi khi nút được chọn */
        }
        .slide-title {
            font-size: 24px; /* Kích thước chữ cho tiêu đề */
            color: white; /* Màu chữ */
            margin-bottom: 10px; /* Khoảng cách giữa tiêu đề và hình ảnh */
        }
        .slide {
            display: none; /* Ẩn tất cả các slide mặc định */
            position: relative; /* Để có thể căn chỉnh các thành phần bên trong */
        }
        .slide img {
            width: 650px; /* Chiều rộng cố định */
            height: 350px; /* Chiều cao cố định */
            object-fit: fill; /* Kéo giãn ảnh để lấp đầy khung */
        }
        .dots {
            position: relative; /* Để căn giữa dấu chấm */
            text-align: center; /* Căn giữa dấu chấm */
            margin-top: 10px; /* Khoảng cách giữa chữ và dấu chấm */
        }
        .dot {
            height: 10px; /* Kích thước dấu chấm */
            width: 10px; /* Kích thước dấu chấm */
            margin: 0 5px; /* Khoảng cách giữa các dấu chấm */
            background-color: white; /* Màu trắng */
            border-radius: 50%; /* Đường viền tròn */
            display: inline-block; /* Hiển thị thành dòng ngang */
            cursor: pointer; /* Con trỏ khi hover vào */
            transition: all 0.3s; /* Hiệu ứng chuyển tiếp */
        }
        .dot.active {
            height: 15px; /* Kích thước lớn hơn khi được chọn */
            width: 15px; /* Kích thước lớn hơn khi được chọn */
            background-color: #00BFFF; /* Màu xanh lam khi được chọn */
        }
        .dot:hover {
            background-color: #00BFFF; /* Màu nền khi hover */
            transform: scale(1.2); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <li>
        <a href="?station=home" onclick="showPage('home');" class="main-link" data-tooltip="Go to Home">
            <i class="fas fa-home"></i>
            <span class="link-text">Home</span>
        </a>
    </li>

    <button class="dropdown-btn" onclick="toggleDropdown()">
        <i class="fas fa-tachometer-alt"></i>
        <span class="link-text">Dashboard</span>
    </button>
    <div class="dropdown-container">
        <a href="?station=all" onclick="showPage('all');">
            <i class="fas fa-th-list"></i>
            <span class="link-text">All</span>
        </a>
        <a href="?station=A" onclick="showPage('A');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station A</span>
        </a>
        <a href="?station=B" onclick="showPage('B');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station B</span>
        </a>
        <a href="?station=C" onclick="showPage('C');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station C</span>
        </a>
        <a href="?station=D" onclick="showPage('D');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station D</span>
        </a>
        <a href="?station=E" onclick="showPage('E');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station E</span>
        </a>
        <a href="?station=F" onclick="showPage('F');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station F</span>
        </a>
        <a href="?station=G" onclick="showPage('G');">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station G</span>
        </a>
    </div>

    <a href="?station=list" onclick="showPage('list');" data-tooltip="Watch List">
        <i class="fas fa-list"></i>
        <span class="link-text">List</span>
    </a>
</div>

<div class="main-content" id="main-content">
    <?php if ($station === 'home'): ?>
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

    <?php elseif ($station !== 'all'): ?>
        <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>
        <!-- Bảng Left Rack và Right Rack chỉ hiển thị khi chọn trạm A-G -->
        <div class="container">
            <!-- Bảng Left Rack -->
            <table>
                <caption>Left Rack</caption>
                <?php for ($row = 7; $row >= 1; $row--): ?>
                    <tr>
                        <?php for ($col = 1; $col <= 14; $col++): ?>
                            <?php $index = ($row - 1) * 14 + $col; ?>
                            <td class="<?= in_array($station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">
                                <?= $station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT) ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </table>

            <!-- Bảng Right Rack -->
            <table>
                <caption>Right Rack</caption>
                <?php for ($row = 7; $row >= 1; $row--): ?>
                    <tr>
                        <?php for ($col = 1; $col <= 14; $col++): ?>
                            <?php $index = ($row - 1) * 14 + $col; ?>
                            <td class="<?= in_array($station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">
                                <?= $station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT) ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </table>
        </div>
        
        <!-- Biểu đồ -->
        <div class="charts">
                <!-- Biểu đồ cột -->
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>

                <!-- Biểu đồ tròn -->
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
         </div>

        <?php elseif ($station === 'all'): ?>
            <!-- Biểu đồ -->
            <div class="charts">
                <!-- Biểu đồ cột -->
                <div class="chart-container">
                    <canvas id="barChart"></canvas>
                </div>

                <!-- Biểu đồ tròn -->
                <div class="chart-container">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>

        <?php elseif ($station === 'list'): ?>
            <h2>Danh Sách Khách Hàng</h2>
            <table>
                <thead>
                    <tr>
                        <th>Mã Khách</th>
                        <th>Tên Khách</th>
                        <th>Số Pallet</th>
                        <th>RFID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['MAKH']) ?></td>
                            <td><?= htmlspecialchars($row['TENKH']) ?></td>
                            <td><?= htmlspecialchars($row['LUONG_PALLET']) ?></td>
                            <td><?= htmlspecialchars($row['RFID']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<script>
    let slideIndex = 1;
    showSlides(slideIndex);

    function showSlides(n) {
        let slides = document.getElementsByClassName("slide");
        let dots = document.getElementsByClassName("dot");
        if (n > slides.length) { slideIndex = 1 }
        if (n < 1) { slideIndex = slides.length }
        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (let i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
    }

    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    setInterval(function() {
        showSlides(slideIndex += 1);
    }, 10000); // Thay đổi slide mỗi 10 giây

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
    }

    function toggleDropdown() {
        const dropdown = document.querySelector('.dropdown-container');
        dropdown.classList.toggle('show');
    }

    function showPage(page) {
        // Function to show the selected page
        const pages = document.querySelectorAll('.page');
        pages.forEach(p => p.style.display = 'none');
        document.getElementById(page).style.display = 'block';
    }

    // Set default page
    document.addEventListener('DOMContentLoaded', function() {
        showPage('home'); // Load home page by default
    });
    // Dữ liệu biểu đồ
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers); // Mã khách hàng
    const customerData = customerLabels.map(key => customers[key].length); // Đếm số lượng RFID cho mỗi khách hàng
    const totalSlots = 196 * (<?= $station === 'all' ? 7 : 1 ?>); // Tổng số ô, nếu là 'all' thì 7 trạm, nếu trạm cụ thể thì 1 trạm
    const filledSlots = <?= count($highlighted) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [{
                label: 'Used Slots',
                data: customerData,
                backgroundColor: 'rgba(54, 162, 235, 1)',
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Used Slots',
                        color: 'white'
                    },
                    grid: {
                        color: 'white'
                    },
                    ticks: {
                        color: 'white',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        color: 'white'
                    },
                    ticks: {
                        color: 'white'
                    }
                }
            }
        }
    });

    // Biểu đồ tròn
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Used', 'Remaining'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            }
        }
    });

    // Dropdown logic
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.classList.toggle('active');
        const dropdownContent = this.nextElementSibling;
        if (dropdownContent.style.display === 'block') {
            dropdownContent.style.display = 'none';
        } else {
            dropdownContent.style.display = 'block';
        }
    });

    function toggleSidebar() {
    let sidebar = document.getElementById('sidebar');
    let content = document.querySelector('.main-content'); // Đảm bảo có phần tử main-content nếu cần

    // Toggle lớp 'collapsed' cho sidebar
    sidebar.classList.toggle('collapsed');
    if (content) {
        content.classList.toggle('collapsed'); // Chỉ thêm nếu bạn muốn nội dung cũng thay đổi vị trí
    }
    
    // Cập nhật footer nếu cần thiết
    if (typeof updateFooterPosition === 'function') {
        updateFooterPosition();
    }}   

    function toggleDropdown() {
    const dropdown = document.querySelector('.dropdown-container');
    dropdown.classList.toggle('show'); // Toggle class 'show' để hiện/ẩn dropdown
    }

    document.querySelectorAll('.dropdown-container a').forEach(item => {
        item.addEventListener('click', function() {
            const dropdown = document.querySelector('.dropdown-container');
            dropdown.classList.remove('show'); // Ẩn dropdown khi chọn một trạm
        });
    });

    function showPage(pageId) {
        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });
        document.getElementById(pageId).classList.add('active');
    }

    window.onload = function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (!urlParams.has('station') || urlParams.get('station') === 'home') {
        showPage('home');
    } else if (urlParams.get('station') === 'list') {
        showPage('list');
    } else {
        showPage('data');
    }
};
</script>