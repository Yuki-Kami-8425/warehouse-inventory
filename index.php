<?php
// Kết nối đến cơ sở dữ liệu Azure SQL
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

// Tạo kết nối
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Xóa sản phẩm nếu có yêu cầu
if (isset($_GET['delete'])) {
    $productID = $_GET['delete'];
    $sql = "DELETE FROM dbo.Products WHERE ProductID = ?";
    $params = array($productID);
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Sản phẩm đã được xóa thành công.";
    }
}

// Xử lý thêm sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $productName = $_POST['ProductName'];
    $quantity = $_POST['Quantity'];
    $location = $_POST['Location'];
    $price = $_POST['Price'];
    $lastUpdated = date('Y-m-d H:i:s');

    $sql = "INSERT INTO dbo.Products (ProductName, Quantity, Location, Price, LastUpdated)
            VALUES (?, ?, ?, ?, ?)";
    $params = array($productName, $quantity, $location, $price, $lastUpdated);

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        echo "Sản phẩm mới đã được thêm thành công.";
    }
}

// Lấy dữ liệu từ bảng Products
$sql = "SELECT * FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
</head>
<body>

<h1>Quản lý kho hàng</h1>

<h2>Danh sách sản phẩm</h2>
<table border="1">
    <tr>
        <th>ProductID</th>
        <th>ProductName</th>
        <th>Quantity</th>
        <th>Location</th>
        <th>Price</th>
        <th>LastUpdated</th>
        <th>Hành động</th>
    </tr>

    <?php
    // Hiển thị dữ liệu sản phẩm
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['ProductID'] . "</td>";
        echo "<td>" . $row['ProductName'] . "</td>";
        echo "<td>" . $row['Quantity'] . "</td>";
        echo "<td>" . $row['Location'] . "</td>";
        echo "<td>" . $row['Price'] . "</td>";
        echo "<td>" . $row['LastUpdated']->format('Y-m-d H:i:s') . "</td>";
        echo "<td><a href='?delete=" . $row['ProductID'] . "' onclick='return confirm(\"Bạn có chắc muốn xóa sản phẩm này?\")'>Xóa</a></td>";
        echo "</tr>";
    }
    ?>
</table>

<h2>Thêm sản phẩm mới</h2>
<form method="post" action="">
    <label for="ProductName">Tên sản phẩm:</label>
    <input type="text" name="ProductName" required><br><br>

    <label for="Quantity">Số lượng:</label>
    <input type="number" name="Quantity" required><br><br>

    <label for="Location">Vị trí:</label>
    <input type="text" name="Location" required><br><br>

    <label for="Price">Giá:</label>
    <input type="text" name="Price" required><br><br>

    <input type="submit" value="Thêm sản phẩm">
</form>

</body>
</html>

<?php
// Đóng kết nối
sqlsrv_close($conn);
?>
