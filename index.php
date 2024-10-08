<?php
// Thông tin kết nối đến Azure SQL Database
$serverName = "eiusmartwarehouse.database.windows.net,1433"; // Địa chỉ máy chủ Azure SQL
$connectionOptions = array(
    "Database" => "eiu_warehouse_24", // Tên cơ sở dữ liệu
    "Uid" => "eiuadmin", // Tên đăng nhập
    "PWD" => "Khoa123456789", // Mật khẩu
    "LoginTimeout" => 15, // Tăng thời gian timeout lên 15 giây
    "Encrypt" => true, // Mã hóa kết nối
    "TrustServerCertificate" => false // Không tin tưởng chứng chỉ tự ký
);

// Tạo kết nối đến SQL Server
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Kiểm tra kết nối
if ($conn === false) {
    die("Kết nối thất bại: " . print_r(sqlsrv_errors(), true)); // Hiển thị thông báo lỗi nếu kết nối thất bại
}

// Câu truy vấn SQL để lấy dữ liệu từ bảng dbo.Products
$sql = "SELECT ProductID, ProductName, Quantity FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra xem có lỗi khi thực hiện truy vấn hay không
if ($stmt === false) {
    die("Truy vấn thất bại: " . print_r(sqlsrv_errors(), true)); // Hiển thị thông báo lỗi nếu câu truy vấn thất bại
}

// Tạo mảng để lưu dữ liệu sản phẩm cho biểu đồ
$productNames = array();
$productQuantities = array();

// Hiển thị dữ liệu dưới dạng bảng HTML và lưu trữ dữ liệu cho biểu đồ
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Warehouse Inventory with Charts</title>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>"; // Thêm thư viện Chart.js
echo "<style>";
echo "table { border-collapse: collapse; width: 100%; margin: 20px 0; }";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }";
echo "th { background-color: #f2f2f2; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<h2 style='text-align: center;'>Warehouse Inventory</h2>";
echo "<table border='1'>";
echo "<tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Quantity</th>
      </tr>";

// Lặp qua từng dòng kết quả và hiển thị trong bảng, đồng thời lưu dữ liệu cho biểu đồ
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>" . $row['ProductID'] . "</td>
            <td>" . $row['ProductName'] . "</td>
            <td>" . $row['Quantity'] . "</td>
          </tr>";
    array_push($productNames, $row['ProductName']); // Thêm tên sản phẩm vào mảng
    array_push($productQuantities, $row['Quantity']); // Thêm số lượng vào mảng
}

echo "</table>";

// Giải phóng bộ nhớ sau khi thực thi câu truy vấn
sqlsrv_free_stmt($stmt);

// Đóng kết nối cơ sở dữ liệu
sqlsrv_close($conn);
?>

<!-- Khu vực hiển thị biểu đồ -->
<div style="width: 50%; margin: auto;">
    <h3 style="text-align: center;">Biểu đồ Cột: Số lượng sản phẩm</h3>
    <canvas id="barChart"></canvas> <!-- Vùng vẽ biểu đồ cột -->
    <h3 style="text-align: center;">Biểu đồ Tròn: Phân bố số lượng sản phẩm</h3>
    <canvas id="pieChart"></canvas> <!-- Vùng vẽ biểu đồ tròn -->
</div>

<script>
// Chuyển dữ liệu PHP sang JavaScript
var productNames = <?php echo json_encode($productNames); ?>; // Tên sản phẩm
var productQuantities = <?php echo json_encode($productQuantities); ?>; // Số lượng sản phẩm

// Vẽ biểu đồ cột
var ctxBar = document.getElementById('barChart').getContext('2d');
var barChart = new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: productNames,
        datasets: [{
            label: 'Số lượng sản phẩm',
            data: productQuantities,
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

// Vẽ biểu đồ tròn
var ctxPie = document.getElementById('pieChart').getContext('2d');
var pieChart = new Chart(ctxPie, {
    type: 'pie',
    data: {
        labels: productNames,
        datasets: [{
            label: 'Phân bố sản phẩm',
            data: productQuantities,
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    }
});
</script>
</body>
</html>
