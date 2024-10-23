<?php
// Kết nối đến cơ sở dữ liệu Azure SQL
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

// Hàm để lấy dữ liệu cho từng trạm
function getStationData($stationPrefix) {
    global $conn;
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$stationPrefix%'";
    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $data = [];
    $customers = [];
    $highlighted = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
        $customers[$row['MAKH']][] = $row['RFID'];
        $highlighted[] = trim($row['RFID']);
    }

    return [$data, $customers, $highlighted];
}

// Lấy dữ liệu cho từng trạm
list($dataA, $customersA, $highlightedA) = getStationData('A');
list($dataB, $customersB, $highlightedB) = getStationData('B');
// Lặp lại cho các trạm C, D, E, F, G nếu cần

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
    <style>
        body {
            background-color: #001F3F;
            color: white;
            font-size: 8px;
        }
        h2 {
            text-align: center;
            font-size: 24px;
        }
        .container {
            display: flex;
            justify-content: space-around;
            margin: 20px;
        }
        table {
            width: 30%;
            border-collapse: collapse;
            font-size: 8px;
        }
        th, td {
            border: 2px solid white;
            padding: 5px;
            text-align: center;
        }
        td.highlight {
            background-color: #32CD32;
        }
        .chart-container {
            width: 30%;
            margin: 20px;
        }
        .charts {
            display: flex;
            justify-content: space-around;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="navbar">
    <button onclick="showPage('all')">All</button>
    <button onclick="showPage('stationA')">Station A</button>
    <button onclick="showPage('stationB')">Station B</button>
    <!-- Thêm các nút cho các trạm khác nếu cần -->
</div>

<div id="all" class="page" style="display:none;">
    <h2>Warehouse - All Stations</h2>
    <!-- Hiển thị dữ liệu cho tất cả các trạm -->
</div>

<div id="stationA" class="page" style="display:none;">
    <h2>Warehouse Station A</h2>
    <div class="container">
        <!-- Bảng Left Rack Station A -->
        <table>
            <caption style="caption-side: top;">Left Rack</caption>
            <?php for ($row = 7; $row >= 1; $row--): ?>
                <tr>
                    <?php for ($col = 1; $col <= 14; $col++): ?>
                        <?php $index = ($row - 1) * 14 + $col; ?>
                        <td class="<?= in_array('AL' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlightedA) ? 'highlight' : '' ?>">AL<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>

        <!-- Bảng Right Rack Station A -->
        <table>
            <caption style="caption-side: top;">Right Rack</caption>
            <?php for ($row = 7; $row >= 1; $row--): ?>
                <tr>
                    <?php for ($col = 1; $col <= 14; $col++): ?>
                        <?php $index = ($row - 1) * 14 + $col; ?>
                        <td class="<?= in_array('AR' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlightedA) ? 'highlight' : '' ?>">AR<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>

    <div class="charts">
        <!-- Biểu đồ cột Station A -->
        <div class="chart-container">
            <canvas id="barChartA"></canvas>
        </div>

        <!-- Biểu đồ tròn Station A -->
        <div class="chart-container">
            <canvas id="pieChartA"></canvas>
        </div>
    </div>
</div>

<script>
    function showPage(page) {
        const pages = document.querySelectorAll('.page');
        pages.forEach(p => p.style.display = 'none');
        document.getElementById(page).style.display = 'block';

        if (page === 'stationA') {
            renderCharts('A', <?= json_encode($customersA) ?>, <?= count($highlightedA) ?>);
        }
    }

    function renderCharts(station, customers, filledSlots) {
        const customerLabels = Object.keys(customers);
        const customerData = customerLabels.map(key => customers[key].length);
        const totalSlots = 196;

        // Biểu đồ cột
        const ctxBar = document.getElementById('barChart' + station).getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: customerLabels,
                datasets: [{
                    label: 'Used Slots',
                    data: customerData,
                    backgroundColor: 'rgba(54, 162, 235, 1)',
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: 'white' } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Number of Used Slots', color: 'white' },
                        grid: { color: 'white' },
                        ticks: { color: 'white', stepSize: 1 }
                    },
                    x: {
                        grid: { color: 'white' },
                        ticks: { color: 'white' }
                    }
                }
            }
        });

        // Biểu đồ tròn
        const ctxPie = document.getElementById('pieChart' + station).getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Used', 'Remaining'],
                datasets: [{
                    data: [filledSlots, totalSlots - filledSlots],
                    backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                plugins: {
                    legend: { labels: { color: 'white' } }
                }
            }
        });
    }
</script>

</body>
</html>
