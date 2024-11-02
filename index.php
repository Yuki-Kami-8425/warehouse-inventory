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

// Lấy trạm từ query string, mặc định là 'all'
$station = isset($_GET['station']) ? $_GET['station'] : 'all';

// Lấy dữ liệu từ bảng cho tất cả các trạm nếu là 'all', ngược lại lấy theo trạm
if ($station === 'all') {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse";
} else {
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
        }
        .sidebar a i, .dropdown-btn i {
            margin-right: 10px; /* Khoảng cách giữa icon và chữ */
        }
        .sidebar a:hover, .dropdown-btn:hover {
            background-color: #575757;
        }
        .dropdown-container {
            display: none;
            background-color: #262626;
        }
        .dropdown-container a {
            padding-left: 30px;
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <li>
        <a href="#" onclick="showPage('home');" class="main-link">
            <i class="fas fa-home"></i>
            <span class="link-text">Home</span>
        </a>
    </li>

    <button class="dropdown-btn">
        <i class="fas fa-tachometer-alt"></i>
        <span class="link-text">Dashboard</span>
    </button>
    <div class="dropdown-container">
        <a href="?station=all">
            <i class="fas fa-th-list"></i>
            <span class="link-text">All</span>
        </a>
        <a href="?station=A">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station A</span>
        </a>
        <a href="?station=B">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station B</span>
        </a>
        <a href="?station=C">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station C</span>
        </a>
        <a href="?station=D">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station D</span>
        </a>
        <a href="?station=E">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station E</span>
        </a>
        <a href="?station=F">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station F</span>
        </a>
        <a href="?station=G">
            <i class="fas fa-industry"></i>
            <span class="link-text">Station G</span>
        </a>
    </div>

    <a href="#">
        <i class="fas fa-list"></i>
        <span class="link-text">List</span>
    </a>
</div>

<div class="main-content" id="main-content">
    <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>

    <?php if ($station !== 'all'): ?>
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
    <?php endif; ?>

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
    }
}
</script>