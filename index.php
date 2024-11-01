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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Warehouse Management - <?= $station === 'all' ? 'All Stations' : 'Station ' . $station ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #001F3F; /* Xanh đậm */
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
            transition: width 0.3s; /* Hiệu ứng khi thu gọn */
        }
        .sidebar.collapsed {
            width: 80px; /* Kích thước khi thu gọn */
        }
        .sidebar a, .dropdown-btn {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 16px;
            color: white;
            display: flex;
            align-items: center; /* Canh giữa icon và text */
        }
        .sidebar a i, .dropdown-btn i {
            margin-right: 10px; /* Khoảng cách giữa icon và text */
        }
        .sidebar a:hover {
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
            transition: margin-left 0.3s; /* Hiệu ứng khi thu gọn */
        }
        .main-content.collapsed {
            margin-left: 80px; /* Kích thước khi thu gọn */
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

        /* Icon thu gọn sidebar */
        .toggle-btn {
            position: absolute;
            top: 10px;
            right: -25px; /* Đặt ở bên phải sidebar */
            background-color: #111;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 18px;
            padding: 10px;
            transition: right 0.3s;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">&#9776;</button> <!-- Icon thu gọn -->
    <a href="#"><i class="fas fa-home"></i> Home</a>
    <button class="dropdown-btn"><i class="fas fa-tachometer-alt"></i> Dashboard <i class="fa fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all"><i class="fas fa-box"></i> All</a>
        <a href="?station=A"><i class="fas fa-box"></i> Station A</a>
        <a href="?station=B"><i class="fas fa-box"></i> Station B</a>
        <a href="?station=C"><i class="fas fa-box"></i> Station C</a>
        <a href="?station=D"><i class="fas fa-box"></i> Station D</a>
        <a href="?station=E"><i class="fas fa-box"></i> Station E</a>
        <a href="?station=F"><i class="fas fa-box"></i> Station F</a>
        <a href="?station=G"><i class="fas fa-box"></i> Station G</a>
    </div>
    <a href="#"><i class="fas fa-list"></i> List</a>
</div>

<div class="main-content">
    <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>

    <?php if ($station !== 'all'): ?>
        <div class="container">
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
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Dữ liệu cho biểu đồ cột
    const barData = {
        labels: [<?php foreach ($data as $item) echo '"' . $item['TENKH'] . '", '; ?>],
        datasets: [{
            label: 'Number of Pallets',
            data: [<?php foreach ($data as $item) echo $item['LUONG_PALLET'] . ', '; ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    };

    // Dữ liệu cho biểu đồ tròn
    const pieData = {
        labels: [<?php foreach ($data as $item) echo '"' . $item['TENKH'] . '", '; ?>],
        datasets: [{
            label: 'Number of Pallets',
            data: [<?php foreach ($data as $item) echo $item['LUONG_PALLET'] . ', '; ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)',
                'rgba(255, 205, 86, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(255, 205, 86, 1)'
            ],
            borderWidth: 1
        }]
    };

    const barChartConfig = {
        type: 'bar',
        data: barData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    };

    const pieChartConfig = {
        type: 'pie',
        data: pieData
    };

    const barChart = new Chart(
        document.getElementById('barChart'),
        barChartConfig
    );

    const pieChart = new Chart(
        document.getElementById('pieChart'),
        pieChartConfig
    );

    // Hàm thu gọn sidebar
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('collapsed');
    }

    // Dropdown toggle
    const dropdown = document.querySelector('.dropdown-btn');
    dropdown.onclick = function () {
        this.nextElementSibling.classList.toggle('show');
    };

    // Đóng dropdown nếu nhấp bên ngoài
    window.onclick = function (event) {
        if (!event.target.matches('.dropdown-btn')) {
            const dropdowns = document.getElementsByClassName("dropdown-container");
            for (let i = 0; i < dropdowns.length; i++) {
                dropdowns[i].classList.remove('show');
            }
        }
    };
</script>
</body>
</html>
