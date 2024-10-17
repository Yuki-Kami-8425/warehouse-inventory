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

// Xử lý thêm sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productName = trim($_POST['ProductName']);
    $quantity = intval($_POST['Quantity']);
    $location = trim($_POST['Location']);
    $price = floatval($_POST['Price']);
    $lastUpdated = date('Y-m-d H:i:s');

    // Kiểm tra dữ liệu đầu vào
    if (empty($productName) || empty($location) || $quantity < 0 || $price < 0) {
        $message = "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.";
    } else {
        // Câu lệnh SQL thêm sản phẩm mới (bỏ qua ProductID)
        $sql = "INSERT INTO dbo.Products (ProductName, Quantity, Location, Price, LastUpdated)
                VALUES (?, ?, ?, ?, ?)";
        $params = array($productName, $quantity, $location, $price, $lastUpdated);

        // Thực thi câu lệnh
        $stmt = sqlsrv_query($conn, $sql, $params);

        // Kiểm tra kết quả
        if ($stmt === false) {
            $message = "Lỗi khi thêm sản phẩm: " . print_r(sqlsrv_errors(), true);
        } else {
            $message = "Sản phẩm mới đã được thêm thành công.";
        }
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
            $message = "Lỗi khi xoá sản phẩm: " . print_r(sqlsrv_errors(), true);
        } else {
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect để tránh việc gửi lại form
            exit;
        }
    } else {
        $message = "ID sản phẩm không hợp lệ.";
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

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="sidebar" id="sidebar">
        <button class="toggle-btn" onclick="toggleSidebar()">&#9776;</button>
        <ul>
            <li><a href="#" onclick="showPage('home')"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
            <li><a href="#" onclick="showPage('dashboard')"><i class="fas fa-tachometer-alt"></i><span class="link-text"> Dashboard</span></a></li>
            <li><a href="#" onclick="showPage('edit-warehouse')"><i class="fas fa-edit"></i><span class="link-text"> Edit Warehouse</span></a></li>
        </ul>
    </div>

    <div class="content">
        <div id="home" class="page">
            <div class="slideshow-container">
                <div class="slideshow">
                    <img class="slide" src="warehouse1.jpg" alt="Warehouse 1">
                    <img class="slide" src="warehouse2.jpg" alt="Warehouse 2">
                    <img class="slide" src="warehouse3.jpg" alt="Warehouse 3">
                </div>
                <div class="dots">
                    <span class="dot" onclick="showSlide(0)"></span>
                    <span class="dot" onclick="showSlide(1)"></span>
                    <span class="dot" onclick="showSlide(2)"></span>
                </div>
            </div>
        </div>

        <div id="dashboard" class="page" style="display:none;">
        <h1>Quản lý sản phẩm</h1>
    <p style="color: red;"><?php echo $message; ?></p>

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

        <input type="submit" name="add_product" value="Thêm sản phẩm">
    </form>

    <h2>Danh sách sản phẩm</h2>
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
        </div>



        <div id="edit-warehouse" class="page" style="display:none;">
        <h2>Biểu đồ số lượng sản phẩm</h2>
    <canvas id="myChart" width="400" height="200"></canvas>

    <h2>Biểu đồ tròn số lượng sản phẩm</h2>
    <canvas id="pieChart" width="400" height="200"></canvas>

    <script>
        var productNames = <?php echo json_encode($productNames); ?>;
        var quantities = <?php echo json_encode($quantities); ?>;

        // Kiểm tra dữ liệu trong console
        console.log(productNames);
        console.log(quantities);

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
                responsive: true
            }
        });
    </script>
        </div>

    </div>

<script src="script.js"></script>

</body>
</html>