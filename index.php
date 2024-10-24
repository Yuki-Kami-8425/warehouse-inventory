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
            margin: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .sidebar {
            height: 100%;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
        }
        .sidebar a, .dropdown-btn {
            padding: 15px 20px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            outline: none;
        }
        .sidebar a:hover, .dropdown-btn:hover {
            background-color: #575757;
        }
        .dropdown-container {
            display: none;
            background-color: #414141;
            padding-left: 20px;
        }
        .main {
            margin-left: 260px;
            padding: 20px;
        }
        .chart-container {
            width: 50%;
            margin: auto;
        }
        .charts {
            display: flex;
            justify-content: space-around;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <a href="#">Home</a>
    <button class="dropdown-btn">Dashboard 
        <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
        <a href="?station=all">All</a>
        <?php foreach (range('A', 'G') as $st): ?>
            <a href="?station=<?= $st ?>">Station <?= $st ?></a>
        <?php endforeach; ?>
    </div>
    <a href="#">List</a>
</div>

<!-- Main content -->
<div class="main">
    <h2>Warehouse <?= $station === 'all' ? 'All Stations' : 'Station ' . $station ?></h2>

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
    // Lấy dữ liệu từ PHP
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers);
    const customerData = customerLabels.map(key => customers[key].length);

    // Tính toán số ô cho "All" trạm
    const totalSlots = 1372; // 98x2x7 = 1372 ô cho cả 7 trạm
    const filledSlots = <?= count($highlighted) ?>;

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [{
                label: 'Number of Stored Pallets',
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
                        color: '#333'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Stored Pallets',
                        color: '#333'
                    },
                    ticks: {
                        stepSize: 1
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
                        color: '#333'
                    }
                }
            }
        }
    });

    // Dropdown toggle
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.classList.toggle('active');
        const dropdownContent = this.nextElementSibling;
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
</script>

</body>
</html>
