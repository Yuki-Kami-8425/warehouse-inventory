<?php
// Database connection information
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789" // Consider using environment variables for security
);

// Connect to the database
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Query total pallet (1372 slots)
$total_slots = 1372;

// Query customer count and pallets
$sql = "SELECT TENKH, COUNT(*) as pallet_count FROM dbo.stored_warehouse GROUP BY TENKH";
$stmt = sqlsrv_query($conn, $sql);

// Check query errors
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Prepare data for the chart
$customers = [];
$pallets = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $customers[] = $row['TENKH'];
    $pallets[] = $row['pallet_count'];
}

// Calculate total pallets stored
$total_pallets = array_sum($pallets);

// Close the database connection
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
    <title>Warehouse Dashboard</title>
    <style>
        /* Add your CSS here or link to an external stylesheet */
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <ul>
            <li><a href="#" onclick="showPage('home');" class="main-link"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
            <li>
                <a href="#" onclick="toggleStations(); showPage('dashboard');" class="main-link">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="link-text"> Dashboard</span>
                </a>
                <ul class="station-list">
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <li><a href="#" onclick="showPage('station<?= $i; ?>');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station <?= $i; ?></span></a></li>
                    <?php endfor; ?>
                </ul>
            </li>
            <li><a href="#" onclick="showPage('list-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> List</span></a></li>
        </ul>
        <div id="datetime" class="datetime"></div>
    </div>

    <div class="content">
        <div id="home" class="page">
            <div class="slideshow-container">
                <!-- Slideshow content -->
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 1</h2>
                    <img class="slide-image" src="Picture1.png" alt="Slide 1">
                </div>
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 2</h2>
                    <img class="slide-image" src="Picture2.png" alt="Slide 2">
                </div>
                <div class="slide">
                    <h2 class="slide-title">Tiêu đề cho Hình 3</h2>
                    <img class="slide-image" src="Picture3.png" alt="Slide 3">
                </div>
                <div class="dots">
                    <span class="dot" onclick="showSlide(1)"></span>
                    <span class="dot" onclick="showSlide(2)"></span>
                    <span class="dot" onclick="showSlide(3)"></span>
                </div>
            </div>
        </div>

        <div id="dashboard" class="page" style="display:none;">
            <!-- Dashboard content -->
        </div>

        <div id="list-warehouse" class="page" style="display:none;">List Warehouse will be here.</div>

        <div id="all" class="page" style="display:none;">
            <h2>Warehouse Statistics</h2>
            <div class="chart-row">
                <div class="chart-container"><canvas id="pieChart_all"></canvas></div>
                <div class="chart-container"><canvas id="barChart_all"></canvas></div>
            </div>
            <script>
                const totalPalletData = {
                    datasets: [{
                        data: [<?php echo $total_slots - $total_pallets; ?>, <?php echo $total_pallets; ?>],
                        backgroundColor: ['#FF6384', '#36A2EB'],
                        borderColor: ['#FFFFFF', '#FFFFFF'],
                        borderWidth: 2
                    }],
                    labels: ['Empty Slots', 'Stored Pallets']
                };

                const barChartData = {
                    labels: <?php echo json_encode($customers); ?>,
                    datasets: [{
                        label: 'Pallets Stored',
                        backgroundColor: '#36A2EB',
                        borderColor: '#FFFFFF',
                        borderWidth: 2,
                        data: <?php echo json_encode($pallets); ?>
                    }]
                };

                function renderCharts() {
                    const ctx1 = document.getElementById('pieChart_all').getContext('2d');
                    new Chart(ctx1, {
                        type: 'pie',
                        data: totalPalletData,
                        options: {
                            plugins: {
                                legend: { labels: { color: 'white' } }
                            }
                        }
                    });

                    const ctx2 = document.getElementById('barChart_all').getContext('2d');
                    new Chart(ctx2, {
                        type: 'bar',
                        data: barChartData,
                        options: {
                            scales: {
                                x: { ticks: { color: 'white' }, grid: { display: false } },
                                y: { ticks: { color: 'white' }, grid: { color: 'rgba(255, 255, 255, 0.2)' } }
                            },
                            plugins: {
                                legend: { labels: { color: 'white' } }
                            }
                        }
                    });
                }

                // Call renderCharts() when the page is shown
                window.onload = renderCharts;
            </script>
        </div>

        <?php for ($i = 1; $i <= 7; $i++): ?>
            <div id="station<?= $i; ?>" class="page" style="display:none;">
                Station <?= $i; ?> content will be here.
            </div>
        <?php endfor; ?>
    </div>

    <script src="script.js"></script>
</body>
</html>
