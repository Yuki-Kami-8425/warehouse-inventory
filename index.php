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
    $productName = htmlspecialchars(trim($_POST['ProductName']));
    $quantity = intval($_POST['Quantity']);
    $location = htmlspecialchars(trim($_POST['Location']));
    $price = floatval($_POST['Price']);
    $lastUpdated = date('Y-m-d H:i:s');

    // Câu lệnh SQL thêm sản phẩm mới (bỏ qua ProductID)
    $sql = "INSERT INTO dbo.Products (ProductName, Quantity, Location, Price, LastUpdated)
            VALUES (?, ?, ?, ?, ?)";
    $params = array($productName, $quantity, $location, $price, $lastUpdated);

    // Thực thi câu lệnh
    $stmt = sqlsrv_query($conn, $sql, $params);

    // Kiểm tra kết quả
    if ($stmt === false) {
        error_log("Lỗi khi thêm sản phẩm: " . print_r(sqlsrv_errors(), true)); // Log error
        echo "Lỗi khi thêm sản phẩm.";
    } else {
        header("Location: " . $_SERVER['PHP_SELF']); // Redirect to avoid form resubmission
        exit;
    }
}

// Xử lý xoá sản phẩm
if (isset($_GET['delete'])) {
    $productID = intval($_GET['delete']); // Đảm bảo ID là số nguyên

    // Kiểm tra giá trị productID
    if ($productID > 0) {
        // Câu lệnh SQL xoá sản phẩm
        $sql = "DELETE FROM dbo.Products WHERE ProductID = ?";
        $params = array($productID);

        // Thực thi câu lệnh
        $stmt = sqlsrv_query($conn, $sql, $params);

        // Kiểm tra kết quả
        if ($stmt === false) {
            error_log("Lỗi khi xoá sản phẩm: " . print_r(sqlsrv_errors(), true)); // Log error
            echo "Lỗi khi xoá sản phẩm.";
        } else {
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect để tránh việc gửi lại form
            exit;
        }
    } else {
        echo "ID sản phẩm không hợp lệ.";
    }
}


// Lấy danh sách sản phẩm để hiển thị trong bảng
$sql = "SELECT * FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Quản lý sản phẩm</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
    </style>
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
    <table>
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
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ProductID']) . "</td>";
            echo "<td>" . htmlspecialchars($row['ProductName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Quantity']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Location']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Price']) . "</td>";
            echo "<td>" . htmlspecialchars($row['LastUpdated']->format('Y-m-d H:i:s')) . "</td>";
            echo "<td><a href='?delete=" . htmlspecialchars($row['ProductID']) . "'>Xoá</a></td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
// Đóng kết nối
sqlsrv_close($conn);
?>
