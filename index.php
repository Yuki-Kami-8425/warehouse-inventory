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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #003366;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            color: white;
            font-size: 2.5rem;
            text-align: center;
            margin: 20px 0;
        }
        .chart-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }
        .chart-container {
            width: 35vw;
            height: 35vw;
            display: inline-block;
        }
        .chartjs-render-monitor {
            border: 2px solid white;
        }
        @media (max-width: 350px) {
            .chart-container {
                width: 80vw;
                height: 80vw;
            }
        }
    </style>
</head>
<body>
    <div id="all" class="page" style="display:block;">
        <h2>Warehouse Statistics</h2>
        <div class="chart-row">
            <div class="chart-container"> <canvas id="pieChart_all"></canvas> </div>
            <div class="chart-container"> <canvas id="barChart_all"></canvas> </div>
        </div>

        <script>
            // Dữ liệu cho biểu đồ tròn
            var totalPalletData = {
                datasets: [{
                    data: [<?php echo $total_slots - $total_pallets; ?>, <?php echo $total_pallets; ?>],
                    backgroundColor: ['#FF6384', '#36A2EB'],
                    borderColor: ['#FFFFFF', '#FFFFFF'],
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
                    borderColor: '#FFFFFF',
                    borderWidth: 2,
                    data: <?php echo json_encode($pallets); ?>
                }]
            };

            // Vẽ biểu đồ tròn
            var ctx1 = document.getElementById('pieChart_all').getContext('2d');
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
            var ctx2 = document.getElementById('barChart_all').getContext('2d');
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
        </script>
    </div>

    <script src="script.js"></script>
</body>
</html>
