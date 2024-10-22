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
<html>
<head>
    <title>Station A Rack</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #003366; /* Nền xanh đậm */
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .rack-container {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin: 20px 0;
        }

        .rack {
            display: grid;
            grid-template-columns: repeat(7, 1fr); /* 7 cột */
            grid-template-rows: repeat(14, 1fr); /* 14 hàng */
            gap: 5px;
            width: 40%;
        }

        .slot {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 10px;
            background-color: #666666; /* Màu xám cho các ô */
            border: 1px solid white;
            font-size: 12px;
        }

        .highlight {
            background-color: #FF6347; /* Màu đỏ cam để high-light */
        }

        /* Bố trí biểu đồ */
        .chart-row {
            display: flex;
            justify-content: space-around;
            width: 100%;
            margin-top: 20px;
        }

        .chart-container {
            width: 40vw;
            height: 40vw;
        }

        /* Responsive cho màn hình nhỏ hơn */
        @media (max-width: 768px) {
            .rack-container, .chart-row {
                flex-direction: column;
                align-items: center;
            }
            .chart-container {
                width: 80vw;
                height: 80vw;
            }
        }
    </style>
</head>
<body>

<h2>Station A Rack Layout</h2>

<div class="rack-container">
    <!-- Left Rack -->
    <div class="rack">
        <?php
        // Hiển thị 14 ô từ A01 đến A14 cho rack trái
        for ($i = 1; $i <= 14; $i++) {
            $rfid = sprintf("AL%02d", $i);
            $highlight = in_array($rfid, $rfids) ? 'highlight' : '';
            echo "<div class='slot $highlight'>$rfid</div>";
        }
        ?>
    </div>

    <!-- Right Rack -->
    <div class="rack">
        <?php
        // Hiển thị 14 ô từ A98 đến A85 cho rack phải
        for ($i = 98; $i >= 85; $i--) {
            $rfid = sprintf("AR%02d", $i);
            $highlight = in_array($rfid, $rfids) ? 'highlight' : '';
            echo "<div class='slot $highlight'>$rfid</div>";
        }
        ?>
    </div>
</div>

<div class="chart-row">
    <!-- Biểu đồ cột: Số lượng pallet theo khách hàng trong trạm A -->
    <div class="chart-container">
        <canvas id="barChart"></canvas>
    </div>

    <!-- Biểu đồ tròn: Tổng số pallet trong trạm A -->
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>
</div>

<script>
// Dữ liệu cho biểu đồ tròn
var pieData = {
    datasets: [{
        data: [<?php echo 98 - $total_pallets; ?>, <?php echo $total_pallets; ?>],
        backgroundColor: ['#FF6384', '#36A2EB'],
        borderColor: ['#FFFFFF', '#FFFFFF'],
        borderWidth: 2
    }],
    labels: ['Empty Slots', 'Stored Pallets']
};

// Dữ liệu cho biểu đồ cột
var barData = {
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
var ctx1 = document.getElementById('pieChart').getContext('2d');
var pieChart = new Chart(ctx1, {
    type: 'pie',
    data: pieData,
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
var ctx2 = document.getElementById('barChart').getContext('2d');
var barChart = new Chart(ctx2, {
    type: 'bar',
    data: barData,
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

</body>
</html>
