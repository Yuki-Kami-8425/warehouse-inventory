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
$highlighted = [];
$chartData = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $highlighted[] = trim($row['RFID']);
    $chartData[] = [
        'TENKH' => $row['TENKH'],
        'LUONG_PALLET' => $row['LUONG_PALLET']
    ];
}

// Đóng kết nối
sqlsrv_close($conn);

// Tạo dữ liệu cho biểu đồ
$labels = [];
$values = [];

foreach ($chartData as $data) {
    $labels[] = $data['TENKH'];
    $values[] = $data['LUONG_PALLET'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - Station <?= $station ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #001F3F;
            color: white;
            font-size: 12px;
            font-family: Arial, sans-serif;
        }
        h2 {
            text-align: center;
            font-size: 24px;
        }
        .container {
            display: flex;
            justify-content: space-around;
            margin: 20px;
        }
        table {
            width: 30%;
            border-collapse: collapse;
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
                    <td class="<?= in_array($station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>L<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
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
                    <td class="<?= in_array($station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>R<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>
</div>

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
// Dữ liệu cho biểu đồ cột
const barLabels = <?= json_encode($labels) ?>;
const barData = <?= json_encode($values) ?>;

// Biểu đồ cột
const barCtx = document.getElementById('barChart').getContext('2d');
const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: barLabels,
        datasets: [{
            label: 'Pallets',
            data: barData,
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

// Biểu đồ tròn
const pieCtx = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Occupied', 'Available'],
        datasets: [{
            data: [barData.reduce((a, b) => a + b, 0), 196 - barData.reduce((a, b) => a + b, 0)],
            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>

</body>
</html>
