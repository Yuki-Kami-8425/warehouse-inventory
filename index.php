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
$sql = "SELECT DISTINCT MAKH, COUNT(MAKH) as Count FROM dbo.stored_warehouse WHERE RFID LIKE 'A%' GROUP BY MAKH";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Lấy tên khách hàng và số lượng
$customers = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $customers[$row['MAKH']] = $row['Count'];
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
        h2 {
            font-size: 18px; /* Kích thước chữ cho tiêu đề */
            text-align: center;
        }
        .container {
            display: flex; /* Sử dụng flexbox để bố trí các phần tử */
            justify-content: space-around; /* Căn giữa các bảng */
            margin: 20px; /* Giãn cách giữa các bảng và biểu đồ */
        }
        table {
            width: 30%; /* Mỗi bảng chiếm 30% màn hình */
            border-collapse: collapse;
        }
        th, td {
            border: 2px solid white; /* Đường viền trắng */
            padding: 5px; /* Giảm padding để bảng nhỏ hơn */
            text-align: center;
            font-size: 8px; /* Kích thước chữ trong bảng */
        }
        td.highlight {
            background-color: #FFD700; /* Màu vàng cho ô được highlight */
        }
        .chart-container {
            width: 30%; /* Chiếm 30% màn hình cho biểu đồ */
            margin: 20px; /* Giãn cách giữa các biểu đồ */
        }
        .charts {
            display: flex; /* Bố trí 2 biểu đồ nằm ngang */
            justify-content: space-around; /* Căn giữa các biểu đồ */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Station A</h2>

<div class="container">
    <!-- Bảng Left Rack -->
    <table>
        <caption style="caption-side: top;">Left Rack</caption>
        <tr>
            <td class="<?= isset($customers['BIDC']) ? 'highlight' : '' ?>">AL01</td>
            <td>AL02</td>
            <td>AL03</td>
            <td class="<?= isset($customers['BIDC']) ? 'highlight' : '' ?>">AL04</td>
            <td>AL05</td>
            <td>AL06</td>
            <td>AL07</td>
            <td>AL08</td>
            <td>AL09</td>
            <td>AL10</td>
        </tr>
        <!-- ... các hàng tiếp theo tương tự ... -->
    </table>

    <!-- Bảng Right Rack -->
    <table>
        <caption style="caption-side: top;">Right Rack</caption>
        <tr>
            <td class="<?= isset($customers['BIDC']) ? 'highlight' : '' ?>">AR01</td>
            <td>AR02</td>
            <td>AR03</td>
            <td>AR04</td>
            <td>AR05</td>
            <td>AR06</td>
            <td>AR07</td>
            <td>AR08</td>
            <td>AR09</td>
            <td>AR10</td>
        </tr>
        <!-- ... các hàng tiếp theo tương tự ... -->
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
    const customers = <?= json_encode($customers) ?>; // Khách hàng và số lượng
    const totalSlots = 196; // Tổng số ô
    const filledSlots = <?= count(array_filter(array_keys($customers), fn($c) => str_starts_with($c, 'A'))) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: Object.keys(customers), // Tên khách hàng
            datasets: [{
                label: 'Number of Customers',
                data: Object.values(customers), // Số lượng khách hàng
                backgroundColor: 'rgba(54, 162, 235, 1)', // Màu lam tươi
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1 // Chia đơn vị là 1
                    },
                    grid: {
                        color: 'white' // Màu đường lưới trắng
                    }
                },
                x: {
                    grid: {
                        color: 'white' // Màu đường lưới trắng
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
            labels: ['Filled', 'Available'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Màu đỏ và xanh
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        }
    });
</script>

</body>
</html>
