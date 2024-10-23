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
                        <li><a href="#" onclick="showPage('all');" class="station-link"><i class="fas fa-th-list"></i> <span class="link-text">All</span></a></li>
                        <li><a href="#" onclick="showPage('station1');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 1</span></a></li>
                        <li><a href="#" onclick="showPage('station2');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 2</span></a></li>
                        <li><a href="#" onclick="showPage('station3');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 3</span></a></li>
                        <li><a href="#" onclick="showPage('station4');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 4</span></a></li>
                        <li><a href="#" onclick="showPage('station5');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 5</span></a></li>
                        <li><a href="#" onclick="showPage('station6');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 6</span></a></li>
                        <li><a href="#" onclick="showPage('station7');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 7</span></a></li>
                    </ul>
                </li>
                <li><a href="#" onclick="showPage('list-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> List</span></a></li>
            </ul>

            <div id="datetime" class="datetime"></div>
        </div>

        <div class="content">
            <div id="home" class="page">
                <div class="slideshow-container">
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
                
            </div>
            <div id="edit-warehouse" class="page" style="display:none;">List Warehouse will be here.</div>
            <div id="all" class="page" style="display:none;">
                <head>
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

                            h2 {
                                color: white; /* Màu chữ trắng */
                                font-size: 2.5rem; /* Kích thước chữ */
                                text-align: center; /* Căn giữa chữ theo chiều ngang */
                                margin: 0 auto; /* Đảm bảo căn giữa theo chiều ngang */
                                line-height: 100px; /* Đảm bảo căn giữa theo chiều dọc */
                                height: 100px; /* Đặt chiều cao của tiêu đề */
                                width: 100%; /* Đảm bảo tiêu đề chiếm toàn bộ chiều rộng */
                            }


                            .chart-row {
                                display: flex;
                                justify-content: center; /* Căn lề ở giữa theo chiều ngang */
                                gap: 20px; /* Khoảng cách giữa hai biểu đồ */
                                margin-top: 30px;
                            }

                            .chart-container {
                                width: 35vw; /* Đặt 40% chiều rộng màn hình cho mỗi biểu đồ */
                                height: 35vw; /* Đảm bảo tỉ lệ vuông */
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
                            @media (max-width: 350px) {
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
                            <div class="chart-container"> <canvas id="pieChart_all"></canvas> </div>

                            <!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
                            <div class="chart-container"> <canvas id="barChart_all"></canvas> </div>
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
                        var ctx1 = document.getElementById('pieChart_all').getContext('2d');
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
                        var ctx2 = document.getElementById('barChart_all').getContext('2d');
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
            </div>
            <div id="station1" class="page" style="display:none;">
            <div id="stationAContent" style="display: none;"></div>
                <script>
                    function loadStationA() {
                        // Ẩn các nội dung khác nếu cần
                        document.getElementById('stationAContent').style.display = 'block';
                        
                        // Gọi mã PHP để tải dữ liệu từ trạm A
                        fetch('station A.php') // Thay đổi đường dẫn đến file PHP cho station A
                            .then(response => response.text())
                            .then(data => {
                                document.getElementById('stationAContent').innerHTML = data;
                                // Gọi hàm để vẽ biểu đồ sau khi dữ liệu đã được tải
                                drawBarChart(); // Hàm vẽ biểu đồ cột
                                drawPieChart(); // Hàm vẽ biểu đồ tròn
                            })
                            .catch(error => console.error('Error:', error));
                    }
                </script>
            </div>
            <div id="station2" class="page" style="display:none;">Station 2 content will be here.</div>
            <div id="station3" class="page" style="display:none;">Station 3 content will be here.</div>
            <div id="station4" class="page" style="display:none;">Station 4 content will be here.</div>
            <div id="station5" class="page" style="display:none;">Station 5 content will be here.</div>
            <div id="station6" class="page" style="display:none;">Station 6 content will be here.</div>
            <div id="station7" class="page" style="display:none;">Station 7 content will be here.</div>
        </div>

        <script src="script.js"></script>
    </body>
    </html>
