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
        /* CSS để điều chỉnh màu sắc và bố cục biểu đồ */
        body {
            background-color: #003366; /* Màu xanh dương đậm */
            color: white; /* Chữ trắng */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .chart-row {
            display: flex;
            justify-content: center; /* Căn lề ở giữa theo chiều ngang */
            gap: 20px; /* Khoảng cách giữa hai biểu đồ */
            margin-top: 30px;
        }

        .chart-container {
            width: 40vw; /* Đặt 40% chiều rộng màn hình cho mỗi biểu đồ */
            height: 40vw; /* Đảm bảo tỉ lệ vuông */
            display: inline-block;
        }

        h2 {
            color: white; /* Màu chữ trắng cho tiêu đề */
        }

        /* Điều chỉnh cho biểu đồ */
        .chartjs-render-monitor {
            border: 2px solid white; /* Viền trắng xung quanh biểu đồ */
        }

        /* Responsive: Biểu đồ sẽ thu nhỏ lại trên màn hình nhỏ */
        @media (max-width: 450px) {
            .chart-container {
                width: 80vw; /* Chiều rộng lớn hơn cho màn hình nhỏ */
                height: 80vw; /* Điều chỉnh chiều cao theo tỷ lệ */
            }
        }
    </style>
</head>
<body>

<h2>Warehouse Statistics</h2>

<div class="chart-row">
    <!-- Biểu đồ tròn: Tổng số pallet -->
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>

    <!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
    <div class="chart-container">
        <canvas id="barChart"></canvas>
    </div>
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
                },
                grid: {
                    display: false /* Ẩn các đường kẻ trên trục X */
                }
            },
            y: {
                ticks: {
                    color: 'white' /* Màu chữ trắng trên trục Y */
                },
                grid: {
                    color: 'rgba(255, 255, 255, 0.2)' /* Đường kẻ mờ nhạt hơn trên trục Y */
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
