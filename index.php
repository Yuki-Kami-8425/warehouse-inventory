<?php 

$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
); 
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$station = isset($_GET['station']) ? $_GET['station'] : 'dashboard';
$sql = '';
$params = null;

switch ($station) {
case 'all':
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID, PALLET_status FROM dbo.stored_warehouse";
    break;
case 'home':
    $sql = null; // Hoặc không cần khởi tạo $sql
    break;
case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G':
    $sql = "SELECT MAKH, TENSP, TENKH, LUONG_PALLET, RFID, NGAYCT, PALLET_status FROM dbo.stored_warehouse WHERE RFID LIKE ?";
    $params = array($station . '%');
    break;
default:
    $sql = null; // Một truy vấn mặc định
    break;
}

$stmt = sqlsrv_query($conn, $sql, $params ?? null); 
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

// Tính số lượng pallet (slots) cho mỗi khách hàng
$customerSlotCount = [];
foreach ($customers as $customerId => $rfids) {
    $customerSlotCount[$customerId] = count($rfids); // Mỗi khách hàng có số lượng slot (RFID)
}

// Sắp xếp số lượng slot giảm dần
arsort($customerSlotCount);

// Lấy 3 khách hàng nhiều nhất và một cột "Other" cho các khách hàng còn lại
$topCustomers = array_slice($customerSlotCount, 0, 3, true); // Lấy 3 khách hàng đầu tiên
$otherData = array_slice($customerSlotCount, 3); // Các khách hàng còn lại

// Tính tổng số slot cho các khách hàng còn lại (Other)
$otherSum = array_sum($otherData);

// Cập nhật dữ liệu cho biểu đồ
$customerLabels = array_keys($topCustomers);
$customerData = array_values($topCustomers);

// Nếu có dữ liệu "Other", thêm vào labels và dữ liệu
if ($otherSum > 0) {
    $customerLabels[] = 'Other';
    $customerData[] = $otherSum;
}

