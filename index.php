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

// Đặt tên trạm
$stations = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
$station = isset($_GET['station']) ? $_GET['station'] : 'A'; // Lấy trạm từ tham số URL

// Lấy dữ liệu từ bảng cho trạm
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$station%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu
$highlighted = [];
$chartData = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $highlighted[] = trim($row['RFID']);
    $chartData[] = [
        'TENKH' => $row['TENKH'],
        'LUONG_PALLET' => $row['LUONG_PALLET']
    ];
}

// Đóng kết nối
sqlsrv_close($conn);

// Tạo dữ liệu cho biểu đồ
$labels = [];
$values = [];

foreach ($chartData as $data) {
    $labels[] = $data['TENKH'];
    $values[] = $data['LUONG_PALLET'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<h2>Warehouse Station <?= $station ?></h2>

<!-- Phần điều hướng cho các trạm -->
<div class="nav-buttons">
    <?php foreach ($stations as $st): ?>
        <a href="?station=<?= $st ?>">Station <?= $st ?></a>
    <?php endforeach; ?>
</div>

<div class="container">
    <!-- Bảng Left Rack -->
    <table>
        <caption style="caption-side: top;">Left Rack</caption>
        <?php for ($row = 7; $row >= 1; $row--): ?>
            <tr>
                <?php for ($col = 1; $col <= 14; $col++): ?>
                    <?php $index = ($row - 1) * 14 + $col; ?>
                    <td class="<?= in_array($station . 'L' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>L<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
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
                    <td class="<?= in_array($station . 'R' . str_pad($index, 2, '0', STR_PAD_LEFT), $highlighted) ? 'highlight' : '' ?>"><?= $station ?>R<?= str_pad($index, 2, '0', STR_PAD_LEFT) ?></td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </table>
</div>

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
// Dữ liệu cho biểu đồ cột
const barLabels = <?= json_encode($labels) ?>;
const barData = <?= json_encode($values) ?>;

// Biểu đồ cột
const barCtx = document.getElementById('barChart').getContext('2d');
const barChart = new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: barLabels,
        datasets: [{
            label: 'Pallets',
            data: barData,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
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

// Biểu đồ tròn
const pieCtx = document.getElementById('pieChart').getContext('2d');
const pieChart = new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels: ['Occupied', 'Available'],
        datasets: [{
            data: [barData.reduce((a, b) => a + b, 0), 196 - barData.reduce((a, b) => a + b, 0)],
            backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
            borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            }
        }
    }
});
</script>
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
            <div id="all" class="page" style="display:none;"> </div>
            <div id="station1" class="page" style="display:none;">
                
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
