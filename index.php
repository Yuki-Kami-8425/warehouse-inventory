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

// Tạo mảng để lưu dữ liệu cho các trạm
$stations = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
$stationData = [];

// Lấy dữ liệu cho từng trạm
foreach ($stations as $station) {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$station%'";
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

    $stationData[$station] = [
        'data' => $data,
        'customers' => $customers,
        'highlighted' => $highlighted,
    ];
}

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
            background-color: #001F3F; /* Xanh đậm */
            color: white; /* Màu chữ trắng */
            font-size: 8px; /* Kích thước chữ */
        }
        h2 {
            text-align: center;
            font-size: 24px; /* Cỡ chữ tiêu đề lớn hơn */
        }
        .button-container {
            text-align: center;
            margin: 20px;
        }
        .station-button, .all-button {
            margin: 5px;
            padding: 10px;
            font-size: 16px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
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

<h2>Warehouse Management</h2>

<div class="button-container">
    <button class="all-button" onclick="showAll()">All</button>
    <?php foreach ($stations as $station): ?>
        <button class="station-button" onclick="showStation('<?= $station ?>')">Station <?= $station ?></button>
    <?php endforeach; ?>
</div>

<div id="stationContent"></div>

<script>
    const stationData = <?= json_encode($stationData) ?>;

    function showAll() {
        const content = document.getElementById('stationContent');
        content.innerHTML = `
            <h2>All Stations</h2>
            <div class="container">
                <table>
                    <caption style="caption-side: top;">Left Rack</caption>
                    ${generateTableForAll('L')}
                </table>
                <table>
                    <caption style="caption-side: top;">Right Rack</caption>
                    ${generateTableForAll('R')}
                </table>
            </div>
            <div class="charts">
                <div class="chart-container">
                    <canvas id="barChartAll"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="pieChartAll"></canvas>
                </div>
            </div>
        `;

        drawAllCharts();
    }

    function showStation(station) {
        const content = document.getElementById('stationContent');
        const data = stationData[station];

        content.innerHTML = `
            <h2>Warehouse Station ${station}</h2>
            <div class="container">
                <table>
                    <caption style="caption-side: top;">Left Rack</caption>
                    ${generateTable(data.highlighted, station, 'L')}
                </table>
                <table>
                    <caption style="caption-side: top;">Right Rack</caption>
                    ${generateTable(data.highlighted, station, 'R')}
                </table>
            </div>
            <div class="charts">
                <div class="chart-container">
                    <canvas id="barChart${station}"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="pieChart${station}"></canvas>
                </div>
            </div>
        `;

        // Gọi hàm để vẽ biểu đồ
        drawCharts(data.customers, station);
    }

    function generateTableForAll(side) {
        let html = '';
        const rows = 7;
        const cols = 14;
        let highlightedAll = new Set();

        for (const station of Object.keys(stationData)) {
            highlightedAll = new Set([...highlightedAll, ...stationData[station].highlighted]);
        }

        for (let row = rows; row >= 1; row--) {
            html += '<tr>';
            for (let col = 1; col <= cols; col++) {
                const index = (row - 1) * cols + col;
                const cellId = side === 'L' ? `AL${String(index).padStart(2, '0')}` : `AR${String(index).padStart(2, '0')}`;
                html += `<td class="${highlightedAll.has(cellId) ? 'highlight' : ''}">${cellId}</td>`;
            }
            html += '</tr>';
        }
        return html;
    }

    function generateTable(highlighted, station, side) {
        let html = '';
        const rows = 7;
        const cols = 14;
        for (let row = rows; row >= 1; row--) {
            html += '<tr>';
            for (let col = 1; col <= cols; col++) {
                const index = (row - 1) * cols + col;
                const cellId = side === 'L' ? `AL${String(index).padStart(2, '0')}` : `AR${String(index).padStart(2, '0')}`;
                html += `<td class="${highlighted.includes(cellId) ? 'highlight' : ''}">${cellId}</td>`;
            }
            html += '</tr>';
        }
        return html;
    }

    function drawAllCharts() {
        const totalCustomers = {};
        const filledSlots = {};
        const totalSlots = 196;

        // Tính toán dữ liệu cho biểu đồ tổng
        for (const station of Object.keys(stationData)) {
            const data = stationData[station].customers;
            for (const customer in data) {
                totalCustomers[customer] = (totalCustomers[customer] || 0) + data[customer].length;
            }
            filledSlots[station] = Object.keys(data).length;
        }

        const customerLabels = Object.keys(totalCustomers);
        const customerData = customerLabels.map(key => totalCustomers[key]);
        const totalFilledSlots = Object.values(filledSlots).reduce((total, num) => total + num, 0);

        // Biểu đồ cột
        const ctxBar = document.getElementById('barChartAll').getContext('2d');
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
                    legend: { display: true },
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Biểu đồ tròn
        const ctxPie = document.getElementById('pieChartAll').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: Object.keys(filledSlots),
                datasets: [{
                    label: 'Filled Slots',
                    data: Object.values(filledSlots),
                    backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)', 'rgba(255, 255, 255, 0.2)'],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                }
            }
        });
    }

    function drawCharts(customers, station) {
        const customerLabels = Object.keys(customers);
        const customerData = customerLabels.map(key => customers[key].length);

        // Biểu đồ cột
        const ctxBar = document.getElementById(`barChart${station}`).getContext('2d');
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
                    legend: { display: true },
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Biểu đồ tròn
        const ctxPie = document.getElementById(`pieChart${station}`).getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: Object.keys(customers),
                datasets: [{
                    label: 'Filled Slots',
                    data: customerData,
                    backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)', 'rgba(255, 159, 64, 0.2)'],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                }
            }
        });
    }
</script>

</body>
</html>
