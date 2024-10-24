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
    <link rel="stylesheet" href="styles.css">
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
        }
        .sidebar a {
            padding: 10px 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
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
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="sidebar" id="sidebar">
    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <li><a href="#" onclick="showPage('home');" class="main-link"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
    <button class="dropdown-btn">Dashboard 
        <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
        <a href="?station=all">All</a>
        <a href="?station=A">Station A</a>
        <a href="?station=B">Station B</a>
        <a href="?station=C">Station C</a>
        <a href="?station=D">Station D</a>
        <a href="?station=E">Station E</a>
        <a href="?station=F">Station F</a>
        <a href="?station=G">Station G</a>
    </div>
    <li><a href="#" onclick="showPage('edit-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> Edit</span></a></li>
</div>

<div class="main-content">
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
</script>
<script src="script.js"></script>
</body>
</html>
