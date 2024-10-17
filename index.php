<?php
// Kết nối đến Azure SQL
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

// Lấy dữ liệu từ bảng warehouse_products
$sql = "SELECT * FROM warehouse_products";
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
    <title>Smart Warehouse</title>
    <style>
        /* General styles */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        /* Container for sidebar and main content */
        .container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar styles */
        .sidebar {
            width: 250px;
            background-color: #333;
            display: flex;
            flex-direction: column;
            padding: 10px;
            transition: width 0.3s;
        }

        .sidebar a {
            padding: 10px;
            text-decoration: none;
            color: white;
            display: block;
            transition: background-color 0.3s;
        }

        .sidebar a:hover {
            background-color: #575757;
        }

        /* Toggle button */
        .toggle-btn {
            background-color: #333;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .sidebar.collapsed {
            width: 60px;
        }

        .sidebar.collapsed a {
            display: none;
        }

        /* Main content */
        .main-content {
            flex: 1;
            background: url('warehouse-bg.jpg') no-repeat center center;
            background-size: cover;
            position: relative;
        }

        .main-content .section {
            display: none;
            padding: 20px;
        }

        .main-content .section.active {
            display: block;
        }

        /* Slideshow */
        .slideshow-container {
            position: relative;
            max-width: 100%;
            margin: auto;
        }

        .mySlides {
            display: none;
        }

        .mySlides img {
            width: 100%;
        }

        .fade {
            animation-name: fade;
            animation-duration: 1.5s;
        }

        @keyframes fade {
            from {opacity: .4} 
            to {opacity: 1}
        }

        /* Dots */
        .dot {
            height: 15px;
            width: 15px;
            margin: 0 2px;
            background-color: #bbb;
            border-radius: 50%;
            display: inline-block;
            transition: background-color 0.6s ease;
        }

        .active, .dot:hover {
            background-color: #717171;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <a href="#" onclick="showHome()">Home</a>
            <a href="#" onclick="showDashboard()">Dashboard</a>
            <a href="#" onclick="showEditWarehouse()">Edit Warehouse</a>
        </div>

        <!-- Main content -->
        <div class="main-content">
            <div id="home-section" class="section active">
                <!-- Slideshow -->
                <div class="slideshow-container">
                    <div class="mySlides fade">
                        <img src="warehouse1.jpg" style="width:100%">
                    </div>
                    <div class="mySlides fade">
                        <img src="warehouse2.jpg" style="width:100%">
                    </div>
                    <div class="mySlides fade">
                        <img src="warehouse3.jpg" style="width:100%">
                    </div>
                </div>
                <!-- Dots below slideshow -->
                <div style="text-align:center">
                    <span class="dot" onclick="currentSlide(1)"></span> 
                    <span class="dot" onclick="currentSlide(2)"></span> 
                    <span class="dot" onclick="currentSlide(3)"></span> 
                </div>
            </div>

            <div id="dashboard-section" class="section">
                <!-- Dashboard Data from Azure SQL -->
                <h2>Warehouse Dashboard</h2>
                <table>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                    </tr>
                    <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['product_id']; ?></td>
                            <td><?php echo $row['product_name']; ?></td>
                            <td><?php echo $row['quantity']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>

            <div id="edit-warehouse-section" class="section">
                <h2>Edit Warehouse</h2>
                <!-- Placeholder for edit warehouse feature -->
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar
        function toggleSidebar() {
            let sidebar = document.getElementById("sidebar");
            sidebar.classList.toggle("collapsed");
        }

        // Show sections
        function showHome() {
            document.getElementById("home-section").classList.add("active");
            document.getElementById("dashboard-section").classList.remove("active");
            document.getElementById("edit-warehouse-section").classList.remove("active");
        }

        function showDashboard() {
            document.getElementById("home-section").classList.remove("active");
            document.getElementById("dashboard-section").classList.add("active");
            document.getElementById("edit-warehouse-section").classList.remove("active");
        }

        function showEditWarehouse() {
            document.getElementById("home-section").classList.remove("active");
            document.getElementById("dashboard-section").classList.remove("active");
            document.getElementById("edit-warehouse-section").classList.add("active");
        }

        // Slideshow logic
        let slideIndex = 0;
        showSlides();

        function showSlides() {
            let i;
            let slides = document.getElementsByClassName("mySlides");
            let dots = document.getElementsByClassName("dot");
            for (i = 0; i < slides.length; i++) {
                slides[i].style.display = "none";
            }
            slideIndex++;
            if (slideIndex > slides.length) {slideIndex = 1}
            for (i = 0; i < dots.length; i++) {
                dots[i].className = dots[i].className.replace(" active", "");
            }
            slides[slideIndex-1].style.display = "block";
            dots[slideIndex-1].className += " active";
            setTimeout(showSlides, 5000); // Change image every 5 seconds
        }
    </script>
</body>
</html>

<?php
// Đóng kết nối SQL
sqlsrv_close($conn);
?>
