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

// Lấy danh sách khách hàng ở trạm A
$sqlCustomers = "SELECT DISTINCT TENKH FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmtCustomers = sqlsrv_query($conn, $sqlCustomers);
$customerCount = 0;

$customerNames = []; // Mảng chứa tên khách hàng

if ($stmtCustomers !== false) {
    while ($row = sqlsrv_fetch_array($stmtCustomers, SQLSRV_FETCH_ASSOC)) {
        $customerNames[] = $row['TENKH'];
        $customerCount++;
    }
}

// Tính số ô đã sử dụng
$occupiedSlots = 0;
$sqlOccupied = "SELECT COUNT(*) AS occupied_count FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmtOccupied = sqlsrv_query($conn, $sqlOccupied);
if ($stmtOccupied !== false) {
    $row = sqlsrv_fetch_array($stmtOccupied, SQLSRV_FETCH_ASSOC);
    $occupiedSlots = $row['occupied_count'];
}

// Tính tổng số ô
$totalSlots = 196;

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Warehouse A Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #003366; /* Màu nền xanh đậm */
            color: white; /* Màu chữ trắng */
            font-family: Arial, sans-serif;
        }
        .table-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        table {
            width: 45%;
            margin: 10px;
            border-collapse: collapse;
            font-size: 8px; /* Thay đổi kích thước chữ trong bảng */
        }
        th, td {
            padding: 5px;
            text-align: center;
            border: 1px solid white; /* Viền trắng */
        }
        .highlight {
            background-color: #ffcc00; /* Màu high-light cho ô có RFID */
        }
        canvas {
            width: 40%; /* Thay đổi kích thước biểu đồ */
            height: 200px; /* Chiều cao biểu đồ */
        }
    </style>
</head>
<body>

<h2>Warehouse A Statistics</h2>

<div class="table-container">
    <div>
        <h3>Left Rack</h3>
        <table>
            <?php
            for ($i = 14; $i >= 1; $i--) { // Duyệt từ 14 xuống 1
                echo "<tr>";
                for ($j = 1; $j <= 7; $j++) {
                    $slotNumber = sprintf("AL%02d", ($i - 1) * 7 + $j); // Tạo số ô từ AL01 đến AL98
                    $highlightClass = in_array($slotNumber, ['AL01', 'AL02']) ? 'highlight' : ''; // Thay 'AL01', 'AL02' bằng các ô cần phát sáng
                    echo "<td class='$highlightClass'>$slotNumber</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
    <div>
        <h3>Right Rack</h3>
        <table>
            <?php
            for ($i = 14; $i >= 1; $i--) { // Duyệt từ 14 xuống 1
                echo "<tr>";
                for ($j = 1; $j <= 7; $j++) {
                    $slotNumber = sprintf("AR%02d", ($i - 1) * 7 + $j); // Tạo số ô từ AR01 đến AR98
                    $highlightClass = in_array($slotNumber, ['AR01', 'AR02']) ? 'highlight' : ''; // Thay 'AR01', 'AR02' bằng các ô cần phát sáng
                    echo "<td class='$highlightClass'>$slotNumber</td>";
                }
                echo "</tr>";
            }
            ?>
        </table>
    </div>
</div>

<div class="chart-container">
    <canvas id="barChart"></canvas>
    <canvas id="pieChart"></canvas>
</div>

<script>
    // Biểu đồ cột (Bar Chart)
    var ctxBar = document.getElementById('barChart').getContext('2d');
    var barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($customerNames); ?>,
            datasets: [{
                label: 'Số lượng khách hàng',
                data: Array(<?php echo $customerCount; ?>).fill(1), // Chỉ hiển thị số lượng 1 cho mỗi khách hàng
                backgroundColor: 'rgba(0, 123, 255, 0.5)',
                borderColor: 'white',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng'
                    }
                }
            }
        }
    });

    // Biểu đồ tròn (Pie Chart)
    var ctxPie = document.getElementById('pieChart').getContext('2d');
    var pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Đã sử dụng', 'Còn lại'],
            datasets: [{
                label: 'Số lượng kho hàng',
                data: [<?php echo $occupiedSlots; ?>, <?php echo $totalSlots - $occupiedSlots; ?>],
                backgroundColor: ['#FF6384', '#36A2EB'], // Màu sắc biểu đồ
                borderColor: 'white',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

</body>
</html>
