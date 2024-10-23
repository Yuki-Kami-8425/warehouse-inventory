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

// Xác định trạm đang hiển thị hoặc dashboard
$station = isset($_GET['station']) ? strtoupper($_GET['station']) : 'ALL';
$total_slots = 1372; // Tổng số pallet trong kho

// Truy vấn dữ liệu cho tất cả trạm hoặc trạm cụ thể
if ($station === 'ALL') {
    $sql = "SELECT TENKH, COUNT(*) as pallet_count FROM dbo.stored_warehouse GROUP BY TENKH";
} else {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$station%'";
}

$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Chuẩn bị dữ liệu cho các trạm
$data = [];
$customers = [];
$highlighted = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if ($station === 'ALL') {
        $customers[] = $row['TENKH'];
        $pallets[] = $row['pallet_count'];
    } else {
        $data[] = $row;
        $customers[$row['MAKH']][] = $row['RFID']; // Lưu danh sách RFID cho mỗi khách hàng
        $highlighted[] = trim($row['RFID']); // Danh sách RFID để highlight
    }
}

// Đóng kết nối
sqlsrv_close($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
    <style>
        body {
            background-color: #001F3F; /* Màu nền xanh đậm */
            color: white;
            font-size: 8px;
        }
        h2 {
            text-align: center;
            font-size: 24px;
        }
        .container, .charts {
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
            background-color: #32CD32; /* Màu xanh lục cho highlight */
        }
        .chart-container {
            width: 30%;
            margin: 20px;
        }
        .menu {
            text-align: center;
            margin-bottom: 20px;
        }
        .menu a {
            margin: 0 10px;
            padding: 5px 10px;
            color: white;
            background-color: #007BFF;
            text-decoration: none;
            border-radius: 5px;
        }
        .menu a:hover {
            background-color: #0056b3;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Management</h2>

<div class="menu">
    <a href="?station=all">Dashboard</a>
    <a href="?station=A">Station A</a>
    <a href="?station=B">Station B</a>
    <a href="?station=C">Station C</a>
    <a href="?station=D">Station D</a>
    <a href="?station=E">Station E</a>
    <a href="?station=F">Station F</a>
    <a href="?station=G">Station G</a>
</div>

<?php if ($station === 'ALL'): ?>
    <div class="charts">
        <!-- Biểu đồ tròn: Tổng số pallet -->
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>

        <!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <!-- Bảng Left Rack -->
        <table>
            <caption>Left Rack</caption>
            <?php for ($row = 7; $row >= 1; $row--): ?>
                <tr>
                    <?php for ($col = 1; $col <= 14; $col++): ?>
                        <?php $index = ($row - 1) * 14 + $col; ?>
                        <td class="<?= in_array($station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>L<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
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
                        <td class="<?= in_array($station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>R<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>
<?php endif; ?>

<script>
<?php if ($station === 'ALL'): ?>
    // Dữ liệu cho biểu đồ tròn
    var totalPalletData = {
        datasets: [{
            data: [<?= $total_slots - $total_pallets ?>, <?= $total_pallets ?>],
            backgroundColor: ['#FF6384', '#36A2EB'],
            borderColor: ['#FFFFFF', '#FFFFFF'],
            borderWidth: 2
        }],
        labels: ['Empty Slots', 'Stored Pallets']
    };

    // Dữ liệu cho biểu đồ cột
    var barChartData = {
        labels: <?= json_encode($customers) ?>,
        datasets: [{
            label: 'Pallets Stored',
            backgroundColor: '#36A2EB',
            borderColor: '#FFFFFF',
            borderWidth: 2,
            data: <?= json_encode($pallets) ?>
        }]
    };

    // Vẽ biểu đồ tròn
    var ctx1 = document.getElementById('pieChart').getContext('2d');
    var pieChart = new Chart(ctx1, {
        type: 'pie',
        data: totalPalletData,
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

    // Vẽ biểu đồ cột
    var ctx2 = document.getElementById('barChart').getContext('2d');
    var barChart = new Chart(ctx2, {
        type: 'bar',
        data: barChartData,
        options: {
            scales: {
                x: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    ticks: {
                        color: 'white'
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.2)'
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            }
        }
    });
<?php endif; ?>
</script>

</body>
</html>
