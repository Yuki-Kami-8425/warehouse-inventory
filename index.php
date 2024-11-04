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
} elseif (in_array($station, ['A', 'B', 'C', 'D', 'E', 'F', 'G'])) {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE ?";
    $params = array($station . '%');
}
 else {
    // Nếu không chọn all hoặc các trạm A-G, không hiển thị gì
    $sql = null; // Không truy vấn
    $params = null; // Không cần tham số
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
            <div id="list" class="page">
                <h2>Danh sách kho hàng</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Số chứng từ</th>
                            <th>Ngày chứng từ</th>
                            <th>Mã khách hàng</th>
                            <th>Tên khách hàng</th>
                            <th>Mã sản phẩm</th>
                            <th>Tên sản phẩm</th>
                            <th>Đơn vị</th>
                            <th>Số lượng pallet</th>
                            <th>RFID</th>
                            <th>Trạng thái pallet</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối đến cơ sở dữ liệu
                        $conn = new mysqli($servername, $username, $password, $dbname);
                        
                        // Kiểm tra kết nối
                        if ($conn->connect_error) {
                            die("Kết nối thất bại: " . $conn->connect_error);
                        }

                        // Truy vấn dữ liệu
                        $sql = "SELECT TOP (1000) [SOCT], [NGAYCT], [MAKH], [TENKH], [MASP], [TENSP], [DONVI], [LUONG_PALLET], [RFID], [PALLET_status] FROM [dbo].[stored_warehouse]";
                        $result = $conn->query($sql);

                        // Hiển thị dữ liệu
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>" . htmlspecialchars($row['SOCT']) . "</td>
                                        <td>" . htmlspecialchars($row['NGAYCT']) . "</td>
                                        <td>" . htmlspecialchars($row['MAKH']) . "</td>
                                        <td>" . htmlspecialchars($row['TENKH']) . "</td>
                                        <td>" . htmlspecialchars($row['MASP']) . "</td>
                                        <td>" . htmlspecialchars($row['TENSP']) . "</td>
                                        <td>" . htmlspecialchars($row['DONVI']) . "</td>
                                        <td>" . htmlspecialchars($row['LUONG_PALLET']) . "</td>
                                        <td>" . htmlspecialchars($row['RFID']) . "</td>
                                        <td>" . htmlspecialchars($row['PALLET_status']) . "</td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='10'>Không có dữ liệu</td></tr>";
                        }
                        // Đóng kết nối
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<script>
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
    const dropdownContainer = document.querySelector('.dropdown-container');
    const dashboardButton = document.querySelector('.dropdown-toggle'); // Nút Dashboard
    const homeButton = document.querySelector('#home-button'); // Nút Home
    const listButton = document.querySelector('#list-button'); // Nút List

    function toggleDropdown() {
        dropdownContainer.classList.toggle('show');
    }

    dashboardButton.addEventListener('click', function() {
        toggleDropdown();
        this.classList.toggle('active'); // Đánh dấu nút Dashboard là active
    });

    homeButton.addEventListener('click', function() {
        dropdownContainer.classList.remove('show'); // Ẩn dropdown khi ấn Home
        document.querySelectorAll('.sidebar a, .dropdown-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active'); // Đánh dấu nút Home là active
    });

    listButton.addEventListener('click', function() {
        dropdownContainer.classList.remove('show'); // Ẩn dropdown khi ấn List
        document.querySelectorAll('.sidebar a, .dropdown-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active'); // Đánh dấu nút List là active
    });

    // Ẩn dropdown khi chọn một trạm, nhưng chỉ ẩn nếu không phải đang xổ xuống
    document.querySelectorAll('.dropdown-container a').forEach(item => {
        item.addEventListener('click', function() {
            dropdownContainer.classList.remove('show'); // Ẩn dropdown khi chọn một trạm
            document.querySelectorAll('.sidebar a, .dropdown-btn').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active'); // Đánh dấu nút được chọn là active
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
            homeButton.classList.add('active'); // Đánh dấu nút Home là active
        } else if (urlParams.get('station') === 'list') {
            showPage('list');
            listButton.classList.add('active'); // Đánh dấu nút List là active
        } else {
            showPage('data');
            dashboardButton.classList.add('active'); // Đánh dấu nút Dashboard là active
        }
    };
</script>