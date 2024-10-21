<!DOCTYPE html>
<html>
<head>
    <title>Warehouse Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Statistics</h2>

<?php
// Kết nối đến cơ sở dữ liệu
$servername = "eiusmartwarehouse.database.windows.net";
$username = "eiuadmin";
$password = "Khoa123456789";
$dbname = "eiu_warehouse_24";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Truy vấn tổng số pallet (1372 ô)
$total_slots = 1372;

// Truy vấn số khách hàng và số pallet của họ
$sql = "SELECT TENKH, COUNT(*) as pallet_count FROM dbo.stored_warehouse GROUP BY TENKH";
$result = $conn->query($sql);

// Tạo mảng cho dữ liệu biểu đồ
$customers = [];
$pallets = [];

if ($result->num_rows > 0) {
    // Lấy dữ liệu
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row['TENKH'];
        $pallets[] = $row['pallet_count'];
    }
}

// Đóng kết nối
$conn->close();
?>

<!-- Biểu đồ tròn: Tổng số pallet -->
<canvas id="pieChart" width="400" height="400"></canvas>

<!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
<canvas id="barChart" width="400" height="400"></canvas>

<script>
// Dữ liệu cho biểu đồ tròn
var totalPalletData = {
    datasets: [{
        data: [<?php echo $total_slots - array_sum($pallets); ?>, <?php echo array_sum($pallets); ?>],
        backgroundColor: ['#FF6384', '#36A2EB']
    }],
    labels: ['Empty Slots', 'Stored Pallets']
};

// Dữ liệu cho biểu đồ cột
var barChartData = {
    labels: <?php echo json_encode($customers); ?>,
    datasets: [{
        label: 'Pallets Stored',
        backgroundColor: '#36A2EB',
        data: <?php echo json_encode($pallets); ?>
    }]
};

// Vẽ biểu đồ tròn
var ctx1 = document.getElementById('pieChart').getContext('2d');
var pieChart = new Chart(ctx1, {
    type: 'pie',
    data: totalPalletData
});

// Vẽ biểu đồ cột
var ctx2 = document.getElementById('barChart').getContext('2d');
var barChart = new Chart(ctx2, {
    type: 'bar',
    data: barChartData
});
</script>

</body>
</html>
