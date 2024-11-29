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
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse";
    break;
case 'home':
    $sql = null; // Hoặc không cần khởi tạo $sql
    break;
case 'A': case 'B': case 'C': case 'D': case 'E': case 'F': case 'G':
    $sql = "SELECT MAKH, TENSP, TENKH, LUONG_PALLET, RFID, NGAYCT FROM dbo.stored_warehouse WHERE RFID LIKE ?";
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
            height: 100%;  /* Thay vì 100vh, sử dụng 100% chiều cao của phần tử cha */
            margin: 0;  /* Remove default margin */
            overflow: hidden;  /* Prevent body from scrolling */
        }

        .main-content {
            flex-grow: 1; /* Take all available space */
            padding: 2%; /* Thay vì padding 20px, dùng padding theo tỷ lệ phần trăm */
            transition: margin-left 0.3s ease;
            max-width: 100%;  /* Prevent overflow */
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%; /* Chiều cao toàn màn hình */
            width: 15%; /* Chiều rộng là 15% của chiều ngang màn hình */
            background-color: #2c3e50; /* Màu nền thanh bên */
            padding-top: 5%; /* Khoảng cách phía trên là 5% của chiều cao */
            transition: width 0.3s; /* Hiệu ứng chuyển đổi khi thu gọn */
            display: flex;
            flex-direction: column;
            align-items: flex-start;
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
            padding: 2%; /* Chuyển padding sang tỷ lệ phần trăm */
            text-decoration: none;
        }

        .dropdown-container {
            padding-left: 5%; /* Chuyển padding sang tỷ lệ phần trăm */
        }

        .dropdown-btn.active + .dropdown-container {
            display: block; /* Đảm bảo dropdown hiện khi button active */
        }

        .collapsed .link-text {
            display: none; /* Ẩn văn bản khi sidebar bị thu gọn */
        }

        .icon {
            width: 5%; /* Đặt kích thước icon theo tỷ lệ phần trăm */
            height: 5%;
            margin-right: 3%; /* Khoảng cách giữa icon và văn bản */
        }

        .sidebar.collapsed {
            width: 15%; /* Kích thước sidebar khi thu gọn */
        }

        .sidebar.collapsed + .main-content {
            margin-left: 15%;  /* Adjust margin of main-content when collapsed */
        }

        .sidebar + .main-content {
            margin-left: 40%;  /* Initial width of the sidebar */
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
            padding: 2% 3%; /* Chuyển padding sang tỷ lệ phần trăm */
            text-decoration: none;
            font-size: 2vw; /* Đặt kích thước chữ theo tỷ lệ phần trăm viewport */
            color: white;
            display: flex;
            align-items: center;
            background-color: transparent;
        }

        .sidebar a i, .dropdown-btn i {
            margin-right: 3%; /* Khoảng cách giữa icon và chữ */
        }

        .dropdown-container {
            display: none;
            background-color: #262626;
        }

        .dropdown-container a {
            padding-left: 7%; /* Thụt lề cho các phần tử trong dropdown */
        }

        .dropdown-container.show {
            display: block; /* Hiển thị dropdown khi có class 'show' */
        }

        .main-content {
            margin-left: 60%; /* Margin chính khi sidebar không thu gọn */
            padding: 2%; /* Chuyển padding sang tỷ lệ phần trăm */
            width: 100%;
            transition: margin-left 0.3s; /* Hiệu ứng chuyển đổi khi sidebar thu gọn */
        }

        .main-content.collapsed {
            margin-left: 15%; /* Kích thước margin khi sidebar thu gọn */
        }

        h2 {
            text-align: center;
        }

        .container {
            display: flex; 
            justify-content: space-around; 
            margin: 2%; /* Chuyển margin sang tỷ lệ phần trăm */
        }

        table {
            width: 35%; /* Chiều rộng bảng ở dạng phần trăm */
            border-collapse: collapse;
            font-size: 1.2vw; /* Font-size theo tỷ lệ phần trăm của viewport */
        }

        .container table caption {
            font-size: 2vw; /* Kích thước của tiêu đề bảng */
            font-weight: bold;
            margin-bottom: 1%; /* Khoảng cách giữa tiêu đề bảng và nội dung */
        }

        th, td {
            border: 0.2vw solid white; /* Đặt viền bảng theo tỷ lệ phần trăm */
            padding: 1%; /* Padding cho các ô trong bảng */
            text-align: center;
        }

        td.highlight {
            background-color: #32CD32;
        }

        .charts {
            display: flex; 
            justify-content: space-between; /* Evenly distribute space between charts */
            gap: 3%; /* Khoảng cách giữa các chart */
        }

        .chart-container {
            flex: 1; /* Chiếm không gian bằng nhau */
            display: flex;
            flex-direction: column;
            align-items: center; /* Căn giữa */
            width: 30%; /* Điều chỉnh theo tỷ lệ phần trăm */
            margin: 0 auto;
        }

        .charts-center {
            display: flex;
            flex: 1;
            justify-content: center; /* Căn giữa các biểu đồ */
            gap: 3%; /* Khoảng cách giữa các biểu đồ */
            margin: 0 auto;
        }

        #barChart {
            width: 20%; /* Chiều rộng theo tỷ lệ phần trăm */
            height: 18%; /* Chiều cao theo tỷ lệ viewport */
        }

        #pieChart {
            width: 20%; /* Chiều rộng theo tỷ lệ phần trăm */
            height: 20%;
        }

        #barChartCaption, #pieChartCaption {
            text-align: center;
            color: white;
            margin-top: 1%; /* Khoảng cách trên */
            margin-bottom: 0;
        }

        .toggle-btn {
            position: absolute;
            top: 3%;
            left: 2%;
            font-size: 3vw; /* Kích thước biểu tượng theo tỷ lệ viewport */
            color: white;
            background: none;
            border: none;
            cursor: pointer;
        }

        .toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
            transform: scale(1.1);
            transition: all 0.3s;
        }

        .home-container {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar a {
            display: block;
            color: #fff;
            text-decoration: none;
            padding: 2%;
            transition: background-color 0.3s ease;
            background-color: none;
        }

        .sidebar a:hover, .dropdown-btn:hover {
            color: #32CD32;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar a:hover i, .dropdown-btn:hover i {
            color: #32CD32;
        }

        .sidebar a.active {
            color: #ADD8E6;
            font-weight: bold;
        }

        .sidebar a:hover::after, .dropdown-btn:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background-color: #000;
            color: #fff;
            padding: 1% 2%;
            border-radius: 5px;
            white-space: nowrap;
            z-index: 1;
            opacity: 0.8;
            margin-left: 1%;
        }

        .slide-title {
            font-size: 3vw;
            color: white;
            margin-bottom: 2%;
        }

        .slide:hover {
            transform: scale(1.02);
            transition: transform 0.3s;
        }

        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
            padding: 2% 0;
            text-align: center;
        }

        .slide {
            display: none;
            position: relative;
        }

        .slide img {
            width: 70%; /* Chiều rộng ảnh theo tỷ lệ phần trăm */
            height: auto; /* Tự động điều chỉnh chiều cao */
            object-fit: fill;
        }

        .dots {
            position: relative;
            text-align: center;
            margin-top: 2%;
        }

        .dot {
            height: 1.5vw;
            width: 1.5vw;
            margin: 0 1%;
            background-color: white;
            border-radius: 50%;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active {
            height: 2vw;
            width: 2vw;
            background-color: #00BFFF;
        }

        .dot:hover {
            background-color: #00BFFF;
            transform: scale(1.2);
            transition: all 0.3s;
        }

        .tooltip {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2% 3%;
            border-radius: 8px;
            display: none;
            pointer-events: none;
            font-size: 1.5vw;
            z-index: 9999;
            max-width: 40%; /* Tăng chiều rộng tối đa */
            min-width: 20%; /* Tăng chiều rộng tối thiểu */
            word-wrap: break-word;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
        }

        .chartCaption {
            font-size: 2vw;
            font-weight: bold;
            color: white;
            text-align: center;
            margin-top: 2%;
        }

        .highlight {
            position: relative;
            background-color: #32CD32;
            cursor: pointer;
        }

        .highlight:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            top: 50%;
            left: 110%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 2% 3%;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            font-size: 1.5vw;
            opacity: 0.9;
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
                <div id="home" class="page">
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
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px; font-weight: bold;">
                        Percentage of Total Slots (%)
                    </div>
                </div>
                <div class="chart-container"> <!-- Biểu đồ tròn -->
                    <canvas id="pieChart"></canvas>
                    <div id="chartCaption" style="text-align: center; color: white; margin-top: 5px; font-weight: bold;">
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
                    intersect: true, // Tooltip chỉ hiện khi chuột nằm trên cột
                    mode: 'nearest', // Canh tooltip theo điểm gần nhất
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
                        label: function (tooltipItem) {
                            const customerId = tooltipItem.label;
                            const slotCount = tooltipItem.raw;
                            return `${customerId}: ${slotCount} slots`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false // Không hiển thị vạch dọc
                    },
                    ticks: {
                        color: 'white', // Màu chữ trục X
                        font: {
                            size: 20
                        }
                    },
                    barPercentage: 0.9, // Tăng tỷ lệ chiều rộng cột
                    categoryPercentage: 0.8 // Tăng khoảng cách giữa các nhóm cột
                },
                y: {
                    min: 0,
                    max: 100,
                    ticks: {
                        color: 'white',
                        font: {
                            size: 20
                        },
                        stepSize: 10
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