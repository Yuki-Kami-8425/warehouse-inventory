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

// Truy vấn để lấy danh sách RFID cho trạm A
$sql = "SELECT RFID FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu các RFID của trạm A
$rfids = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rfids[] = $row['RFID'];
}

// Truy vấn số lượng pallet theo khách hàng cho trạm A
$sql_customer = "SELECT TENKH, COUNT(*) as pallet_count FROM dbo.stored_warehouse WHERE RFID LIKE 'A%' GROUP BY TENKH";
$stmt_customer = sqlsrv_query($conn, $sql_customer);

$customers = [];
$pallets = [];
while ($row = sqlsrv_fetch_array($stmt_customer, SQLSRV_FETCH_ASSOC)) {
    $customers[] = $row['TENKH'];
    $pallets[] = $row['pallet_count'];
}

// Tính tổng số pallet trong trạm A
$total_pallets = array_sum($pallets);

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Rack Display</title>
    <style>
        body {
            background-color: #003366; /* Xanh đậm */
            color: white;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .rack-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            margin: 10px;
        }
        th, td {
            padding: 8px;
            border: 1px solid white;
            text-align: center;
            width: 50px;
        }
        .highlight {
            background-color: yellow; /* Màu phát sáng cho ô tìm thấy */
            color: black;
        }
        canvas {
            background-color: white;
            margin: 10px;
            width: 400px;
            height: 300px;
        }
    </style>
</head>
<body>

<h1>Warehouse Station A - Rack Display</h1>

<div class="rack-container">
    <!-- Left Rack -->
    <table>
        <caption>Left Rack</caption>
        <tbody>
            <?php
            $leftRack = [];
            for ($i = 98; $i >= 1; $i--) {
                $leftRack[] = sprintf("AL%02d", $i);
            }

            for ($row = 0; $row < 7; $row++) {
                echo "<tr>";
                for ($col = 0; $col < 14; $col++) {
                    $cell = $leftRack[$row * 14 + $col];
                    $highlightClass = ($cell == "AL04") ? "highlight" : ""; // Example highlight for AL04
                    echo "<td class='$highlightClass'>$cell</td>";
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Right Rack -->
    <table>
        <caption>Right Rack</caption>
        <tbody>
            <?php
            $rightRack = [];
            for ($i = 98; $i >= 1; $i--) {
                $rightRack[] = sprintf("AR%02d", $i);
            }

            for ($row = 0; $row < 7; $row++) {
                echo "<tr>";
                for ($col = 0; $col < 14; $col++) {
                    $cell = $rightRack[$row * 14 + $col];
                    $highlightClass = ($cell == "AR01") ? "highlight" : ""; // Example highlight for AR01
                    echo "<td class='$highlightClass'>$cell</td>";
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<!-- Chart display -->
<canvas id="customerChart"></canvas>
<canvas id="palletChart"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Biểu đồ cột
    var customerChartCtx = document.getElementById('customerChart').getContext('2d');
    var customerChart = new Chart(customerChartCtx, {
        type: 'bar',
        data: {
            labels: ['Becames IDC', 'EIU', 'SUS', 'SUA'],
            datasets: [{
                label: 'Customers',
                data: [5, 3, 2, 4],
                backgroundColor: 'rgba(255, 255, 255, 0.7)',
                borderColor: 'rgba(255, 255, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true },
                x: { ticks: { color: 'white' } },
                y: { ticks: { color: 'white' } }
            }
        }
    });

    // Biểu đồ tròn
    var palletChartCtx = document.getElementById('palletChart').getContext('2d');
    var palletChart = new Chart(palletChartCtx, {
        type: 'doughnut',
        data: {
            labels: ['Used Pallets', 'Available Pallets'],
            datasets: [{
                label: 'Pallets',
                data: [350, 1022], // Example data
                backgroundColor: ['#FFCC00', '#003366'],
                borderColor: '#FFFFFF',
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                legend: { labels: { color: 'white' } }
            }
        }
    });
</script>

</body>
</html>

