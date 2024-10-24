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

// Lấy trạm từ query string, mặc định là 'all'
$station = isset($_GET['station']) ? $_GET['station'] : 'all';

// Lấy dữ liệu từ bảng cho tất cả các trạm nếu là 'all', ngược lại lấy theo trạm
if ($station === 'all') {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse";
} else {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE ?";
    $params = array($station . '%');
}
$stmt = sqlsrv_query($conn, $sql, $params ?? null);

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
    $highlighted[] = trim($row['RFID']); // Dùng trim để loại bỏ khoảng trắng
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
        /* Các kiểu CSS khác đã có trước đó... */

        /* Slideshow styling */
        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
        }
        .slide {
            display: none; /* Ẩn tất cả slides theo mặc định */
        }
        .slide img {
            width: 100%; /* Đảm bảo hình ảnh chiếm toàn bộ chiều rộng */
            height: auto; /* Giữ tỷ lệ chiều cao */
        }
        .dot {
            cursor: pointer;
            height: 15px;
            width: 15px;
            margin: 0 2px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
        }
        .dot.active {
            background-color: #717171;
        }

        /* Các kiểu CSS cho dashboard và list */
        .page {
            display: none; /* Ẩn tất cả các trang theo mặc định */
        }
        .charts {
            display: flex;
            justify-content: space-between;
        }
        .chart-container {
            width: 48%;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <a href="#" onclick="showPage('home')">Home</a>
    <button class="dropdown-btn">Dashboard 
        <i class="fa fa-caret-down"></i>
    </button>
    <div class="dropdown-container">
        <a href="#" onclick="showPage('dashboard', 'all')">All</a>
        <a href="#" onclick="showPage('dashboard', 'A')">Station A</a>
        <a href="#" onclick="showPage('dashboard', 'B')">Station B</a>
        <a href="#" onclick="showPage('dashboard', 'C')">Station C</a>
        <a href="#" onclick="showPage('dashboard', 'D')">Station D</a>
        <a href="#" onclick="showPage('dashboard', 'E')">Station E</a>
        <a href="#" onclick="showPage('dashboard', 'F')">Station F</a>
        <a href="#" onclick="showPage('dashboard', 'G')">Station G</a>
    </div>
    <a href="#" onclick="showPage('list')">List</a>
</div>

<div class="main-content">
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

    <div id="dashboard" class="page">
        <h2><?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . $station ?></h2>

        <?php if ($station !== 'all'): ?>
            <div class="container">
                <!-- Bảng Left Rack -->
                <table>
                    <caption>Left Rack</caption>
                    <?php for ($row = 7; $row >= 1; $row--): ?>
                        <tr>
                            <?php for ($col = 1; $col <= 14; $col++): ?>
                                <?php $index = ($row - 1) * 14 + $col; ?>
                                <td class="<?= in_array($station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">
                                    <?= $station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT) ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </table>

                <!-- Bảng Right Rack -->
                <table>
                    <caption>Right Rack</caption>
                    <?php for ($row = 7; $row >= 1; $row--): ?>
                        <tr>
                            <?php for ($col = 1; $col <= 14; $col++): ?>
                                <?php $index = ($row - 1) * 14 + $col; ?>
                                <td class="<?= in_array($station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>">
                                    <?= $station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT) ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endfor; ?>
                </table>
            </div>
        <?php endif; ?>

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
    </div>

    <div id="list" class="page">
        <!-- Nội dung cho List sẽ ở đây -->
        <h2>List Content</h2>
        <p>Đây là nội dung của List.</p>
    </div>
</div>

<script>
    let currentSlide = 0;
    showSlides();

    function showSlides() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        slides.forEach((slide, index) => {
            slide.style.display = (index === currentSlide) ? 'block' : 'none';
            dots[index].className = dots[index].className.replace(' active', '');
        });
        dots[currentSlide].className += ' active';
        currentSlide = (currentSlide + 1) % slides.length;
        setTimeout(showSlides, 3000); // Thay đổi slide mỗi 3 giây
    }

    function showPage(pageId, station = 'all') {
        const pages = document.querySelectorAll('.page');
        pages.forEach(page => {
            page.style.display = 'none'; // Ẩn tất cả các trang
        });
        document.getElementById(pageId).style.display = 'block'; // Hiện trang được chọn

        if (pageId === 'home') {
            showSlides(); // Bắt đầu slideshow khi vào trang Home
        } else {
            // Cập nhật giá trị trạm
            if (pageId === 'dashboard') {
                // Gọi lại với station nếu cần
                loadDashboardData(station);
            }
        }
    }

    function loadDashboardData(station) {
        // Gọi lại trang hoặc làm gì đó để cập nhật dữ liệu dựa trên trạm đã chọn
        // Ví dụ, sử dụng AJAX để lấy dữ liệu cho biểu đồ hoặc bảng
    }

    // Dữ liệu biểu đồ
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers); // Mã khách hàng
    const customerData = customerLabels.map(key => customers[key].length); // Đếm số lượng RFID cho mỗi khách hàng
    const totalSlots = 196 * (<?= $station === 'all' ? 7 : 1 ?>); // Tổng số ô, nếu là 'all' thì 7 trạm, nếu trạm cụ thể thì 1 trạm
    const filledSlots = <?= count($highlighted) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
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
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Used Slots',
                        color: 'white'
                    },
                    grid: {
                        color: 'white'
                    },
                    ticks: {
                        color: 'white',
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        color: 'white'
                    },
                    ticks: {
                        color: 'white'
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
                legend: {
                    labels: {
                        color: 'white'
                    }
                }
            }
        }
    });

    // Dropdown logic
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.classList.toggle('active');
        const dropdownContent = this.nextElementSibling;
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
</script>

</body>
</html>
