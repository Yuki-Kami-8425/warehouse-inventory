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
$station = isset($_GET['station']) ? $_GET['station'] : 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css"> <!-- Đường dẫn đến CSS -->
    <script src="script.js"></script> <!-- Đường dẫn đến JavaScript -->
    <title>Warehouse Management</title>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <ul>
            <li><a href="#" class="main-link" onclick="showPage('home')">Home</a></li>
            <li>
                <a href="#" class="main-link" onclick="toggleStations()">Dashboard</a>
                <div class="station-list">
                    <a href="#" class="station-link" onclick="loadStation('all')">All</a>
                    <a href="#" class="station-link" onclick="loadStation('stationA')">Station A</a>
                    <a href="#" class="station-link" onclick="loadStation('stationB')">Station B</a>
                    <a href="#" class="station-link" onclick="loadStation('stationC')">Station C</a>
                    <a href="#" class="station-link" onclick="loadStation('stationD')">Station D</a>
                    <a href="#" class="station-link" onclick="loadStation('stationE')">Station E</a>
                    <a href="#" class="station-link" onclick="loadStation('stationF')">Station F</a>
                    <a href="#" class="station-link" onclick="loadStation('stationG')">Station G</a>
                </div>
            </li>
            <li><a href="#" class="main-link" onclick="showPage('list')">List</a></li>
        </ul>
    </div>

    <div class="content">
        <div class="main-content">
            <h2>
                <?= $station === 'all' ? 'Warehouse Overview' : 'Warehouse Station ' . ucfirst($station) ?>
            </h2>

            <?php if ($station !== 'all'): ?>
                <!-- Hiển thị thông tin cho các trạm cụ thể -->
                <p>Details for <?= ucfirst($station) ?>...</p>
                <!-- Code hiển thị thông tin khác cho trạm -->
            <?php else: ?>
                <!-- Hiển thị thông tin cho tất cả các nhà kho -->
                <p>Overall data for all stations...</p>
                <!-- Code hiển thị thông tin tổng quan -->
            <?php endif; ?>
        </div>
    </div>

    <div class="datetime" id="datetime"></div> <!-- Hiển thị ngày giờ -->

    <script>
        function showPage(page) {
            // Logic để ẩn hiện các trang khác nhau
            if (page === 'home') {
                document.querySelector('.main-content').innerHTML = '<h2>Home</h2><p>Welcome to the warehouse management system.</p>';
                // Thêm logic khác nếu cần
            } else if (page === 'list') {
                document.querySelector('.main-content').innerHTML = '<h2>List</h2><p>Here is the list of all stations...</p>';
                // Thêm logic khác nếu cần
            }
        }

        function toggleStations() {
            const stationList = document.querySelector('.station-list');
            stationList.classList.toggle('open'); // Mở hoặc đóng danh sách trạm
        }

        function loadStation(station) {
            const mainContent = document.querySelector('.main-content');
            if (station === 'all') {
                mainContent.innerHTML = '<h2>Warehouse Overview</h2><p>Overall data for all stations...</p>';
            } else {
                mainContent.innerHTML = `<h2>Warehouse Station ${station.charAt(0).toUpperCase() + station.slice(1)}</h2><p>Details for ${station.charAt(0).toUpperCase() + station.slice(1)}...</p>`;
            }
            toggleStations(); // Đóng danh sách trạm sau khi chọn
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const content = document.querySelector('.content');
            sidebar.classList.toggle('collapsed');
            content.classList.toggle('collapsed');
        }
    </script>
</body>
</html>