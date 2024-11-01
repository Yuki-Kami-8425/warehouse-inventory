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
            width: 60px;
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 16px; /* Cỡ chữ đã được điều chỉnh */
            color: white;
            display: flex;
            align-items: center; /* Căn giữa icon và chữ */
        }
        .sidebar a img {
            width: 24px; /* Kích thước icon */
            margin-right: 10px; /* Khoảng cách giữa icon và chữ */
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
            display: flex;
            align-items: center;
        }
        .dropdown-btn:hover {
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
            width: calc(100% - 250px);
            transition: margin-left 0.3s, width 0.3s;
        }
        .main-content.collapsed {
            margin-left: 60px;
            width: calc(100% - 60px);
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
        .toggle-button {
            position: absolute;
            left: 10px;
            top: 10px;
            background-color: #111;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<button class="toggle-button" onclick="toggleSidebar()">☰</button>

<div class="sidebar">
    <a href="#"><img src="icon_home.png" alt="Home Icon">Home</a>
    <button class="dropdown-btn"><img src="icon_dashboard.png" alt="Dashboard Icon">Dashboard <i class="fa fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all"><img src="icon_all.png" alt="All Icon">All</a>
        <a href="?station=A"><img src="icon_a.png" alt="Station A Icon">Station A</a>
        <a href="?station=B"><img src="icon_b.png" alt="Station B Icon">Station B</a>
        <a href="?station=C"><img src="icon_c.png" alt="Station C Icon">Station C</a>
        <a href="?station=D"><img src="icon_d.png" alt="Station D Icon">Station D</a>
        <a href="?station=E"><img src="icon_e.png" alt="Station E Icon">Station E</a>
        <a href="?station=F"><img src="icon_f.png" alt="Station F Icon">Station F</a>
        <a href="?station=G"><img src="icon_g.png" alt="Station G Icon">Station G</a>
    </div>
    <a href="#"><img src="icon_list.png" alt="List Icon">List</a>
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
    };

    // Thiết lập biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: barData,
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Thiết lập biểu đồ tròn
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'pie',
        data: pieData,
    });

    // Chức năng thu gọn/không thu gọn sidebar
    let sidebarCollapsed = false;

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        sidebarCollapsed = !sidebarCollapsed;
        sidebar.classList.toggle('collapsed', sidebarCollapsed);
        mainContent.classList.toggle('collapsed', sidebarCollapsed);
    }

    // Hiển thị menu dropdown cho Dashboard
    const dropdown = document.querySelector('.dropdown-btn');
    const dropdownContainer = document.querySelector('.dropdown-container');
    dropdown.addEventListener('click', () => {
        dropdownContainer.style.display = dropdownContainer.style.display === 'none' ? 'block' : 'none';
    });
</script>

</body>
</html>
