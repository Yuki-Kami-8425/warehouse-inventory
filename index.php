<?php 
// Database connection information for Azure SQL
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

// Connect to the database
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Check connection
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Retrieve data for station A
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Check for query error
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Create array to store data
$data = [];
$customers = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
    $customers[$row['MAKH']] = $row['TENKH'];
}

// Close connection
sqlsrv_close($conn);

// Variable to identify used slots
$highlighted = [];
foreach ($data as $item) {
    $highlighted[] = $item['RFID'];
}

// Calculate pallet counts for each customer
$palletCounts = array_count_values(array_column($data, 'MAKH'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - Station A</title>
    <style>
        body {
            background-color: #001F3F; /* Dark Blue */
            color: white; /* White text color */
            font-size: 8px; /* Font size */
        }
        h2 {
            text-align: center;
            font-size: 20px; /* Larger font size for title */
        }
        .container {
            display: flex; /* Use flexbox for layout */
            justify-content: space-around; /* Center tables */
            margin: 20px; /* Spacing between tables and charts */
        }
        table {
            width: 30%; /* Each table occupies 30% of the screen */
            border-collapse: collapse;
            font-size: 8px; /* Font size in tables */
        }
        th, td {
            border: 2px solid white; /* White border */
            padding: 5px; /* Padding for cells */
            text-align: center;
        }
        td.highlight {
            background-color: #ADD8E6; /* Light Blue for highlighted cells */
        }
        .chart-container {
            width: 30%; /* 30% screen for charts */
            margin: 20px; /* Spacing between charts */
        }
        .charts {
            display: flex; /* Place 2 charts side by side */
            justify-content: space-around; /* Center charts */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Station A</h2>

<div class="container">
    <!-- Left Rack Table -->
    <table>
        <caption style="caption-side: top;">Left Rack</caption>
        <?php for ($i = 14; $i >= 1; $i--): ?>
            <tr>
                <?php for ($j = 0; $j < 7; $j++): ?>
                    <?php $index = ($i - 1) * 7 + $j + 1; ?>
                    <td class="<?= in_array('AL' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AL<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>

    <!-- Right Rack Table -->
    <table>
        <caption style="caption-side: top;">Right Rack</caption>
        <?php for ($i = 14; $i >= 1; $i--): ?>
            <tr>
                <?php for ($j = 0; $j < 7; $j++): ?>
                    <?php $index = ($i - 1) * 7 + $j + 1; ?>
                    <td class="<?= in_array('AR' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AR<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>
</div>

<!-- Charts -->
<div class="charts">
    <!-- Bar Chart -->
    <div class="chart-container">
        <canvas id="barChart"></canvas>
    </div>

    <!-- Pie Chart -->
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>
</div>

<script>
    // Chart data
    const customers = <?= json_encode($customers) ?>;
    const totalSlots = 196; // Total slots (98x2)
    const filledSlots = <?= count($highlighted) ?>; // Number of used slots
    const palletCounts = <?= json_encode($palletCounts) ?>; // Pallet counts for each customer
    const customerNames = Object.keys(customers); // Customer names

    // Bar Chart
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: customerNames, // Customer names
            datasets: [{
                label: 'Pallet Count',
                data: Object.values(palletCounts), // Pallet counts
                backgroundColor: 'rgba(54, 162, 235, 1)', // Bright blue
                borderColor: 'white', // White border
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // White text color for legend
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'white' // White grid lines
                    },
                    ticks: {
                        stepSize: 1 // Units in bar chart
                    }
                },
                x: {
                    grid: {
                        color: 'white' // White grid lines
                    }
                }
            }
        }
    });

    // Pie Chart
    const ctxPie = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Used', 'Available'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Red and blue
                borderColor: 'white', // White border
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // White text color for legend
                    }
                }
            }
        }
    });
</script>

</body>
</html>
