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

// Lấy dữ liệu từ bảng cho trạm A
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu
$data = [];
$customers = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
    $customers[$row['MAKH']] = $row['TENKH'];
}

// Đóng kết nối
sqlsrv_close($conn);

// Biến để xác định các ô đã được sử dụng
$highlighted = [];
foreach ($data as $item) {
    $highlighted[] = trim($item['RFID']); // Dùng trim để loại bỏ khoảng trắng
}
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
            text-align: center;
            font-size: 24px; /* Cỡ chữ tiêu đề lớn hơn */
        }
        caption {
            font-size: 16px; /* Cỡ chữ cho caption lớn hơn */
        }
        .container {
            display: flex; /* Sử dụng flexbox để bố trí các phần tử */
            justify-content: space-around; /* Căn giữa các bảng */
            margin: 20px; /* Giãn cách giữa các bảng và biểu đồ */
        }
        table {
            width: 30%; /* Mỗi bảng chiếm 30% màn hình */
            border-collapse: collapse;
            font-size: 8px; /* Kích thước chữ trong bảng */
        }
        th, td {
            border: 2px solid white; /* Đường viền trắng */
            padding: 5px; /* Padding cho ô */
            text-align: center;
        }
        td.highlight {
            background-color: #32CD32; /* Màu xanh lục cho ô được highlight */
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
        <?php for ($row = 14; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 7; $col++): ?>
                    <?php $index = ($row - 1) * 7 + $col; ?>
                    <td class="<?= in_array('AL' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AL<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    <!-- Bảng Right Rack -->
    <table>
        <caption style="caption-side: top;">Right Rack</caption>
        <?php for ($row = 14; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 7; $col++): ?>
                    <?php $index = ($row - 1) * 7 + $col; ?>
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
    const customerCount = Object.keys(customers).length; // Số khách hàng
    const totalSlots = 196; // Tổng số ô (98x2)
    const filledSlots = <?= count($highlighted) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: Object.values(customers), // Tên khách hàng
            datasets: [{
                label: 'Số lượng pallet',
                data: <?= json_encode(array_column($data, 'LUONG_PALLET')) ?>, // Lượng pallet
                backgroundColor: 'rgba(54, 162, 235, 1)', // Màu lam tươi
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // Màu chữ trắng cho legend
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'white' // Màu đường lưới trắng
                    },
                    ticks: {
                        stepSize: 1, // Đơn vị trong biểu đồ cột
                        color: 'white' // Màu chữ số trục y
                    }
                },
                x: {
                    grid: {
                        color: 'white' // Màu đường lưới trắng
                    },
                    ticks: {
                        color: 'white' // Màu chữ tên khách hàng
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
            labels: ['Đã sử dụng', 'Còn lại'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Màu đỏ và xanh
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // Màu chữ trắng cho legend
                    }
                }
            }
        }
    });
</script>

</body>
</html>
