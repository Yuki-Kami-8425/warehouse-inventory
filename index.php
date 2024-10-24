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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            transition: width 0.3s;
        }
        .sidebar.collapsed {
            width: 50px; /* Chiều rộng khi thu gọn */
        }
        .sidebar a, .sidebar button {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: flex; /* Hiển thị theo chiều dọc */
            align-items: center; /* Căn giữa biểu tượng và văn bản */
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .dropdown-btn {
            background-color: #111;
            color: white;
            border: none;
            padding: 10px 15px;
            width: 100%;
            text-align: left;
            cursor: pointer;
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
            background-color: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
        }

        .sidebar.collapsed .link-text {
            display: none; /* Ẩn văn bản khi sidebar thu gọn */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <button class="toggle-btn">
        <i class="fa fa-bars"></i> <!-- Biểu tượng để thu gọn -->
    </button>
    <div class="sidebar-content">
        <a href="#">
            <i class="fa fa-home"></i> <!-- Biểu tượng Home -->
            <span class="link-text">Home</span>
        </a>
        <button class="dropdown-btn">
            <i class="fa fa-tachometer-alt"></i> <!-- Biểu tượng Dashboard -->
            <span class="link-text">Dashboard</span>
        </button>
        <div class="dropdown-container">
            <a href="?station=all">
                <i class="fa fa-th-large"></i> <!-- Biểu tượng All -->
                <span class="link-text">All</span>
            </a>
            <a href="?station=A">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station A -->
                <span class="link-text">Station A</span>
            </a>
            <a href="?station=B">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station B -->
                <span class="link-text">Station B</span>
            </a>
            <a href="?station=C">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station C -->
                <span class="link-text">Station C</span>
            </a>
            <a href="?station=D">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station D -->
                <span class="link-text">Station D</span>
            </a>
            <a href="?station=E">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station E -->
                <span class="link-text">Station E</span>
            </a>
            <a href="?station=F">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station F -->
                <span class="link-text">Station F</span>
            </a>
            <a href="?station=G">
                <i class="fa fa-warehouse"></i> <!-- Biểu tượng Station G -->
                <span class="link-text">Station G</span>
            </a>
        </div>
        <a href="#">
            <i class="fa fa-list"></i> <!-- Biểu tượng List -->
            <span class="link-text">List</span>
        </a>
    </div>
</div>

<div class="main-content">
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

    <!-- Bảng dữ liệu -->
    <div class="container">
        <table>
            <caption>Data Table</caption>
            <thead>
                <tr>
                    <th>MAKH</th>
                    <th>TENKH</th>
                    <th>LUONG_PALLET</th>
                    <th>RFID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= $row['MAKH'] ?></td>
                        <td><?= $row['TENKH'] ?></td>
                        <td><?= $row['LUONG_PALLET'] ?></td>
                        <td><?= $row['RFID'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Biểu đồ -->
    <div class="chart-container">
        <canvas id="myChart"></canvas>
    </div>
</div>

<script>
    // Biểu đồ sử dụng Chart.js
    const ctx = document.getElementById('myChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($customers)) ?>,
            datasets: [{
                label: 'Số lượng Pallet',
                data: <?= json_encode(array_map(function($customer) {
                    return count($customer);
                }, $customers)) ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Xử lý thu gọn sidebar
    document.querySelector('.toggle-btn').addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
        const dropdowns = document.querySelectorAll('.dropdown-container');
        dropdowns.forEach(dropdown => dropdown.style.display = 'none'); // Ẩn tất cả dropdowns khi thu gọn
    });

    // Xử lý dropdown cho Dashboard
    const dropdownBtn = document.querySelector('.dropdown-btn');
    dropdownBtn.addEventListener('click', () => {
        const dropdownContainer = document.querySelector('.dropdown-container');
        dropdownContainer.style.display = dropdownContainer.style.display === 'block' ? 'none' : 'block';
    });
</script>
</body>
</html>
