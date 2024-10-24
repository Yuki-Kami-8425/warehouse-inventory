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

// Lấy dữ liệu từ bảng cho trạm A
$sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Tạo mảng để lưu dữ liệu
$data = [];
$customers = [];
$highlighted = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
    $customers[$row['MAKH']][] = $row['RFID']; // Lưu danh sách RFID cho mỗi khách hàng
    $highlighted[] = trim($row['RFID']); // Dùng trim để loại bỏ khoảng trắng, giữ danh sách RFID để highlight
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management</title>
    <style>
        body {
            background-color: #001F3F; /* Xanh đậm */
            color: white; /* Màu chữ trắng */
            font-size: 8px; /* Kích thước chữ */
            display: flex;
            height: 100vh;
        }
        .sidebar {
            background-color: #001F3F; /* Màu nền sidebar */
            width: 200px; /* Độ rộng của sidebar */
            padding: 10px; /* Padding cho sidebar */
            position: fixed; /* Cố định sidebar */
            height: 100%; /* Chiều cao đầy đủ */
            overflow: auto; /* Thêm cuộn nếu cần */
        }
        .sidebar a {
            display: block;
            padding: 8px 16px;
            text-decoration: none;
            color: white;
        }
        .sidebar a:hover {
            background-color: #005F7F; /* Màu nền khi hover */
        }
        .sidebar .dropdown-btn {
            cursor: pointer;
        }
        .dropdown-content {
            display: none;
            padding-left: 15px;
        }
        .content {
            margin-left: 220px; /* Để nội dung không bị che bởi sidebar */
            padding: 20px;
            flex-grow: 1; /* Cho phép nội dung lấp đầy không gian còn lại */
        }
        h2 {
            text-align: center;
            font-size: 24px; /* Cỡ chữ tiêu đề lớn hơn */
        }
        table {
            width: 100%; /* Chiếm 100% chiều rộng */
            border-collapse: collapse;
            font-size: 8px; /* Kích thước chữ trong bảng */
            margin-top: 20px; /* Giãn cách trên bảng */
        }
        th, td {
            border: 2px solid white; /* Đường viền trắng */
            padding: 5px; /* Padding cho ô */
            text-align: center;
        }
        .slideshow-container {
            position: relative;
            max-width: 100%; /* Để slideshow chiếm 100% chiều rộng */
            margin: auto;
        }
        .mySlides {
            display: none; /* Ẩn tất cả các slide ban đầu */
        }
        .dot {
            height: 15px;
            width: 15px;
            margin: 0 2px;
            background-color: white;
            border-radius: 50%;
            display: inline-block;
            cursor: pointer;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="sidebar">
    <h2>Menu</h2>
    <a href="#" id="homeBtn">Home</a>
    <a href="#" class="dropdown-btn">Dashboard</a>
    <div class="dropdown-content">
        <a href="#" id="allBtn">All</a>
        <a href="#" id="stationA">Station A</a>
        <a href="#" id="stationB">Station B</a>
        <a href="#" id="stationC">Station C</a>
        <a href="#" id="stationD">Station D</a>
        <a href="#" id="stationE">Station E</a>
        <a href="#" id="stationF">Station F</a>
        <a href="#" id="stationG">Station G</a>
    </div>
    <a href="#" id="listBtn">List</a>
</div>

<div class="content">
    <div id="home">
        <div class="slideshow-container">
            <div class="mySlides fade">
                <img src="image1.jpg" style="width:100%">
            </div>
            <div class="mySlides fade">
                <img src="image2.jpg" style="width:100%">
            </div>
            <div class="mySlides fade">
                <img src="image3.jpg" style="width:100%">
            </div>
            <div class="mySlides fade">
                <img src="image4.jpg" style="width:100%">
            </div>
        </div>
        
        <div style="text-align:center">
            <span class="dot" onclick="currentSlide(1)"></span> 
            <span class="dot" onclick="currentSlide(2)"></span> 
            <span class="dot" onclick="currentSlide(3)"></span> 
            <span class="dot" onclick="currentSlide(4)"></span> 
        </div>
    </div>

    <div id="dashboard" style="display: none;">
        <!-- Biểu đồ và bảng cho các trạm sẽ hiển thị ở đây -->
    </div>

    <div id="list" style="display: none;">
        <table>
            <caption style="caption-side: top;">List of Items</caption>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Quantity</th>
            </tr>
            <?php
            // Kết nối lại để lấy dữ liệu cho bảng
            $conn = sqlsrv_connect($serverName, $connectionOptions);
            
            // Lấy dữ liệu từ bảng
            $sqlList = "SELECT ID, NAME, QUANTITY FROM dbo.items"; // Thay đổi tên bảng và cột tương ứng
            $stmtList = sqlsrv_query($conn, $sqlList);
            
            if ($stmtList === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            
            while ($rowList = sqlsrv_fetch_array($stmtList, SQLSRV_FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $rowList['ID'] . "</td>";
                echo "<td>" . $rowList['NAME'] . "</td>";
                echo "<td>" . $rowList['QUANTITY'] . "</td>";
                echo "</tr>";
            }
            
            // Đóng kết nối
            sqlsrv_close($conn);
            ?>
        </table>
    </div>
</div>

<script>
    let slideIndex = 0;
    showSlides();

    function showSlides() {
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");
        for (let i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";  
        }
        slideIndex++;
        if (slideIndex > slides.length) {slideIndex = 1}    
        for (let i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";  
        dots[slideIndex - 1].className += " active";
        setTimeout(showSlides, 10000); // Thay đổi sau mỗi 10 giây
    }

    function currentSlide(n) {
        slideIndex = n - 1; // Cập nhật chỉ số slide hiện tại
        showSlides();
    }

    // Logic cho các nút
    document.getElementById('homeBtn').addEventListener('click', function() {
        document.getElementById('home').style.display = 'block';
        document.getElementById('list').style.display = 'none';
        document.getElementById('dashboard').style.display = 'none';
    });

    document.getElementById('listBtn').addEventListener('click', function() {
        document.getElementById('home').style.display = 'none';
        document.getElementById('list').style.display = 'block';
        document.getElementById('dashboard').style.display = 'none';
    });
</script>

</body>
</html>
