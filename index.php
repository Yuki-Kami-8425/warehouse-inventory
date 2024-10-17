<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Smart Warehouse</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <a href="#" id="homeLink">Home</a>
        <a href="#" id="dashboardLink">Dashboard</a>
        <a href="#" id="editWarehouseLink">Edit Warehouse</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div id="home" class="content active">
            <h1>Welcome to Smart Warehouse</h1>
            <div class="slideshow-container">
                <div class="slides fade">
                    <img src="warehouse1.jpg" alt="Warehouse Image 1">
                </div>
                <div class="slides fade">
                    <img src="warehouse2.jpg" alt="Warehouse Image 2">
                </div>
                <div class="slides fade">
                    <img src="warehouse3.jpg" alt="Warehouse Image 3">
                </div>
            </div>
            <div class="dots-container">
                <span class="dot" onclick="currentSlide(1)"></span>
                <span class="dot" onclick="currentSlide(2)"></span>
                <span class="dot" onclick="currentSlide(3)"></span>
            </div>
        </div>

        <div id="dashboard" class="content">
            <h1>Dashboard</h1>
            <?php
                // Sample PHP code to fetch data (replace with actual database query)
                echo "<p>Displaying warehouse dashboard data...</p>";
            ?>
        </div>

        <div id="editWarehouse" class="content">
            <h1>Edit Warehouse</h1>
            <p>Edit warehouse details here.</p>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
