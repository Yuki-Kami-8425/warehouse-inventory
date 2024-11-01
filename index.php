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
    <title>Warehouse Management - <?= $station === 'all' ? 'All Stations' : 'Station ' . $station ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
    font-family: Arial, sans-serif;
    background-color: #001F3F;
    color: white;
    display: flex;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background-color: #111;
    display: flex;
    flex-direction: column;
    position: fixed;
    transition: width 0.3s ease;
}

.sidebar.collapsed {
    width: 80px;
}

.sidebar-logo, .sidebar-item {
    display: flex;
    align-items: center;
    padding: 15px;
    color: white;
    text-decoration: none;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.sidebar-logo {
    font-size: 18px;
    margin-bottom: 10px;
}

.sidebar-item:hover {
    background-color: #575757;
}

.sidebar-item i {
    font-size: 18px;
    width: 30px;
}

.sidebar-item .sidebar-text {
    display: inline-block;
    transition: opacity 0.3s ease;
}

.sidebar.collapsed .sidebar-text {
    opacity: 0;
    visibility: hidden;
}

.dropdown-container {
    display: none;
    flex-direction: column;
}

.sidebar.collapsed .dropdown-container {
    display: none;
}

.main-content {
    margin-left: 250px;
    padding: 20px;
    width: calc(100% - 250px);
    transition: margin-left 0.3s ease;
}

.sidebar.collapsed + .main-content {
    margin-left: 80px;
    width: calc(100% - 80px);
}

    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <a href="#" class="sidebar-logo"><i class="fas fa-warehouse"></i><span class="sidebar-text">Warehouse</span></a>
    <a href="#" class="sidebar-item"><i class="fas fa-home"></i><span class="sidebar-text">Home</span></a>
    <button class="dropdown-btn sidebar-item"><i class="fas fa-chart-bar"></i><span class="sidebar-text">Dashboard</span><i class="fa fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all" class="sidebar-item"><i class="fas fa-globe"></i><span class="sidebar-text">All</span></a>
        <?php foreach (range('A', 'G') as $s): ?>
            <a href="?station=<?= $s ?>" class="sidebar-item"><i class="fas fa-map-marker-alt"></i><span class="sidebar-text">Station <?= $s ?></span></a>
        <?php endforeach; ?>
    </div>
    <a href="#" class="sidebar-item"><i class="fas fa-list"></i><span class="sidebar-text">List</span></a>
    <button id="toggle-btn" class="sidebar-item"><i class="fas fa-bars"></i></button>
</div>

<div class="main-content">
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
    <div class="chart-container"><canvas id="barChart"></canvas></div>
    <div class="chart-container"><canvas id="pieChart"></canvas></div>
</div>
</div>
</div>

<script>
    const customers = <?= json_encode($customers) ?>;
    const customerLabels = Object.keys(customers);
    const customerData = customerLabels.map(key => customers[key].length);
    const totalSlots = 196 * (<?= $station === 'all' ? 7 : 1 ?>);
    const filledSlots = <?= count($highlighted) ?>;

    // Biểu đồ cột
    new Chart(document.getElementById('barChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [{ label: 'Used Slots', data: customerData, backgroundColor: 'rgba(54, 162, 235, 1)', borderColor: 'white', borderWidth: 2 }]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: 'white' } } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Number of Used Slots', color: 'white' }, ticks: { color: 'white' }, grid: { color: '#333' } },
                x: { ticks: { color: 'white' }, grid: { color: '#333' } }
            }
        }
    });

    // Biểu đồ tròn
    new Chart(document.getElementById('pieChart').getContext('2d'), {
        type: 'pie',
        data: { labels: ['Used', 'Remaining'], datasets: [{ data: [filledSlots, totalSlots - filledSlots], backgroundColor: ['#FF6384', '#36A2EB'], borderWidth: 1 }] },
        options: { plugins: { legend: { labels: { color: 'white' } } } }
    });

    // Dropdown logic
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.classList.toggle('active');
        const dropdownContent = this.nextElementSibling;
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
</script>

<script>
    // Dropdown and Sidebar Toggle Script
    document.querySelector('.dropdown-btn').addEventListener('click', function() {
        this.classList.toggle('active');
        const dropdownContent = this.nextElementSibling;
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
    document.getElementById('toggle-btn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('collapsed');
    });
</script>
</body>
</html>

