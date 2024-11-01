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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOM7Jbgtv/5ZlSk1BpGtv3DeD3sI5XfT1E6z9dRe" crossorigin="anonymous">
    <style>
        /* Basic resets */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background-color: #001F3F; color: #FFF; display: flex; }
        
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #1a1a2e;
            padding-top: 20px;
            position: fixed;
            transition: width 0.3s;
            overflow: hidden;
        }
        .sidebar a, .dropdown-btn {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 18px;
            color: #f1f1f1;
            display: flex;
            align-items: center;
            transition: background-color 0.2s;
            white-space: nowrap;
        }
        .sidebar a:hover, .dropdown-btn:hover { background-color: #575757; }
        .dropdown-btn i { margin-left: auto; transition: transform 0.3s; }
        .dropdown-container { display: none; background-color: #333; padding-left: 30px; }
        
        /* Main content styling */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            transition: margin-left 0.3s, width 0.3s;
        }
        
        /* Table and chart styling */
        .container { display: flex; justify-content: space-around; margin: 20px 0; }
        table {
            width: 40%;
            border-collapse: collapse;
            font-size: 10px;
            background-color: #222;
        }
        th, td { border: 1px solid #444; padding: 8px; text-align: center; color: #ddd; }
        td.highlight { background-color: #32CD32; }

        /* Chart styling */
        .chart-container { width: 45%; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <a href="#">Home</a>
    <button class="dropdown-btn">Dashboard <i class="fas fa-caret-down"></i></button>
    <div class="dropdown-container">
        <a href="?station=all">All</a>
        <a href="?station=A">Station A</a>
        <a href="?station=B">Station B</a>
        <a href="?station=C">Station C</a>
        <a href="?station=D">Station D</a>
        <a href="?station=E">Station E</a>
        <a href="?station=F">Station F</a>
        <a href="?station=G">Station G</a>
    </div>
    <a href="#">List</a>
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

</body>
</html>
