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

// Lấy danh sách sản phẩm để hiển thị trong bảng
$sql = "SELECT * FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu cho biểu đồ
$productNames = [];
$quantities = [];

// Lấy dữ liệu cho biểu đồ
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $productNames[] = $row['ProductName'];
    $quantities[] = $row['Quantity'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Thêm sản phẩm mới</h1>
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

    <h1>Danh sách sản phẩm</h1>
    <table border="1">
        <tr>
            <th>ProductID</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Vị trí</th>
            <th>Giá</th>
            <th>Cập nhật lần cuối</th>
            <th>Hành động</th>
        </tr>

        <?php
        // Hiển thị danh sách sản phẩm
        sqlsrv_execute($stmt); // Thực thi lại truy vấn
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['ProductID'] . "</td>";
            echo "<td>" . $row['ProductName'] . "</td>";
            echo "<td>" . $row['Quantity'] . "</td>";
            echo "<td>" . $row['Location'] . "</td>";
            echo "<td>" . $row['Price'] . "</td>";
            echo "<td>" . $row['LastUpdated']->format('Y-m-d H:i:s') . "</td>";
            echo "<td><a href='?delete=" . $row['ProductID'] . "'>Xoá</a></td>";
            echo "</tr>";
        }
        ?>
    </table>

    <h1>Biểu đồ sản phẩm</h1>
    <canvas id="myChart" width="400" height="200"></canvas>
    <script>
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($productNames); ?>,
                datasets: [{
                    label: 'Số lượng sản phẩm',
                    data: <?php echo json_encode($quantities); ?>,
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
    </script>
</body>
</html>

<?php
// Đóng kết nối
sqlsrv_close($conn);
?>
