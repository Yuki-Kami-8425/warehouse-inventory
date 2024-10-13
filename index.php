<?php
// Kết nối đến cơ sở dữ liệu
$serverName = "YOUR_SERVER";
$connectionOptions = array(
    "Database" => "YOUR_DATABASE",
    "Uid" => "YOUR_USERNAME",
    "PWD" => "YOUR_PASSWORD"
);

// Xử lý thêm sản phẩm
if (isset($_POST['add_product'])) {
    $productName = $_POST['ProductName'];
    $quantity = $_POST['Quantity'];
    $location = $_POST['Location'];
    $price = $_POST['Price'];

    $sql = "INSERT INTO Products (ProductName, Quantity, Location, Price) VALUES (?, ?, ?, ?)";
    $params = array($productName, $quantity, $location, $price);
    
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt) {
        // Chuyển hướng sau khi thêm sản phẩm
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Có lỗi khi thêm sản phẩm: " . sqlsrv_errors();
    }

    sqlsrv_close($conn);
}

// Kết nối lại để lấy danh sách sản phẩm
$conn = sqlsrv_connect($serverName, $connectionOptions);
$sql = "SELECT * FROM Products";
$stmt = sqlsrv_query($conn, $sql);

// Lấy tên sản phẩm và số lượng cho biểu đồ
$productNames = [];
$quantities = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $productNames[] = $row['ProductName'];
    $quantities[] = $row['Quantity'];
}
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
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
        // Kết nối lại để lấy danh sách sản phẩm
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        $stmt = sqlsrv_query($conn, $sql);
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
        sqlsrv_close($conn);
        ?>
    </table>

    <h1>Biểu đồ số lượng sản phẩm</h1>
    <canvas id="myChart" width="400" height="200"></canvas>
    
    <h1>Biểu đồ tròn số lượng sản phẩm</h1>
    <canvas id="pieChart" width="400" height="200"></canvas>

    <script>
        var productNames = <?php echo json_encode($productNames); ?>;
        var quantities = <?php echo json_encode($quantities); ?>;

        // Biểu đồ cột
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: productNames,
                datasets: [{
                    label: 'Số lượng sản phẩm',
                    data: quantities,
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

        // Biểu đồ tròn
        var ctxPie = document.getElementById('pieChart').getContext('2d');
        var pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: productNames,
                datasets: [{
                    label: 'Tỷ lệ số lượng sản phẩm',
                    data: quantities,
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
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Tỷ lệ số lượng sản phẩm'
                    }
                }
            }
        });
    </script>
</body>
</html>
