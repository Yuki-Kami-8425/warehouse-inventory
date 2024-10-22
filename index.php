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
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 20px;
            width: 100vw;
            height: 100vh;
            padding: 10px;
            box-sizing: border-box;
        }
        table {
            border-collapse: collapse;
            margin: 10px;
            width: 100%;
            height: 100%;
        }
        th, td {
            padding: 8px;
            border: 1px solid white;
            text-align: center;
            font-size: 12px;
        }
        .highlight {
            background-color: yellow; /* Màu phát sáng cho ô tìm thấy */
            color: black;
        }
        canvas {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>

<h1>Warehouse Station A - Rack Display</h1>

<div class="grid-container">
    <!-- Left Rack -->
    <div>
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
    </div>

    <!-- Right Rack -->
    <div>
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

    <!-- Customer Bar Chart -->
    <div>
        <canvas id="customerChart"></canvas>
    </div>

    <!-- Pallet Pie Chart -->
    <div>
        <canvas id="palletChart"></canvas>
    </div>
</div>

<?php
// Truy vấn số khách hàng trong kho và lượng pallet
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array("Database" => "eiu_warehouse_24", "Uid" => "eiuadmin", "PWD" => "Khoa123456789");
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$sql = "SELECT TENKH, COUNT(*) AS pallet_count FROM dbo.stored_warehouse GROUP BY TENKH";
$stmt = sqlsrv_query($conn, $sql);

$customers = [];
$pallets = [];
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $customers[] = $row['TENKH'];
        $pallets[] = $row['pallet_count'];
    }
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dữ liệu khách hàng và pallet từ PHP
    var customerData = <?php echo json_encode($customers); ?>;
    var palletData = <?php echo json_encode($pallets); ?>;

    // Biểu đồ cột
    var customerChartCtx = document.getElementById('customerChart').getContext('2d');
    var customerChart = new Chart(customerChartCtx, {
        type: 'bar',
        data: {
            labels: customerData,
            datasets: [{
                label: 'Pallets',
                data: palletData,
                backgroundColor: 'rgba(0, 123, 255, 0.7)', /* Màu xanh */
                borderColor: 'rgba(255, 255, 255, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: 'white' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                },
                x: {
                    ticks: { color: 'white' },
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }
                }
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
                data: [350, 1022], // Example data
                backgroundColor: ['#FFCC00', '#003366'], /* Vàng và Xanh */
                borderColor: '#FFFFFF',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: { color: 'white' }
                }
            }
        }
    });
</script>

</body>
</html>
