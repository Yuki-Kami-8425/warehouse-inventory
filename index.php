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
            background-color: #001F3F; /* Màu nền */
            color: white; /* Màu chữ trắng */
            display: flex;
        }
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #111;
            padding-top: 20px;
            position: fixed;
            transition: width 0.3s; /* Hiệu ứng chuyển đổi khi thu gọn */
        }
        .sidebar.collapsed {
            width: 60px; /* Kích thước sidebar khi thu gọn */
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar" id="sidebar">
    <a href="#">Home <i class="fas fa-home"></i></a>
    <button class="dropdown-btn">Dashboard <i class="fas fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all">All <i class="fas fa-warehouse"></i></a>
        <a href="?station=A">Station A <i class="fas fa-location-arrow"></i></a>
        <a href="?station=B">Station B <i class="fas fa-location-arrow"></i></a>
        <a href="?station=C">Station C <i class="fas fa-location-arrow"></i></a>
        <a href="?station=D">Station D <i class="fas fa-location-arrow"></i></a>
        <a href="?station=E">Station E <i class="fas fa-location-arrow"></i></a>
        <a href="?station=F">Station F <i class="fas fa-location-arrow"></i></a>
        <a href="?station=G">Station G <i class="fas fa-location-arrow"></i></a>
    </div>
    <a href="#">List <i class="fas fa-list"></i></a>
    <button id="toggleSidebar" style="background: none; border: none; color: white; cursor: pointer; padding: 10px 15px;">
        <i class="fas fa-angle-left"></i>
    </button>
</div>

<div class="main-content" id="main-content">
    <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>

    <!-- Phần hiển thị kho -->
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
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Toggle sidebar
    const toggleButton = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');

    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
        toggleButton.innerHTML = sidebar.classList.contains('collapsed') ? '<i class="fas fa-angle-right"></i>' : '<i class="fas fa-angle-left"></i>';
    });

    // Dữ liệu biểu đồ
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers);
    const customerData = customerLabels.map(label => customers[label].length);

    // Biểu đồ cột
    const barCtx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [{
                label: 'Số lượng RFID',
                data: customerData,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Biểu đồ tròn
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: customerLabels,
            datasets: [{
                data: customerData,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)',
                    'rgba(153, 102, 255, 0.2)',
                    'rgba(255, 159, 64, 0.2)',
                    'rgba(255, 99, 132, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

</body>
</html>