// Đảm bảo có đủ 4 cột (Nếu không đủ 3 khách hàng, thêm "Other" vào cuối)
if (count($customerLabels) < 4) {
    $customerLabels[] = 'Other';
    $customerData[] = $otherSum;
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - <?= $station === 'all' ? 'All Stations' : 'Station ' . $station ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        window.onload = function() { // Kiểm tra xem URL có chứa tham số station không
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('station')) {
                window.location.href = '?station=home'; // Nếu không có tham số, chuyển hướng đến Home
            }
        };
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #001F3F;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: auto;  /* Ensure body takes full height */
            margin: 0;  /* Remove default margin */
        }

        .overlay h2, .overlay p {
            text-align: center;
            margin-top: 20px; /* Khoảng cách từ trên xuống */
        }

        .overlay {
            position: relative; /* Đảm bảo không bị dính vào các phần tử khác */
            padding: 20px;
        }
                
        .main-content {
            flex-grow: 1; /* Take all available space */
            padding: 20px;
            transition: margin-left 0.3s ease;
            max-width: 100%;  /* Prevent overflow */
            z-index: 500;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 150px;
            background-color: #2c3e50; /* Màu nền thanh bên */
            padding-top: 60px;
            transition: width 0.3s; /* Hiệu ứng chuyển đổi khi thu gọn */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            z-index: 1000;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            position: relative;
        }

        .sidebar a,
        .sidebar button {
            display: flex;
            align-items: center; /* Canh giữa các biểu tượng và văn bản */
            padding: 10px;
            text-decoration: none;
        }

        .dropdown-container {
            padding-left: 20px; /* Thụt lề cho dropdown */
        }

        .dropdown-btn.active + .dropdown-container {
            display: block; /* Đảm bảo dropdown hiện khi button active */
        }

        .collapsed .link-text {
            display: none; /* Ẩn văn bản khi sidebar bị thu gọn */
        }

        .icon {
            width: 24px; /* Đặt kích thước cố định cho icon */
            height: 24px;
            margin-right: 10px; /* Khoảng cách giữa icon và văn bản */
        }

        .sidebar.collapsed {
            width: 60px; /* Kích thước sidebar khi thu gọn */
        }

        .sidebar.collapsed + .main-content {
            margin-left: 60px;  /* Adjust margin of main-content when collapsed */
        }

        .sidebar + .main-content {
            margin-left: 150px;  /* Initial width of the sidebar */
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
            width: 35%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .container table caption {
            font-size: 1.5em; /* Kích thước của tiêu đề bảng */
            font-weight: bold;
            margin-bottom: 10px;
        }

        th, td {
            border: 2px solid white;
            padding: 5px;
            text-align: center;
        }

        td.highlight {
            background-color: #32CD32;
        }

        .charts {
            display: flex; 
            justify-content: space-between; /* Evenly distribute space between charts */
             gap: 20px; 
        }

        .chart-container {
            flex: 1; /* Make both charts take equal space */
            display: flex;
            flex-direction: column;
            align-items: center; /* Align items (chart + caption) horizontally */
            justify-content: center;  
            width: 300px; 
            margin: 0 auto;
        }

        .charts-center {
            display: flex;
            flex: 1;
            justify-content: center; /* Centers the charts horizontally */
            align-items: center;          /* Căn giữa biểu đồ và caption theo chiều ngang */ 
            gap: 20px; /* Optional: space between the charts */
            margin: 0 auto; /* Center within its parent container */
        }

        #barChart {
            width: 400px !important;
            height: 350px !important; /* Adjust height as needed */
        }

        #pieChart {
            width: 350px !important;
            height: 350px !important;
        }

        #barChartCaption, #pieChartCaption {
            text-align: center;
            color: white;
            margin-top: 5px;
            margin-bottom: 0; /* Remove any extra margin */
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

        .sidebar a:hover, .dropdown-btn:hover {
            color: #32CD32; /* Màu chữ khi hover */
            background-color: rgba(255, 255, 255, 0.1); /* Nền khi hover */
        }

        .sidebar a:hover i, .dropdown-btn:hover i {
            color: #32CD32; /* Màu icon khi hover */
        }

        .sidebar a, .dropdown-btn {
            position: relative;
            border: none; /* Xóa viền */
            outline: none; /* Xóa outline khi nhấn */
            box-shadow: none; /* Xóa hiệu ứng bóng */
        }

        .sidebar a.active {
            color: #ADD8E6; /* Màu xanh lam nhạt */
            font-weight: bold; /* Thêm font-weight cho nổi bật */
        }

        .slide:first-child {
            display: block;
        }

        .sidebar a:hover::after, .dropdown-btn:hover::after {  /* Hiệu ứng tooltip */
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

        .sidebar a.active {
            color: #00aaff; /* Màu xanh lam tươi khi nút được chọn */
        }

        .sidebar a.selected {
            color: #ADD8E6; /* Màu xanh lam nhạt */
        }

        .dropdown-container {
            display: none; /* Ẩn menu theo mặc định */
        }

        .dropdown-container.show {
            display: block; /* Hiển thị menu khi có class 'show' */
        }

        .slide-title {
            font-size: 24px; /* Kích thước chữ cho tiêu đề */
            color: white; /* Màu chữ */
            margin-bottom: 10px; /* Khoảng cách giữa tiêu đề và hình ảnh */
        }

        .slide:hover {
            transform: scale(1.02); /* Phóng to một chút */
            transition: transform 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
            padding: 20px 0;
            text-align: center;
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
            width: 450px; /* Chiều rộng cố định */
            height: 250px; /* Chiều cao cố định */
            object-fit: fill; /* Kéo giãn ảnh để lấp đầy khung */
            position: relative;
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

        .tooltip {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            display: none;
            pointer-events: none;
            font-size: 14px;
            z-index: 9999;
            white-space: pre-line;
            max-width: 400px; /* Tăng chiều rộng tối đa */
            min-width: 200px; /* Tăng chiều rộng tối thiểu */
            word-wrap: break-word;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* Hiệu ứng đổ bóng */
        }

        .chartCaption {
            font-size: 20px; /* Thay đổi cỡ chữ theo ý bạn */
            font-weight: bold; /* Làm chữ in đậm */
            color: white; /* Đảm bảo chữ vẫn màu trắng */
            text-align: center;
            margin-top: 10px;
        }
        
        .highlight {
            position: relative;
            background-color: #32CD32; /* Màu nền ô highlight */
            cursor: pointer;
        }

        .highlight:hover::after {
            content: attr(data-tooltip); /* Lấy nội dung từ data-tooltip */
            position: absolute;
            top: 50%; /* Hiển thị ở giữa chiều dọc của ô */
            left: 110%; /* Cách ô một khoảng nhỏ */
            transform: translateY(-50%); /* Canh giữa theo chiều dọc */
            background: rgba(0, 0, 0, 0.8); /* Nền đen nhạt */
            color: #fff; /* Chữ màu trắng */
            padding: 10px 15px;
            border-radius: 5px; /* Bo tròn góc */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); /* Hiệu ứng bóng */
            white-space: pre-line; /* Hiển thị xuống dòng */
            z-index: 9999; /* Hiển thị trên cùng */
            font-size: 14px; /* Kích thước chữ */
            min-width: 180px; /* Chiều rộng tối thiểu */
            max-width: 250px; /* Chiều rộng tối đa */
            text-align: left;
            pointer-events: none; /* Không bị ảnh hưởng bởi chuột */
        }   

        .highlight:hover::before {
            content: ''; /* Mũi tên nhỏ chỉ vào ô */
            position: absolute;
            top: 50%; /* Ở giữa chiều dọc của ô */
            left: 100%; /* Nằm ngay bên cạnh tooltip */
            transform: translate(-50%, -50%);
            border-width: 5px;
            border-style: solid;
            border-color: transparent rgba(0, 0, 0, 0.8) transparent transparent; /* Màu nền đen nhạt cho mũi tên */
        }

        .feature {
            display: flex;                  /* Sử dụng flexbox để căn giữa các phần tử */
            flex-direction: column;         /* Sắp xếp các phần tử theo chiều dọc */
            align-items: center;            /* Căn giữa các phần tử theo chiều ngang */
            justify-content: center;        /* Căn giữa theo chiều dọc (trong trường hợp cần) */
            text-align: center;             /* Căn giữa nội dung chữ */
            padding: 20px;                  /* Thêm padding để tránh các phần tử quá sát nhau */
        }

        .feature img {
            width: 100%;                    /* Đặt ảnh chiếm 100% chiều rộng của container */
            max-width: 250px;               /* Giới hạn chiều rộng tối đa của ảnh */
            height: auto;                   /* Đảm bảo tỷ lệ ảnh không bị biến dạng */
            object-fit: contain;            /* Giữ nguyên tỷ lệ ảnh nếu cần */
            margin-bottom: 15px;            /* Khoảng cách giữa ảnh và tiêu đề */
        }

        .feature h3 {
            font-size: 20px;                /* Cỡ chữ cho tiêu đề */
            margin: 10px 0;                 /* Khoảng cách giữa tiêu đề và đoạn văn */
        }

        .feature p {
            font-size: 16px;                /* Cỡ chữ cho mô tả */
            line-height: 1.5;               /* Đảm bảo đoạn văn không bị chật */
        }

        .all-page {
            height: 100vh; /* Chiếm toàn bộ chiều cao cửa sổ trình duyệt */
            display: flex;
            justify-content: center; /* Căn giữa theo chiều ngang */
            align-items: center; /* Căn giữa theo chiều dọc */
            flex-direction: column; /* Sắp xếp các phần tử theo chiều dọc */
            margin: 0; /* Xóa bỏ margin mặc định */
        }

    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()"; >
        <i class="fas fa-bars"></i>
    </button>
    <ul>
        <li>
            <a href="?station=home" onclick="showPage('home'); closeDropdowns();" class="main-link" data-tooltip="Go to Home">
                <i class="fas fa-home"></i>
                <span class="link-text">Home</span>
            </a>
        </li>
        <li>
            <button class="dropdown-btn" onclick="toggleDropdown(event)" data-tooltip="See dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span class="link-text">Dashboard</span>
            </button>
            <ul class="dropdown-container" style="display: none;">
                <li>
                    <a href="?station=all" onclick="showPage('all'); closeDropdowns();" data-tooltip="See all data">
                        <i class="fas fa-th-list"></i>
                        <span class="link-text">All</span>
                    </a>
                </li>
                <li>
                    <a href="?station=A" onclick="showPage('A'); closeDropdowns();" data-tooltip="Go to station A">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station A</span>
                    </a>
                </li>
                <li>
                    <a href="?station=B" onclick="showPage('B'); closeDropdowns();" data-tooltip="Go to station B">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station B</span>
                    </a>
                </li>
                <li>
                    <a href="?station=C" onclick="showPage('C'); closeDropdowns();" data-tooltip="Go to station C">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station C</span>
                    </a>
                </li>
                <li>
                    <a href="?station=D" onclick="showPage('D'); closeDropdowns();" data-tooltip="Go to station D">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station D</span>
                    </a>
                </li>
                <li>
                    <a href="?station=E" onclick="showPage('E'); closeDropdowns();" data-tooltip="Go to station E">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station E</span>
                    </a>
                </li>
                <li>
                    <a href="?station=F" onclick="showPage('F'); closeDropdowns();" data-tooltip="Go to station F">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station F</span>
                    </a>
                </li>
                <li>    
                    <a href="?station=G" onclick="showPage('G'); closeDropdowns();" data-tooltip="Go to station G">
                        <i class="fas fa-industry"></i>
                        <span class="link-text">Station G</span>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>

<div class="main-content" id="main-content">
<?php
    switch ($station) {
        case 'home': ?>
            <div id="home" class="home page">
                <div class="overlay">
                    <h2>Welcome to EIU Smart Warehouse</h2>
                    <p>Efficient. Connected. Automated.</p>
                    <a href="#services" class="cta-button">Explore More</a>
                </div>

                <section id="overview">
                    <h2>What is Smart Warehouse?</h2>
                    <p>
                        A Smart Warehouse is an advanced storage and logistics facility that leverages modern technologies to improve efficiency, accuracy, and flexibility in warehouse operations. Unlike traditional warehouses, smart warehouses incorporate automation, real-time data tracking, and intelligent systems to streamline processes such as inventory management, order fulfillment, and shipping.
                    </p>
                </section>

                <div class="slideshow-container">
                    <div class="slide">
                        <h2 class="slide-title">Revolutionizing Warehouse Operations with Smart Technology</h2>
                        <img class="slide-image" src="Picture1.png" alt="Slide 1">
                    </div>
                    <div class="slide">
                        <h2 class="slide-title">Real-Time Inventory Management for Efficiency</h2>
                        <img class="slide-image" src="Picture2.png" alt="Slide 2">
                    </div>
                    <div class="slide">
                        <h2 class="slide-title">Optimized Logistics with AI-Powered Solutions</h2>
                        <img class="slide-image" src="Picture3.png" alt="Slide 3">
                    </div>
                    <div class="dots">
                        <span class="dot" onclick="showSlide(1)"></span>
                        <span class="dot" onclick="showSlide(2)"></span>
                        <span class="dot" onclick="showSlide(3)"></span>
                    </div>
                </div>

                <section id="services">
                    <h2>Our Key Features</h2>
                    <div class="features">
                        <div class="feature">
                            <img src="pic4.png" alt="Feature 1">
                            <h3>Real-time Tracking</h3>
                            <p>Monitor your inventory with precision using IoT sensors.</p>
                        </div>
                        <div class="feature">
                            <img src="pic5.png" alt="Feature 2">
                            <h3>Automation</h3>
                            <p>Streamline operations with AI-driven robotics.</p>
                        </div>
                        <div class="feature">
                            <img src="pic6.png" alt="Feature 3">
                            <h3>Cloud Integration</h3>
                            <p>Sync data seamlessly with cloud platforms.</p>
                        </div>
                    </div>
                </section>

                <footer>
                    <p>© 2024 Smart Warehouse Solutions. All rights reserved.</p>
                </footer>
            </div>

            <script>
                let slideIndex = 0;
                showSlides();
                function showSlides() {
                    let slides = document.querySelectorAll('.slide');
                    let dots = document.querySelectorAll('.dot');
                    slides.forEach((slide, index) => {
                        slide.style.display = 'none'; // Ẩn tất cả các slide
                        dots[index].classList.remove("active"); // Xóa lớp active khỏi tất cả các dấu chấm
                    });
                    slideIndex++;
                    if (slideIndex > slides.length) {
                        slideIndex = 1; // Reset lại chỉ số nếu vượt quá số slide
                    }
                    slides[slideIndex - 1].style.display = 'block'; // Hiện slide hiện tại
                    dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm hiện tại
                    setTimeout(showSlides, 5000); // Thay đổi slide mỗi 5 giây
                }
                function showSlide(index) {
                    slideIndex = index; // Đặt chỉ số slide hiện tại
                    let slides = document.querySelectorAll('.slide');
                    let dots = document.querySelectorAll('.dot');
                    slides.forEach(slide => slide.style.display = 'none'); // Ẩn tất cả các slide
                    dots.forEach(dot => dot.classList.remove("active")); // Xóa lớp active khỏi tất cả các dấu chấm
                    slides[slideIndex - 1].style.display = 'block'; // Hiện slide tương ứng
                    dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm tương ứng
                }
            </script>
        <?php break; default: ?>

        <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>
        <!-- Bảng Left Rack và Right Rack chỉ hiển thị khi chọn trạm A-G -->
        <div class="container">
    <table> 
        <!-- Bảng Left Rack -->
        <caption>Left Rack</caption>
        <?php for ($row = 7; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 14; $col++): ?>
                    <?php 
                        $index = ($row - 1) * 14 + $col; 
                        $rfid = $station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT); // Tạo RFID hiện tại

                        // Kiểm tra xem RFID có trong danh sách highlight không
                        $info = null;
                        if (in_array($rfid, $highlighted)) {
                            // Tìm dữ liệu chi tiết cho RFID
                            $filtered = array_filter($data, fn($item) => trim($item['RFID']) === $rfid);
                            $info = reset($filtered); // Lấy dòng dữ liệu đầu tiên (nếu có)

                            $isStored = $info && $info['PALLET_status'] === 'stored';
                        }
                    ?>
                   <td 
                        class="<?= $info ? 'highlight' : '' ?>" 
                        data-tooltip="<?= $info ? 
                            $info['MAKH'] . "\n" .
                            $info['TENSP'] . "\n" .
                            $info['TENKH'] . "\n" .
                            (isset($info['NGAYCT']) && $info['NGAYCT'] instanceof DateTime 
                                ? $info['NGAYCT']->format('Y-m-d') 
                                : 'Undefined') 
                            : '' ?>"
                    >
                        <?= $rfid ?>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    <table> 
        <!-- Bảng Right Rack -->
        <caption>Right Rack</caption>
        <?php for ($row = 7; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 14; $col++): ?>
                    <?php 
                        $index = ($row - 1) * 14 + $col; 
                        $rfid = $station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT); // Tạo RFID hiện tại

                        // Kiểm tra xem RFID có trong danh sách highlight không
                        $info = null;
                        if (in_array($rfid, $highlighted)) {
                            // Tìm dữ liệu chi tiết cho RFID
                            $filtered = array_filter($data, fn($item) => trim($item['RFID']) === $rfid);
                            $info = reset($filtered); // Lấy dòng dữ liệu đầu tiên (nếu có)

                            $isStored = $info && $info['PALLET_status'] === 'stored';
                        }
                    ?>
                    <td 
                        class="<?= $info ? 'highlight' : '' ?>" 
                        data-tooltip="<?= $info ? 
                            $info['MAKH'] . "\n" .
                            $info['TENSP'] . "\n" .
                            $info['TENKH'] . "\n" .
                            (isset($info['NGAYCT']) && $info['NGAYCT'] instanceof DateTime 
                                ? $info['NGAYCT']->format('Y-m-d') 
                                : 'Undefined') 
                            : '' ?>"
                    >
                        <?= $rfid ?>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    </div>
        <!-- Biểu đồ -->
        <div class="charts">
                <div class="chart-container">
                    <!-- Biểu đồ cột -->
                    <canvas id="barChart"></canvas>
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px;">
                        <?= $station === 'all' ? 'Total Customers Using the Warehouse: ' : 'Total Customers at Station ' . $station ?>
                    </div>
                </div>
                <div class="chart-container"> <!-- Biểu đồ tròn -->
                    <canvas id="pieChart"></canvas>
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px;">
                        <?= $station === 'all' ? 'Distribution of Slots in All Stations' : 'Distribution of Slots in Station ' . $station ?>
                    </div>
                </div>
        </div>

        <?php break; case 'all': ?>
            <div class="charts charts-center">
                <!-- Biểu đồ -->
                <div class="chart-container">
                    <!-- Biểu đồ cột -->
                    <canvas id="barChart"></canvas>
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px;">
                        Percentage of Total Slots (%)
                    </div>
                </div>
                <div class="chart-container"> <!-- Biểu đồ tròn -->
                    <canvas id="pieChart"></canvas>
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px;">
                        Distribution of Storage Slots 
                    </div>
                </div>
            </div>
        <?php break; } ?>
    </div>
    
    <script>
    // Dữ liệu biểu đồ
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers); // Mã khách hàng
    const customerData = customerLabels.map(key => customers[key].length); // Đếm số lượng RFID cho mỗi khách hàng

    // Tổng số ô (slots) cho tất cả trạm (ví dụ, nếu là 'all' thì 7 trạm, nếu trạm cụ thể thì 1 trạm)
    const totalSlots = 196 * (<?= $station === 'all' ? 7 : 1 ?>); // Tổng số ô (slots)
    const filledSlots = <?= count($highlighted) ?>; // Số ô đã sử dụng

    // Tính phần trăm ô đã sử dụng
    const filledPercentage = ((filledSlots / totalSlots) * 100).toFixed(2);
    const percentageLabelPlugin = {
        id: 'percentageLabel',
        afterDatasetsDraw(chart) {
            const { ctx, scales: { x, y } } = chart;
            const dataset = chart.data.datasets[0].data;
            
            dataset.forEach((value, index) => {
                const percentage = ((value / totalSlots) * 100).toFixed(2); // Tính tỷ lệ phần trăm
                const xPos = x.getPixelForValue(index);
                const yPos = y.getPixelForValue(value);
                
                ctx.fillStyle = 'white';
                ctx.textAlign = 'center';
                ctx.font = 'bold 20px Arial';
                ctx.fillText(`${percentage}%`, xPos, yPos - 10); // Hiển thị phần trăm
            });
        }
    };

    // Khởi tạo biểu đồ
    var ctxBar = document.getElementById('barChart').getContext('2d');
    var barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($customerLabels); ?>, // Các nhãn khách hàng
            datasets: [{
                label: 'Slots per Customer',
                data: <?php echo json_encode($customerData); ?>, // Dữ liệu số lượng slot
                backgroundColor: 'rgba(54, 162, 235, 1)', // Màu cột
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false // Ẩn legend
                },
                tooltip: {
                    bodyFont: {
                        size: 20
                    },
                    titleFont: {
                        size: 20
                    },
                    padding: 10,
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    displayColors: false,
                    callbacks: {
                        label: function(tooltipItem) {
                            const customerId = tooltipItem.label;
                            const slotCount = tooltipItem.raw;
                            return `${customerId}: ${slotCount} slots`; 
                        }
                    },
                    /* // Điều chỉnh vị trí của tooltip để không bị lệch
                    position: 'average', // Đặt tooltip ở giữa các cột
                    xAlign: 'center', // Đảm bảo tooltip canh giữa theo trục X */
                }
            },
            scales: {
                y: {
                    min: 0, // Thang đo bắt đầu từ 0
                    max: 100, // Thang đo tối đa là 100
                    ticks: {
                        color: 'white', // Màu chữ trục Y
                        font: {
                            size: 20 // Đặt kích thước chữ trục Y thành 20px
                        },
                        stepSize: 10 // Chia thang đo theo bước 10%
                    }
                },
                x: {
                    grid: {
                        display: false // Không hiển thị vạch dọc
                    },
                    ticks: {
                        color: 'white', // Màu chữ trục X
                        font: {
                            size: 20 // Đặt kích thước chữ trục X thành 20px
                        }
                    }
                }
            }
        },
        plugins: [percentageLabelPlugin] // Thêm plugin hiển thị phần trăm
    });

    // Biểu đồ tròn
    var ctxPie = document.getElementById('pieChart').getContext('2d');
    var pieChart = new Chart(ctxPie, {
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
                },
                tooltip: {
                    callbacks: {
                        label: function(tooltipItem) {
                            const data = tooltipItem.dataset.data;
                            const currentValue = data[tooltipItem.dataIndex];
                            const percentage = ((currentValue / totalSlots) * 100).toFixed(2); // Tính phần trăm
                            return tooltipItem.label + ': ' + percentage + '%'; // Hiển thị phần trăm trong tooltip
                        }
                    }
                }
            }
        }
    });

    function toggleDropdown(event) {
        event.stopPropagation();
        closeDropdowns(); // Đảm bảo các dropdown khác đều đóng
        const dropdown = event.currentTarget.nextElementSibling;
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }

    function closeDropdowns() {
        const allDropdowns = document.querySelectorAll('.dropdown-container');
        allDropdowns.forEach(d => {
            d.style.display = 'none';
        });
    }

    function showPage(page) {
        console.log(`Loading page: ${page}`);
        closeDropdowns();  // Đảm bảo tất cả dropdown đều đóng lại khi đổi trang
    }

    document.addEventListener("DOMContentLoaded", function () {
        const urlParams = new URLSearchParams(window.location.search);
        const station = urlParams.get('station');
        const links = document.querySelectorAll('.sidebar a');
        links.forEach(link => {
            const href = link.getAttribute('href');
            if (href && href.includes(`station=${station}`)) {
                link.classList.add('active');
            }
        });
    });
    
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

        updateFooterPosition(); // Cập nhật vị trí của footer sau khi thay đổi thanh công cụ
    }

    function updateFooterPosition() {
        const sidebar = document.getElementById('sidebar');
        const footer = document.getElementById('datetime');

        // Calculate the sidebar width (collapsed or expanded)
        const sidebarWidth = sidebar.offsetWidth;

        // Dynamically position the footer in the center of the page
        footer.style.left = `calc(50% - ${sidebarWidth / 2}px)`;
        footer.style.transform = 'translateX(-50%)';  // Center footer relative to the page
    }

    // Call the function on sidebar toggle to update the footer's position
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

        updateFooterPosition();
    }

    document.querySelectorAll('.highlight').forEach(cell => {
        cell.addEventListener('mouseover', function() {
            const tooltip = document.createElement('div');
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.className = 'tooltip';
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.left = `${rect.left + window.scrollX}px`;
            tooltip.style.top = `${rect.top - tooltip.offsetHeight + window.scrollY}px`;

            this.addEventListener('mouseout', () => {
                tooltip.remove();
            });
        });
    });

</script>