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
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Smart Warehouse</title>
        <link rel="stylesheet" href="styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <script src="script.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    </head>

    <body>
        <div class="sidebar" id="sidebar">
                <button class="toggle-btn" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <ul>
                    <li><a href="#" onclick="showPage('home');" class="main-link"><i class="fas fa-home"></i><span class="link-text"> Home</span></a></li>
                    <li>
                        <a href="#" onclick="toggleStations(); showPage('dashboard');" class="main-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="link-text"> Dashboard</span>
                        </a>
                        <ul class="station-list">
                            <li><a href="#" onclick="showPage('all');" class="station-link"><i class="fas fa-th-list"></i> <span class="link-text">All</span></a></li>
                            <li><a href="#" onclick="showPage('station1');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 1</span></a></li>
                            <li><a href="#" onclick="showPage('station2');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 2</span></a></li>
                            <li><a href="#" onclick="showPage('station3');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 3</span></a></li>
                            <li><a href="#" onclick="showPage('station4');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 4</span></a></li>
                            <li><a href="#" onclick="showPage('station5');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 5</span></a></li>
                            <li><a href="#" onclick="showPage('station6');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 6</span></a></li>
                            <li><a href="#" onclick="showPage('station7');" class="station-link"><i class="fas fa-industry"></i> <span class="link-text">Station 7</span></a></li>
                        </ul>
                    </li>
                    <li><a href="#" onclick="showPage('edit-warehouse');" class="main-link"><i class="fas fa-edit"></i><span class="link-text"> Edit</span></a></li>
                </ul>

                <div id="datetime" class="datetime"></div>
            </div>

            <div class="content">
                <div id="home" class="page">
                    <div class="slideshow-container">
                        <div class="slide">
                            <h2 class="slide-title">Tiêu đề cho Hình 1</h2>
                            <img class="slide-image" src="Picture1.png" alt="Slide 1">
                        </div>
                        <div class="slide">
                            <h2 class="slide-title">Tiêu đề cho Hình 2</h2>
                            <img class="slide-image" src="Picture2.png" alt="Slide 2">
                        </div>
                        <div class="slide">
                            <h2 class="slide-title">Tiêu đề cho Hình 3</h2>
                            <img class="slide-image" src="Picture3.png" alt="Slide 3">
                        </div>
                    
                        <div class="dots">
                            <span class="dot" onclick="showSlide(1)"></span>
                            <span class="dot" onclick="showSlide(2)"></span>
                            <span class="dot" onclick="showSlide(3)"></span>
                        </div>
                    </div>
                    
                </div>

                <div id="dashboard" class="page" style="display:none;">Dashboard will be here.</div>
                <div id="edit-warehouse" class="page" style="display:none;">Edit Warehouse will be here.</div>
                <div id="all" class="page" style="display:none;">All stations content will be here.</div>
                <div id="station1" class="page" style="display:none;">Station 1 content will be here.</div>
                <div id="station2" class="page" style="display:none;">Station 2 content will be here.</div>
                <div id="station3" class="page" style="display:none;">Station 3 content will be here.</div>
                <div id="station4" class="page" style="display:none;">Station 4 content will be here.</div>
                <div id="station5" class="page" style="display:none;">Station 5 content will be here.</div>
                <div id="station6" class="page" style="display:none;">Station 6 content will be here.</div>
                <div id="station7" class="page" style="display:none;">Station 7 content will be here.</div>
        </div>
    </body>
</html>