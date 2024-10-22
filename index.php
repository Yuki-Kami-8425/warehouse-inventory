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

// Lấy dữ liệu từ RFID
$sql = "SELECT DISTINCT MAKH FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Lấy tên khách hàng
$customers = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $customers[] = $row['MAKH'];
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - Station A</title>
    <style>
        body {
            background-color: #001F3F; /* Xanh đậm */
            color: white; /* Màu chữ trắng */
            font-size: 8px; /* Kích thước chữ */
        }
        table {
            width: 45%; /* Chiếm 1/4 màn hình */
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            border: 2px solid white; /* Đường viền trắng */
            padding: 10px;
            text-align: center;
        }
        td.highlight {
            background-color: #32CD32; /* Màu xanh lục cho ô được highlight */
        }
        .chart-container {
            width: 45%; /* Chiếm 1/4 màn hình */
            margin: 20px auto;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2 style="text-align: center;">Warehouse Station A</h2>

<!-- Bảng Left Rack -->
<table>
    <caption style="caption-side: top;">Left Rack</caption>
    <tr>
        <td>AL85</td><td>AL86</td><td>AL87</td><td>AL88</td><td>AL89</td>
        <td>AL90</td><td>AL91</td><td>AL92</td><td>AL93</td><td>AL94</td>
        <td>AL95</td><td>AL96</td><td>AL97</td><td>AL98</td>
    </tr>
    <?php for ($i = 6; $i >= 0; $i--): ?>
        <tr>
            <?php for ($j = 1; $j <= 14; $j++): ?>
                <td class="<?= (in_array("AL" . str_pad($j, 2, "0", STR_PAD_LEFT), $customers) ? 'highlight' : '') ?>">AL<?= str_pad($j, 2, "0", STR_PAD_LEFT) ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; ?>
</table>

<!-- Bảng Right Rack -->
<table>
    <caption style="caption-side: top;">Right Rack</caption>
    <tr>
        <td>AR85</td><td>AR86</td><td>AR87</td><td>AR88</td><td>AR89</td>
        <td>AR90</td><td>AR91</td><td>AR92</td><td>AR93</td><td>AR94</td>
        <td>AR95</td><td>AR96</td><td>AR97</td><td>AR98</td>
    </tr>
    <?php for ($i = 6; $i >= 0; $i--): ?>
        <tr>
            <?php for ($j = 1; $j <= 14; $j++): ?>
                <td class="<?= (in_array("AR" . str_pad($j, 2, "0", STR_PAD_LEFT), $customers) ? 'highlight' : '') ?>">AR<?= str_pad($j, 2, "0", STR_PAD_LEFT) ?></td>
            <?php endfor; ?>
        </tr>
    <?php endfor; ?>
</table>

<!-- Biểu đồ cột -->
<div class="chart-container">
    <canvas id="barChart"></canvas>
</div>

<!-- Biểu đồ tròn -->
<div class="chart-container">
    <canvas id="pieChart"></canvas>
</div>

<script>
    // Dữ liệu biểu đồ
    const customerCount = <?= count($customers) ?>; // Số khách hàng
    const totalSlots = 196; // Tổng số ô (98x2)
    const filledSlots = <?= count(array_filter($customers, fn($c) => str_starts_with($c, 'A'))) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Customers'],
            datasets: [{
                label: 'Number of Customers',
                data: [customerCount],
                backgroundColor: 'rgba(0, 123, 255, 1)', // Màu lam
                borderColor: 'white',
                borderWidth: 2
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
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Filled', 'Available'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Màu đỏ và xanh
                borderColor: 'white',
                borderWidth: 2
            }]
        }
    });
</script>

</body>
</html>
