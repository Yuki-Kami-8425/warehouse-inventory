<?php 
// Thông tin kết nối cơ sở dữ liệu Azure SQL
$serverName = getenv('AZURE_SQL_SERVER') ?: "eiusmartwarehouse.database.windows.net";
$connectionOptions = [
    "Database" => getenv('AZURE_SQL_DATABASE') ?: "eiu_warehouse_24",
    "Uid" => getenv('AZURE_SQL_USER') ?: "eiuadmin",
    "PWD" => getenv('AZURE_SQL_PASSWORD') ?: "Khoa123456789"
];

// Kết nối đến cơ sở dữ liệu
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

$message = "";

// Thêm sản phẩm
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $productName = trim($_POST['ProductName']);
    $quantity = intval($_POST['Quantity']);
    $location = trim($_POST['Location']);
    $price = floatval($_POST['Price']);
    $lastUpdated = date('Y-m-d H:i:s');

    if (empty($productName) || empty($location) || $quantity < 0 || $price < 0) {
        $message = "Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.";
    } else {
        $sql = "INSERT INTO dbo.Products (ProductName, Quantity, Location, Price, LastUpdated) VALUES (?, ?, ?, ?, ?)";
        $params = [$productName, $quantity, $location, $price, $lastUpdated];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $message = "Lỗi khi thêm sản phẩm: " . print_r(sqlsrv_errors(), true);
        } else {
            $message = "Sản phẩm mới đã được thêm thành công.";
        }
    }
}

// Xoá sản phẩm
if (isset($_GET['delete'])) {
    $productID = intval($_GET['delete']);
    if ($productID > 0) {
        $sql = "DELETE FROM dbo.Products WHERE ProductID = ?";
        $params = [$productID];
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            $message = "Lỗi khi xoá sản phẩm: " . print_r(sqlsrv_errors(), true);
        } else {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $message = "ID sản phẩm không hợp lệ.";
    }
}

// Lấy danh sách sản phẩm
$sql = "SELECT * FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Warehouse</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Sidebar and Content -->
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
             <h2>Biểu đồ</h2>
        <canvas id="myChart"></canvas></div>

        <div id="edit-warehouse" class="page" style="display:none;">
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
        $sql = "SELECT ProductID, ProductName, Quantity, Location, Price, LastUpdated FROM Products";
        $stmt = sqlsrv_query($conn, $sql);
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['ProductID'] . "</td>";
            echo "<td>" . $row['ProductName'] . "</td>";
            echo "<td>" . $row['Quantity'] . "</td>";
            echo "<td>" . $row['Location'] . "</td>";
            echo "<td>" . $row['Price'] . "</td>";
            echo "<td>" . $row['LastUpdated']->format('Y-m-d H:i:s') . "</td>";
            echo "<td><a href='?delete=" . $row['ProductID'] . "' onclick='return confirm(\"Bạn có chắc chắn muốn xoá sản phẩm này?\");'>Xoá</a></td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

    </div>

    <h2>Biểu đồ</h2>
    <canvas id="myChart"></canvas>
    <script>
        const productNames = <?php echo json_encode($productNames); ?>;
        const quantities = <?php echo json_encode($quantities); ?>;

        const ctx = document.getElementById('myChart').getContext('2d');
        const myChart = new Chart(ctx, {
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
    </script>
</body>
</html>
