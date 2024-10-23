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

// Lấy dữ liệu từ bảng cho tất cả các trạm từ A đến G
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '[A-G]%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu
$data = [];
$customers = [];
$highlighted = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
    $customers[$row['MAKH']][] = $row['RFID']; // Lưu danh sách RFID cho mỗi khách hàng
    $highlighted[] = trim($row['RFID']); // Dùng trim để loại bỏ khoảng trắng, giữ danh sách RFID để highlight
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #001F3F; /* Màu nền xanh đậm */
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            background-color: #2c3e50; /* Màu nền thanh bên */
            padding-top: 60px;
            transition: width 0.3s;
        }

        .slide:hover {
            transform: scale(1.02); /* Phóng to một chút */
            transition: transform 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }


        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
            padding: 20px 0;
            text-align: center;
        }

        .slide-title {
            font-size: 24px; /* Kích thước chữ cho tiêu đề */
            color: white; /* Màu chữ */
            margin-bottom: 10px; /* Khoảng cách giữa tiêu đề và hình ảnh */
        }

        .slide {
            display: none; /* Ẩn tất cả các slide mặc định */
            position: relative; /* Để có thể căn chỉnh các thành phần bên trong */
        }

        .slide img {
            width: 650px; /* Chiều rộng cố định */
            height: 350px; /* Chiều cao cố định */
            object-fit: fill; /* Kéo giãn ảnh để lấp đầy khung */
        }

        .dots {
            position: relative; /* Để căn giữa dấu chấm */
            text-align: center; /* Căn giữa dấu chấm */
            margin-top: 10px; /* Khoảng cách giữa chữ và dấu chấm */
        }

        .dot {
            height: 10px; /* Kích thước dấu chấm */
            width: 10px; /* Kích thước dấu chấm */
            margin: 0 5px; /* Khoảng cách giữa các dấu chấm */
            background-color: white; /* Màu trắng */
            border-radius: 50%; /* Đường viền tròn */
            display: inline-block; /* Hiển thị thành dòng ngang */
            cursor: pointer; /* Con trỏ khi hover vào */
            transition: all 0.3s; /* Hiệu ứng chuyển tiếp */
        }

        .dot.active {
            height: 15px; /* Kích thước lớn hơn khi được chọn */
            width: 15px; /* Kích thước lớn hơn khi được chọn */
            background-color: #00BFFF; /* Màu xanh lam khi được chọn */
        }

        .dot:hover {
            background-color: #00BFFF; /* Màu nền khi hover */
            transform: scale(1.2); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .sidebar ul li a.active {
            color: #00BFFF; /* Màu xanh lam khi được chọn */
            background-color: rgba(255, 255, 255, 0.1); /* Màu nền khi được chọn (tùy chọn) */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 10px;
            text-align: center;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s; /* Thêm hiệu ứng chuyển màu */
        }

        .sidebar ul li a:hover {
            background-color: rgba(255, 255, 255, 0.2); /* Màu nền khi hover */
            color: #32CD32; /* Màu lục tươi khi đưa chuột vào */
            transform: scale(1.05); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .sidebar ul li a.selected {
            color: #00BFFF; /* Màu xanh lam khi được chọn */
        }

        .sidebar ul li a i {
            margin-right: 10px;
            transition: color 0.3s; /* Thêm hiệu ứng chuyển màu */
        }

        /* Áp dụng màu xanh lam cho icon khi được chọn */
        .sidebar ul li a.selected i {
            color: #00BFFF;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .page {
            margin-top: 20px;
            font-size: 24px;
            color: white;
        }

        /* Hidden sidebar */
        .sidebar.collapsed {
            width: 70px;
        }

        .content.collapsed {
            margin-left: 70px;
        }

        .sidebar.collapsed ul li a {
            justify-content: center;
        }

        .sidebar.collapsed ul li a span {
            display: none;
        }

        .sidebar ul li a i {
            font-size: 24px;
        }

        .toggle-btn {
            position: absolute;
            top: 15px;
            left: 10px;
            font-size: 24px; /* Điều chỉnh kích thước của biểu tượng */
            color: white; /* Màu của biểu tượng */
            background: none;
            border: none;
            cursor: pointer;
        }

        .toggle-btn:hover {
            background-color: rgba(255, 255, 255, 0.1); /* Màu nền khi hover */
            border-radius: 5px; /* Bo góc một chút */
            transform: scale(1.1); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }


        /* Thêm transition cho danh sách trạm */
        .station-list {
            overflow: hidden; /* Giữ nội dung bên trong không tràn ra khi thu nhỏ */
            max-height: 0; /* Đặt chiều cao mặc định là 0 để ẩn */
            transition: max-height 0.5s ease; /* Thêm hiệu ứng chuyển đổi chiều cao */
        }

        /* Khi mở danh sách trạm */
        .station-list.open {
            max-height: 500px; /* Thiết lập chiều cao tối đa cho danh sách trạm khi mở */
        }

        /* Đặt kích thước chữ và biểu tượng cho các mục All và các trạm */
        .sidebar ul li a.station-link {
            font-size: 16px; /* Kích thước chữ nhỏ hơn */
        }

        .sidebar ul li a.station-link i {
            font-size: 20px; /* Kích thước biểu tượng nhỏ hơn */
        }

        /* Đặt kích thước chữ và biểu tượng cho các mục Home, Dashboard và Edit */
        .sidebar ul li a.main-link {
            font-size: 18px; /* Kích thước chữ lớn hơn */
        }

        .sidebar ul li a.main-link i {
            font-size: 24px; /* Kích thước biểu tượng lớn hơn */
        }


        .home-container {
            display: flex; /* Sử dụng flexbox để căn chỉnh */
            flex-direction: column; /* Đặt chiều dọc */
            align-items: center; /* Căn giữa */
        }

        .datetime {
            position: fixed; /* Đặt thành fixed để luôn ở dưới cùng */
            bottom: 10px; /* Cách từ đáy cửa sổ */
            left: 50%; /* Căn giữa theo chiều ngang */
            transform: translateX(-50%); /* Đẩy về phía bên trái để căn giữa chính xác */
            color: white; /* Màu chữ */
            font-size: 12px; /* Kích thước chữ */
            z-index: 1000; /* Đặt trên cùng để luôn nhìn thấy */
            white-space: nowrap; /* Không cho phép xuống dòng */
            overflow: hidden; /* Ẩn nội dung tràn */
            text-overflow: ellipsis; /* Hiệu ứng ba chấm nếu nội dung quá dài */
            max-width: 200px; /* Chiều rộng tối đa */
            text-align: center; /* Căn giữa nội dung */
            margin-top: 5px; /* Khoảng cách giữa nút Home và phần ngày giờ */
            transition: left 0.3s ease, transform 0.3s ease; /* Thêm hiệu ứng chuyển tiếp */
        }

        .tooltip {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.75); /* Nền đen trong suốt */
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0; /* Ẩn ban đầu */
            visibility: hidden; /* Ẩn ban đầu */
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 1000; /* Đặt tooltip luôn ở trên */
        }

        .tooltip.show {
            opacity: 1; /* Hiển thị tooltip */
            visibility: visible;
        }

        .sidebar ul li a {
            position: relative; /* Để xác định vị trí cho tooltip */
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i> </button>
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
                <li><a href="#" onclick="showPage('edit-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> Edit</span></a></li>
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

            <div id="dashboard" class="page" style="display:none;">Dashboard will be here.</div>
            <div id="edit-warehouse" class="page" style="display:none;">Edit Warehouse will be here.</div>
            <div id="all" class="page" style="display:none;">
                <head>
                    <title>Warehouse Statistics</title>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <style>
                        /* CSS để điều chỉnh màu sắc và bố cục biểu đồ */
                        body {
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
                        @media (max-width: 400px) {
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
        </div>
        <div id="station1" class="page" style="display:none;">
            <title>Warehouse Management - Station A</title>
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
                caption {
                    font-size: 16px; /* Cỡ chữ cho caption lớn hơn */
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

    <h2>Warehouse Station A</h2>

    <div class="container">
        <!-- Bảng Left Rack -->
        <table>
            <caption style="caption-side: top;">Left Rack</caption>
            <?php for ($row = 7; $row >= 1; $row--): ?>
                <tr>
                    <?php for ($col = 1; $col <= 14; $col++): ?>
                        <?php $index = ($row - 1) * 14 + $col; ?>
                        <td class="<?= in_array('AL' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AL<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>

        <!-- Bảng Right Rack -->
        <table>
            <caption style="caption-side: top;">Right Rack</caption>
            <?php for ($row = 7; $row >= 1; $row--): ?>
                <tr>
                    <?php for ($col = 1; $col <= 14; $col++): ?>
                        <?php $index = ($row - 1) * 14 + $col; ?>
                        <td class="<?= in_array('AR' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">AR<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </table>
    </div>

    <!-- Biểu đồ -->
    <div class="charts">
        <!-- Biểu đồ cột -->
        <div class="chart-container">
            <canvas id="barChart"></canvas>
        </div>

        <!-- Biểu đồ tròn -->
        <div class="chart-container">
            <canvas id="pieChart"></canvas>
        </div>
    </div>

    <script>
        // Dữ liệu biểu đồ
        const customers = <?= json_encode($customers) ?>;
        const customerLabels = Object.keys(customers); // Mã khách hàng
        const customerData = customerLabels.map(key => customers[key].length); // Đếm số lượng RFID cho mỗi khách hàng
        const totalSlots = 196; // Tổng số ô (98x2)
        const filledSlots = <?= count($highlighted) ?>; // Số ô đã sử dụng

        // Biểu đồ cột
        const ctxBar = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: customerLabels, // Mã khách hàng
                datasets: [{
                    label: 'Used Slots', // Nhãn trục Y
                    data: customerData, // Số lượng RFID cho mỗi khách hàng
                    backgroundColor: 'rgba(54, 162, 235, 1)', // Màu lam tươi
                    borderColor: 'white', // Đường viền trắng
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: 'white' // Màu chữ trắng cho legend
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Used Slots', // Nhãn cho trục Y
                            color: 'white' // Màu chữ trắng cho nhãn
                        },
                        grid: {
                            color: 'white' // Màu đường lưới trắng
                        },
                        ticks: {
                            color: 'white', // Màu chữ trắng cho tick marks
                            stepSize: 1 // Đặt độ chia cho trục Y là 1
                        }
                    },
                    x: {
                        grid: {
                            color: 'white' // Màu đường lưới trắng
                        },
                        ticks: {
                            color: 'white' // Màu chữ trắng cho tick marks
                        }
                    }
                }
            }
        });

        // Biểu đồ tròn
        const ctxPie = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Used', 'Remaining'], // Nhãn cho biểu đồ tròn
                datasets: [{
                    data: [filledSlots, totalSlots - filledSlots],
                    backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Màu đỏ và xanh
                    borderColor: 'white', // Đường viền trắng
                    borderWidth: 2
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: 'white' // Màu chữ trắng cho legend
                        }
                    }
                }
            }
        });
    </script>

    </body>
        </div>
        <div id="station2" class="page" style="display:none;">Station 2 content will be here.</div>
        <div id="station3" class="page" style="display:none;">Station 3 content will be here.</div>
        <div id="station4" class="page" style="display:none;">Station 4 content will be here.</div>
        <div id="station5" class="page" style="display:none;">Station 5 content will be here.</div>
        <div id="station6" class="page" style="display:none;">Station 6 content will be here.</div>
        <div id="station7" class="page" style="display:none;">Station 7 content will be here.</div>
    </div>

    <script>
        let slideIndex = 0;
        showSlides();

    function showSlides() {
        let slides = document.querySelectorAll('.slide');
        let dots = document.querySelectorAll('.dot');

        slides.forEach((slide, index) => {
            slide.style.display = 'none'; // Ẩn tất cả các slide
            dots[index].classList.remove("active"); // Xóa lớp active khỏi tất cả các dấu chấm
        });

        slideIndex++;
        if (slideIndex > slides.length) {
            slideIndex = 1; // Reset lại chỉ số nếu vượt quá số slide
        }

        slides[slideIndex - 1].style.display = 'block'; // Hiện slide hiện tại
        dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm hiện tại

        setTimeout(showSlides, 5000); // Thay đổi slide mỗi 5 giây
    }

    function showSlide(index) {
        slideIndex = index; // Đặt chỉ số slide hiện tại
        let slides = document.querySelectorAll('.slide');
        let dots = document.querySelectorAll('.dot');

        slides.forEach(slide => slide.style.display = 'none'); // Ẩn tất cả các slide
        dots.forEach(dot => dot.classList.remove("active")); // Xóa lớp active khỏi tất cả các dấu chấm

        slides[slideIndex - 1].style.display = 'block'; // Hiện slide tương ứng
        dots[slideIndex - 1].classList.add("active"); // Đánh dấu dấu chấm tương ứng
    }


    function toggleStations() {
        let stationList = document.querySelector('.station-list');
        
        // Nếu danh sách trạm đang mở thì đóng lại, ngược lại thì mở
        if (stationList.classList.contains('open')) {
            stationList.classList.remove('open'); // Đóng danh sách trạm
        } else {
            stationList.classList.add('open'); // Mở danh sách trạm
        }
    }

    function showPage(pageId) {
        let pages = document.querySelectorAll('.page');
        let links = document.querySelectorAll('.sidebar ul li a');

        pages.forEach(page => {
            page.style.display = 'none';
        });

        links.forEach(link => {
            link.classList.remove('active'); // Xóa lớp active
        });

        document.getElementById(pageId).style.display = 'block';

        const activeLink = [...links].find(link => link.onclick.toString().includes(pageId));
        if (activeLink) {
            activeLink.classList.add('active'); // Thêm lớp active cho liên kết đã chọn
        }

        // Đóng danh sách trạm nếu chọn Home hoặc Edit
        if (pageId === 'home' || pageId === 'edit-warehouse') {
            document.querySelector('.station-list').classList.remove('open'); // Đóng danh sách trạm
        }
    }

    function updateTime() {
        const now = new Date();
        const optionsDate = { day: 'numeric', month: 'numeric', year: 'numeric' };
        const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
        
        const dateString = now.toLocaleDateString('en-GB', optionsDate); // 19/10/2024
        const timeString = now.toLocaleTimeString('en-US', optionsTime); // 1:52 PM
        
        const sidebar = document.getElementById('sidebar');

        // Khi thanh công cụ mở, hiển thị ngày trước giờ
        if (sidebar.classList.contains('collapsed')) {
            document.getElementById('datetime').innerHTML = `${dateString} ${timeString}`; // Ngày trước, giờ sau
        } else {
            document.getElementById('datetime').innerHTML = `${dateString}<br>${timeString}`; // Ngày trên, giờ dưới
        }
    }

    function updateTime() {
        const now = new Date();
        const optionsDate = { day: 'numeric', month: 'numeric', year: 'numeric' };
        const optionsTime = { hour: 'numeric', minute: 'numeric', hour12: true };
        
        const dateString = now.toLocaleDateString('en-GB', optionsDate); // 19/10/2024
        const timeString = now.toLocaleTimeString('en-US', optionsTime); // 1:52 PM
        
        const sidebar = document.getElementById('sidebar');
        
        // Khi thanh công cụ mở, hiển thị giờ ở trên và ngày ở dưới
        if (sidebar.classList.contains('collapsed')) {
            document.getElementById('datetime').innerHTML = `${timeString}<br>${dateString}`; // Giờ trên, ngày dưới
        } else {
            document.getElementById('datetime').innerHTML = `${timeString} ${dateString}`; // Cả hai trong một dòng
        }
    }

    // Cập nhật thời gian mỗi giây
    setInterval(updateTime, 1000);
    updateTime(); // Gọi ngay lập tức để thiết lập giá trị ban đầu

    function toggleSidebar() {
        let sidebar = document.getElementById('sidebar');
        let content = document.querySelector('.content');

        if (sidebar.classList.contains('collapsed')) {
            sidebar.classList.remove('collapsed');
            content.classList.remove('collapsed');
        } else {
            sidebar.classList.add('collapsed');
            content.classList.add('collapsed');
        }

        updateFooterPosition(); // Cập nhật vị trí của footer sau khi thay đổi thanh công cụ
    }

    function updateFooterPosition() {
        const sidebar = document.getElementById('sidebar');
        const footer = document.getElementById('datetime');

        // Tính toán chiều rộng thanh công cụ
        const sidebarWidth = sidebar.offsetWidth;

        // Đặt lề trái của footer để căn giữa
        footer.style.left = `calc(${sidebarWidth}px / 2)`; // Căn giữa
        footer.style.transform = 'translateX(-50%)'; // Đẩy về phía bên trái để căn giữa chính xác
    }


    // Gọi hàm ngay lập tức để thiết lập vị trí ban đầu
    updateFooterPosition();
    setInterval(updateFooterPosition, 250); // Cập nhật mỗi giây nếu cần thiết

    let tooltipTimeout;

    document.querySelectorAll('.sidebar ul li a').forEach(item => {
        item.addEventListener('mouseover', function(event) {
            // Xóa timeout trước đó nếu có
            clearTimeout(tooltipTimeout);

            // Tạo tooltip sau 1 giây
            tooltipTimeout = setTimeout(() => {
                showTooltip(event, this); // Hiển thị tooltip
            }, 250); // 1 giây
        });

        item.addEventListener('mouseout', function() {
            clearTimeout(tooltipTimeout); // Xóa timer
            hideTooltip(); // Ẩn tooltip
        });
    });

    function showTooltip(event, element) {
        const tooltip = document.createElement('div');
        tooltip.classList.add('tooltip');
        tooltip.textContent = element.querySelector('.link-text')?.textContent || 'Tooltip';
        
        document.body.appendChild(tooltip);

        const rect = element.getBoundingClientRect();
        tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 10}px`; // Vị trí phía trên nút
        tooltip.style.left = `${rect.left + (rect.width - tooltip.offsetWidth) / 2}px`; // Căn giữa với nút

        tooltip.classList.add('show');
    }

    function hideTooltip() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) {
            tooltip.remove(); // Xóa tooltip khỏi DOM
        }
    }
    </script>
</body>
</html>
