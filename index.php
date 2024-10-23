<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="scripts.js"></script>
</head>
<body>
    <div class="sidebar">
        <!-- Thanh điều hướng bên với các nút -->
        <button onclick="showStation('all')">All</button>
        <button onclick="showStation('A')">Trạm A</button>
        <button onclick="showStation('B')">Trạm B</button>
        <button onclick="showStation('C')">Trạm C</button>
        <button onclick="showStation('D')">Trạm D</button>
        <button onclick="showStation('E')">Trạm E</button>
        <button onclick="showStation('F')">Trạm F</button>
        <button onclick="showStation('G')">Trạm G</button>
    </div>

    <div class="content">
        <h1>Warehouse Overview</h1>
        <?php
        // Kết nối cơ sở dữ liệu
        $serverName = "eiusmartwarehouse.database.windows.net";
        $connectionOptions = array(
            "Database" => "eiu_warehouse_24",
            "Uid" => "eiuadmin",
            "PWD" => "Khoa123456789"
        );

        // Thiết lập kết nối
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Xử lý nội dung dựa trên trạm được chọn
        $station = isset($_GET['station']) ? $_GET['station'] : 'A'; // Mặc định là trạm A

        if ($station == 'all') {
            // Hiển thị tất cả các trạm
            echo "<h2>All Stations</h2>";
            $tsql = "SELECT * FROM stored_warehouse";
            $getResults = sqlsrv_query($conn, $tsql);
            if ($getResults === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            echo "<table border='1'>";
            echo "<tr><th>Position</th><th>Customer</th><th>Quantity</th></tr>";
            while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
                echo "<tr><td>" . $row['rack_position'] . "</td><td>" . $row['TENKH'] . "</td><td>" . $row['LUONG_PALLET'] . "</td></tr>";
            }
            echo "</table>";
        } else {
            // Hiển thị dữ liệu của từng trạm
            echo "<h2>Station " . htmlspecialchars($station) . "</h2>";
            
            // Truy vấn thông tin cho từng trạm A-G
            $tsql = "SELECT * FROM stored_warehouse WHERE rack_position LIKE '" . $station . "%'";
            $getResults = sqlsrv_query($conn, $tsql);
            if ($getResults === false) {
                die(print_r(sqlsrv_errors(), true));
            }

            echo "<table border='1'>";
            echo "<tr><th>Position</th><th>Customer</th><th>Quantity</th></tr>";
            while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
                echo "<tr><td>" . $row['rack_position'] . "</td><td>" . $row['TENKH'] . "</td><td>" . $row['LUONG_PALLET'] . "</td></tr>";
            }
            echo "</table>";

            // Biểu đồ cột cho số lượng khách hàng
            echo "<canvas id='barChart_" . $station . "'></canvas>";
            echo "<script>
                var ctx = document.getElementById('barChart_" . $station . "').getContext('2d');
                var barChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Customer 1', 'Customer 2', 'Customer 3'], // Thay bằng dữ liệu thực tế
                        datasets: [{
                            label: 'Quantity',
                            data: [10, 20, 30], // Thay bằng dữ liệu thực tế
                            backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>";

            // Biểu đồ tròn cho tổng số kho
            echo "<canvas id='pieChart_" . $station . "'></canvas>";
            echo "<script>
                var ctx = document.getElementById('pieChart_" . $station . "').getContext('2d');
                var pieChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Occupied', 'Available'],
                        datasets: [{
                            data: [50, 146], // Thay bằng dữ liệu thực tế (50 là số lượng đã chiếm)
                            backgroundColor: ['#FF6384', '#36A2EB']
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            </script>";
        }
        ?>
    </div>

    <script>
        // Hàm JavaScript chuyển hướng đến trạm tương ứng khi nhấn nút
        function showStation(station) {
            window.location.href = 'index.php?station=' + station;
        }
    </script>
</body>
</html>
