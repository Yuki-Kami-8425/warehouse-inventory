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
    
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #004080; /* Màu nền xanh đậm */
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            background-color: #2c3e50; /* Màu nền thanh bên */
            padding-top: 60px;
            transition: width 0.3s;
        }

        .slide:hover {
            transform: scale(1.02); /* Phóng to một chút */
            transition: transform 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
            padding: 20px 0;
            text-align: center;
        }

        .slide-title {
            font-size: 24px; /* Kích thước chữ cho tiêu đề */
            color: white; /* Màu chữ */
            margin-bottom: 10px; /* Khoảng cách giữa tiêu đề và hình ảnh */
        }

        .slide {
            display: none; /* Ẩn tất cả các slide mặc định */
            position: relative; /* Để có thể căn chỉnh các thành phần bên trong */
        }

        .slide img {
            width: 650px; /* Chiều rộng cố định */
            height: 350px; /* Chiều cao cố định */
            object-fit: fill; /* Kéo giãn ảnh để lấp đầy khung */
        }

        .dots {
            position: relative; /* Để căn giữa dấu chấm */
            text-align: center; /* Căn giữa dấu chấm */
            margin-top: 10px; /* Khoảng cách giữa chữ và dấu chấm */
        }

        .dot {
            height: 10px; /* Kích thước dấu chấm */
            width: 10px; /* Kích thước dấu chấm */
            margin: 0 5px; /* Khoảng cách giữa các dấu chấm */
            background-color: white; /* Màu trắng */
            border-radius: 50%; /* Đường viền tròn */
            display: inline-block; /* Hiển thị thành dòng ngang */
            cursor: pointer; /* Con trỏ khi hover vào */
            transition: all 0.3s; /* Hiệu ứng chuyển tiếp */
        }

        .dot.active {
            height: 15px; /* Kích thước lớn hơn khi được chọn */
            width: 15px; /* Kích thước lớn hơn khi được chọn */
            background-color: #00BFFF; /* Màu xanh lam khi được chọn */
        }

        .dot:hover {
            background-color: #00BFFF; /* Màu nền khi hover */
            transform: scale(1.2); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .sidebar ul li a.active {
            color: #00BFFF; /* Màu xanh lam khi được chọn */
            background-color: rgba(255, 255, 255, 0.1); /* Màu nền khi được chọn (tùy chọn) */
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 10px;
            text-align: center;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s; /* Thêm hiệu ứng chuyển màu */
        }

        .sidebar ul li a:hover {
            background-color: rgba(255, 255, 255, 0.2); /* Màu nền khi hover */
            color: #32CD32; /* Màu lục tươi khi đưa chuột vào */
            transform: scale(1.05); /* Phóng to một chút */
            transition: all 0.3s; /* Thêm hiệu ứng chuyển tiếp */
        }

        .sidebar ul li a.selected {
            color: #00BFFF; /* Màu xanh lam khi được chọn */
        }

        .sidebar ul li a i {
            margin-right: 10px;
            transition: color 0.3s; /* Thêm hiệu ứng chuyển màu */
        }

        /* Áp dụng màu xanh lam cho icon khi được chọn */
        .sidebar ul li a.selected i {
            color: #00BFFF;
        }

        .content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .page {
            margin-top: 20px;
            font-size: 24px;
            color: white;
        }

        /* Hidden sidebar */
        .sidebar.collapsed {
            width: 70px;
        }

        .content.collapsed {
            margin-left: 70px;
        }

        .sidebar.collapsed ul li a {
            justify-content: center;
        }

        .sidebar.collapsed ul li a span {
            display: none;
        }

        .sidebar ul li a i {
            font-size: 20px; /* Kích thước icon */
        }

        .datetime {
            position: fixed;
            bottom: 10px;
            left: 10px;
            color: white; /* Màu chữ cho ngày giờ */
            font-size: 16px; /* Kích thước chữ */
        }
    </style>

    <script>
        // Hàm hiển thị slide đầu tiên
        let slideIndex = 1;
        showSlide(slideIndex);

        // Hàm để chuyển đến slide tiếp theo hoặc trước
        function showSlide(n) {
            const slides = document.getElementsByClassName("slide");
            if (n > slides.length) slideIndex = 1;
            if (n < 1) slideIndex = slides.length;

            for (let i = 0; i < slides.length; i++) {
                slides[i].style.display = "none"; // Ẩn tất cả các slide
            }
            slides[slideIndex - 1].style.display = "block"; // Hiển thị slide hiện tại

            // Cập nhật dấu chấm
            const dots = document.getElementsByClassName("dot");
            for (let i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", ""); // Ẩn dấu chấm
            }
            dots[slideIndex - 1].className += " active"; // Hiển thị dấu chấm hiện tại
        }

        // Hàm chuyển slide tự động mỗi 5 giây
        setInterval(function() {
            showSlide(slideIndex += 1);
        }, 5000);

        // Hàm chuyển trang
        function showPage(page) {
            const pages = document.getElementsByClassName("page");
            for (let i = 0; i < pages.length; i++) {
                pages[i].style.display = "none"; // Ẩn tất cả các trang
            }
            document.getElementById(page).style.display = "block"; // Hiển thị trang được chọn
        }

        // Hàm toggle thanh bên
        function toggleSidebar() {
            const sidebar = document.getElementById("sidebar");
            const content = document.querySelector(".content");
            sidebar.classList.toggle("collapsed");
            content.classList.toggle("collapsed");
        }

        // Hàm chuyển trạng thái các trạm
        function toggleStations() {
            const stationList = document.querySelector(".station-list");
            stationList.style.display = (stationList.style.display === "block") ? "none" : "block";
        }

        // Cập nhật thời gian hiện tại
        function updateDateTime() {
            const now = new Date();
            const options = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const dateTimeString = now.toLocaleString('vi-VN', options);
            document.getElementById("datetime").innerText = dateTimeString; // Cập nhật nội dung phần tử
        }

        // Cập nhật thời gian mỗi giây
        setInterval(updateDateTime, 1000);
    </script>
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
