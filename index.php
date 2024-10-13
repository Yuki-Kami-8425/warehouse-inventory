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

// Xử lý thêm sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productName = $_POST['ProductName'];
    $quantity = $_POST['Quantity'];
    $location = $_POST['Location'];
    $price = $_POST['Price'];
    $lastUpdated = date('Y-m-d H:i:s');

    // Câu lệnh SQL thêm sản phẩm mới (bỏ qua ProductID)
    $sql = "INSERT INTO dbo.Products (ProductName, Quantity, Location, Price, LastUpdated)
            VALUES (?, ?, ?, ?, ?)";
    $params = array($productName, $quantity, $location, $price, $lastUpdated);

    // Thực thi câu lệnh
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Kiểm tra kết quả
    if ($stmt === false) {
        echo "Lỗi khi thêm sản phẩm.";
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Sản phẩm mới đã được thêm thành công.";
    }
}

// Xử lý xoá sản phẩm
if (isset($_GET['delete'])) {
    $productID = $_GET['delete'];

    // Câu lệnh SQL xoá sản phẩm
    $sql = "DELETE FROM dbo.Products WHERE ProductID = ?";
    $params = array($productID);

    // Thực thi câu lệnh
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Kiểm tra kết quả
    if ($stmt === false) {
        echo "Lỗi khi xoá sản phẩm.";
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Sản phẩm đã được xóa thành công.";
    }
}

// Truy vấn dữ liệu sản phẩm từ bảng Products để hiển thị trong bảng và biểu đồ
$sql = "SELECT ProductName, Quantity, Price FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

$products = array();
$quantities = array();
$prices = array();

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $products[] = $row['ProductName'];
    $quantities[] = $row['Quantity'];
    $prices[] = $row['Price'];
}

// Đóng kết nối cơ sở dữ liệu
sqlsrv_free_stmt($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm và biểu đồ</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Quản lý sản phẩm</h1>
    <form method="post" action="">
        <label for="ProductName">Tên sản phẩm:</label>
        <input type="text" name="ProductName" required><br><br>

        <label for="Quantity">Số lượng:</label>
        <input type="number" name="Quantity" required><br><br>

        <label for="Location">Vị trí:</label>
        <input type="text" name="Location" required><br><br>

        <label for="Price">Giá:</label>
        <input type="text" name="Price" required><br><br>

        <input type="submit" name="add_product" value="Thêm sản phẩm">
    </form>

    <h2>Danh sách sản phẩm</h2>
    <table border="1">
        <tr>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Giá</th>
            <th>Hành động</th>
        </tr>

        <?php
        // Hiển thị danh sách sản phẩm
        foreach ($products as $index => $productName) {
            echo "<tr>";
            echo "<td>" . $productName . "</td>";
            echo "<td>" . $quantities[$index] . "</td>";
            echo "<td>" . $prices[$index] . "</td>";
            echo "<td><a href='?delete=" . $index . "'>Xoá</a></td>"; // Cần thay đổi index ở đây nếu muốn xóa theo ID thực tế
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Biểu đồ tròn: Phân bổ số lượng sản phẩm</h2>
    <canvas id="pieChart" width="400" height="400"></canvas>

    <h2>Biểu đồ cột: Số lượng sản phẩm theo giá</h2>
    <canvas id="barChart" width="400" height="400"></canvas>

    <script>
        // Lấy dữ liệu từ PHP
        var productNames = <?php echo json_encode($products); ?>;
        var productQuantities = <?php echo json_encode($quantities); ?>;
        var productPrices = <?php echo json_encode($prices); ?>;

        // Biểu đồ tròn: Phân bổ số lượng sản phẩm
        var pieCtx = document.getElementById('pieChart').getContext('2d');
        var pieChart = new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: productNames,
                datasets: [{
                    label: 'Số lượng sản phẩm',
                    data: productQuantities,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
                }]
            }
        });

        // Biểu đồ cột: Số lượng sản phẩm theo giá
        var barCtx = document.getElementById('barChart').getContext('2d');
        var barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: productNames,
                datasets: [{
                    label: 'Giá sản phẩm (USD)',
                    data: productPrices,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
                    borderColor: ['#FF6384', '#36A2EB', '#FFCE56'],
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
    </script>
</body>
</html>

<?php
// Đóng kết nối
sqlsrv_close($conn);
?>
