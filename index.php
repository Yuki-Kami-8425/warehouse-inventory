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

// Truy vấn tổng số pallet (1372 ô)
$total_slots = 1372;

// Truy vấn số khách hàng và số pallet của họ
$sql = "SELECT TENKH, COUNT(*) as pallet_count FROM dbo.stored_warehouse GROUP BY TENKH";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng cho dữ liệu biểu đồ
$customers = [];
$pallets = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $customers[] = $row['TENKH'];
    $pallets[] = $row['pallet_count'];
}

// Tính tổng số pallet đã lưu trữ
$total_pallets = array_sum($pallets);

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Warehouse Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* CSS để điều chỉnh màu sắc phù hợp với nền xanh dương đậm */
        body {
            background-color: #003366; /* Màu xanh dương đậm */
            color: white; /* Chữ trắng */
        }
        .chart-container {
            width: 25vw; /* 1/4 chiều rộng của màn hình */
            height: 25vw; /* Tỷ lệ tương ứng với chiều rộng */
            margin: 20px;
            display: inline-block;
        }
        h2 {
            color: white; /* Màu chữ trắng cho tiêu đề */
        }
        /* Điều chỉnh cho biểu đồ */
        .chartjs-render-monitor {
            border: 2px solid white; /* Viền trắng xung quanh biểu đồ */
        }
    </style>
</head>
<body>

<h2>Warehouse Statistics</h2>

<!-- Biểu đồ tròn: Tổng số pallet -->
<div class="chart-container">
    <canvas id="pieChart"></canvas>
</div>

<!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
<div class="chart-container">
    <canvas id="barChart"></canvas>
</div>

<script>
// Dữ liệu cho biểu đồ tròn
var totalPalletData = {
    datasets: [{
        data: [<?php echo $total_slots - $total_pallets; ?>, <?php echo $total_pallets; ?>],
        backgroundColor: ['#FF6384', '#36A2EB'], /* Màu sắc cho biểu đồ */
        borderColor: ['#FFFFFF', '#FFFFFF'], /* Viền trắng */
        borderWidth: 2
    }],
    labels: ['Empty Slots', 'Stored Pallets']
};

// Dữ liệu cho biểu đồ cột
var barChartData = {
    labels: <?php echo json_encode($customers); ?>,
    datasets: [{
        label: 'Pallets Stored',
        backgroundColor: '#36A2EB',
        borderColor: '#FFFFFF', /* Viền trắng */
        borderWidth: 2,
        data: <?php echo json_encode($pallets); ?>
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
                    color: 'white' /* Màu chữ trắng trong chú giải */
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
                    color: 'white' /* Màu chữ trắng trên trục X */
                }
            },
            y: {
                ticks: {
                    color: 'white' /* Màu chữ trắng trên trục Y */
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: 'white' /* Màu chữ trắng trong chú giải */
                }
            }
        }
    }
});
</script>

</body>
</html>
