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
            transition: margin-left 0.3s; /* Chuyển động cho toàn bộ */
        }
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #111;
            padding-top: 20px;
            position: fixed;
            transition: width 0.3s;
            overflow: hidden; /* Ẩn nội dung bên trong khi thu gọn */
        }
        .sidebar a, .sidebar button {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: flex; /* Sử dụng flex để căn giữa icon và chữ */
            align-items: center; /* Căn giữa theo chiều dọc */
            border: none;
            background: none;
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
        .main-content {
            margin-left: 250px; /* Đặt lề trái cho nội dung chính */
            padding: 20px;
            width: calc(100% - 250px); /* Đảm bảo nội dung chính không bị chồng lên sidebar */
            transition: margin-left 0.3s; /* Chuyển động cho nội dung chính */
        }
        .collapsed .sidebar {
            width: 60px; /* Chiều rộng của sidebar khi thu gọn */
        }
        .collapsed .main-content {
            margin-left: 60px; /* Đặt lề trái cho nội dung chính khi sidebar thu gọn */
            width: calc(100% - 60px); /* Đảm bảo nội dung chính không bị chồng lên sidebar */
        }
        .collapsed .sidebar a span {
            display: none; /* Ẩn văn bản khi thu gọn */
        }
        .sidebar a i {
            min-width: 30px; /* Đảm bảo icon có chiều rộng cố định */
            text-align: center; /* Căn giữa icon */
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
    <a href="#"><i class="fas fa-home"></i> <span>Home</span></a>
    <button class="dropdown-btn"><i class="fas fa-chart-bar"></i> <span>Dashboard</span> <i class="fas fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all"><i class="fas fa-box"></i> <span>All</span></a>
        <a href="?station=A"><i class="fas fa-warehouse"></i> <span>Station A</span></a>
        <a href="?station=B"><i class="fas fa-warehouse"></i> <span>Station B</span></a>
        <a href="?station=C"><i class="fas fa-warehouse"></i> <span>Station C</span></a>
        <a href="?station=D"><i class="fas fa-warehouse"></i> <span>Station D</span></a>
        <a href="?station=E"><i class="fas fa-warehouse"></i> <span>Station E</span></a>
        <a href="?station=F"><i class="fas fa-warehouse"></i> <span>Station F</span></a>
        <a href="?station=G"><i class="fas fa-warehouse"></i> <span>Station G</span></a>
    </div>
    <a href="#"><i class="fas fa-list"></i> <span>List</span></a>
    <button id="toggle-btn"><i class="fas fa-angle-left"></i></button>
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
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>
</div>

<script>
    // Biểu đồ cột
    const customerData = <?= json_encode(array_values($customers)) ?>; // Thay đổi dữ liệu để phù hợp với biểu đồ
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_keys($customers)) ?>,
            datasets: [{
                label: 'Used Slots',
                data: customerData.map(customer => customer.length), // Đếm số lượng RFID
                backgroundColor: 'rgba(75, 192, 192, 1)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Used Slots by Customer'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: Math.max(...customerData.map(customer => customer.length)) + 1 // Giới hạn tối đa cho trục Y
                }
            }
        }
    });

    // Biểu đồ tròn
    const filledSlots = <?= array_sum(array_map('count', $customers)) ?>; // Tổng số slot đã sử dụng
    const totalSlots = <?= ($station === 'all') ? '98 * 2 * 7' : '98 * 2' ?>; // Tổng số slot cho tất cả trạm
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Filled Slots', 'Available Slots'],
            datasets: [{
                label: 'Total Slots',
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 206, 86, 1)'
                ],
                borderColor: 'white',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Warehouse Slot Usage'
                }
            }
        }
    });

    // Toggle sidebar
    const toggleButton = document.getElementById('toggle-btn');
    const sidebar = document.getElementById('sidebar');
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        toggleButton.innerHTML = sidebar.classList.contains('collapsed') ? '<i class="fas fa-angle-right"></i>' : '<i class="fas fa-angle-left"></i>';
    });
</script>

</body>
</html>
