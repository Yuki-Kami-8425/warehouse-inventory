<?php
// Kết nối cơ sở dữ liệu Azure SQL
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

// Kiểm tra kết nối
if ($conn === false) {
    die(json_encode(["error" => sqlsrv_errors()]));
}

// Lấy danh sách sản phẩm từ cơ sở dữ liệu
$sql = "SELECT * FROM dbo.Products";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra nếu truy vấn trả về lỗi
if ($stmt === false) {
    die(json_encode(["error" => sqlsrv_errors()]));
}

// Tạo mảng chứa dữ liệu sản phẩm
$products = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $row['LastUpdated'] = $row['LastUpdated']->format('Y-m-d H:i:s'); // Định dạng thời gian
    $products[] = $row;
}

// Đảm bảo không có bất kỳ đầu ra nào trước json_encode
header('Content-Type: application/json');
echo json_encode($products);

// Đóng kết nối
sqlsrv_close($conn);
?>


<!DOCTYPE html>
<html>
<head>
    <title>Quản lý sản phẩm - Realtime</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Thêm jQuery -->
</head>
<body>
    <h1>Danh sách sản phẩm</h1>
    <table border="1" id="productTable">
        <tr>
            <th>ProductID</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Vị trí</th>
            <th>Giá</th>
            <th>Cập nhật lần cuối</th>
            <th>Hành động</th>
        </tr>
    </table>

    <h1>Biểu đồ số lượng sản phẩm</h1>
    <canvas id="myChart" width="400" height="200"></canvas>
    
    <h1>Biểu đồ tròn số lượng sản phẩm</h1>
    <canvas id="pieChart" width="400" height="200"></canvas>

    <script>
       function updateProductTable(products) {
    let tableContent = '';
    products.forEach(product => {
        tableContent += `
            <tr>
                <td>${product.ProductID}</td>
                <td>${product.ProductName}</td>
                <td>${product.Quantity}</td>
                <td>${product.Location}</td>
                <td>${product.Price}</td>
                <td>${product.LastUpdated}</td>
                <td><a href="?delete=${product.ProductID}">Xoá</a></td>
            </tr>
        `;
    });
    $('#productTable').html(`
        <tr>
            <th>ProductID</th>
            <th>Tên sản phẩm</th>
            <th>Số lượng</th>
            <th>Vị trí</th>
            <th>Giá</th>
            <th>Cập nhật lần cuối</th>
            <th>Hành động</th>
        </tr>
        ${tableContent}
    `);
}

function fetchProducts() {
    $.ajax({
        url: 'get_products.php',
        method: 'GET',
        dataType: 'json',
        success: function(products) {
            updateProductTable(products);
        },
        error: function(error) {
            console.log("Lỗi khi tải sản phẩm: ", error);
        }
    });
}

// Gọi hàm để lấy sản phẩm
fetchProducts();

       

        // Hàm để cập nhật biểu đồ
        function updateCharts(products) {
            const productNames = products.map(p => p.ProductName);
            const quantities = products.map(p => p.Quantity);

            // Cập nhật biểu đồ cột
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

            // Cập nhật biểu đồ tròn
            const ctxPie = document.getElementById('pieChart').getContext('2d');
            const pieChart = new Chart(ctxPie, {
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
        }

        // Hàm lấy dữ liệu sản phẩm từ server
        function fetchProducts() {
            $.ajax({
                url: 'get_products.php',
                method: 'GET',
                dataType: 'json',
                success: function(products) {
                    updateProductTable(products);
                    updateCharts(products);
                },
                error: function(error) {
                    console.log("Lỗi khi tải sản phẩm: ", error);
                }
            });
        }

        // Tự động tải lại dữ liệu mỗi 5 giây
        setInterval(fetchProducts, 5000);

        // Tải dữ liệu lần đầu khi trang vừa tải
        fetchProducts();
    </script>
</body>
</html>

