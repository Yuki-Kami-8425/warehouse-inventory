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

// Biến để lưu thông báo phản hồi
$message = "";

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
<html>
<head>
    <title>Warehouse Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2>Warehouse Statistics</h2>

<!-- Biểu đồ tròn: Tổng số pallet -->
<canvas id="pieChart" width="400" height="400"></canvas>

<!-- Biểu đồ cột: Số lượng pallet theo khách hàng -->
<canvas id="barChart" width="400" height="400"></canvas>

<script>
// Dữ liệu cho biểu đồ tròn
var totalPalletData = {
    datasets: [{
        data: [<?php echo $total_slots - $total_pallets; ?>, <?php echo $total_pallets; ?>],
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
