<?php 
// Thông tin kết nối cơ sở dữ liệu Azure SQL (không thay đổi)
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

// Đặt tên trạm
$stations = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
$station = isset($_GET['station']) ? $_GET['station'] : 'A'; // Lấy trạm từ tham số URL

// Lấy dữ liệu từ bảng cho trạm
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$station%'";
$stmt = sqlsrv_query($conn, $sql);

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
    $customers[$row['MAKH']][] = $row['RFID'];
    $highlighted[] = trim($row['RFID']);
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - Station <?= $station ?></title>
    <style>
        body {
            background-color: #001F3F;
            color: white;
            font-size: 8px;
        }
        h2 {
            text-align: center;
            font-size: 24px;
        }
        caption {
            font-size: 16px;
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
            width: 30%;
            margin: 20px;
        }
        .charts {
            display: flex;
            justify-content: space-around;
        }
        /* Thêm kiểu cho nút */
        .nav-buttons {
            text-align: center;
            margin: 20px;
        }
        .nav-buttons a {
            margin: 5px;
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .nav-buttons a:hover {
            background-color: #0056b3;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Station <?= $station ?></h2>

<!-- Phần điều hướng cho các trạm -->
<div class="nav-buttons">
    <?php foreach ($stations as $st): ?>
        <a href="?station=<?= $st ?>">Station <?= $st ?></a>
    <?php endforeach; ?>
</div>

<div class="container">
    <!-- Bảng Left Rack -->
    <table>
        <caption style="caption-side: top;">Left Rack</caption>
        <?php for ($row = 7; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 14; $col++): ?>
                    <?php $index = ($row - 1) * 14 + $col; ?>
                    <td class="<?= in_array('AL' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AL<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    <!-- Bảng Right Rack -->
    <table>
        <caption style="caption-side: top;">Right Rack</caption>
        <?php for ($row = 7; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 14; $col++): ?>
                    <?php $index = ($row - 1) * 14 + $col; ?>
                    <td class="<?= in_array('AR' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AR<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
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

<script>
    // Dữ liệu biểu đồ
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers);
    const customerData = customerLabels.map(key => customers[key].length);
    const totalSlots = 196;
    const filledSlots = <?= count($highlighted) ?>;

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
</script>

</body>
</html>
