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
        }
        h2 {
            text-align: center;
            font-size: 24px; /* Cỡ chữ tiêu đề lớn hơn */
        }
        .button-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .button {
            background-color: #007BFF; /* Màu nền nút */
            color: white; /* Màu chữ nút */
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .button:hover {
            background-color: #0056b3; /* Màu nền khi hover */
        }
        /* Các style khác vẫn giữ nguyên */
    </style>
</head>
<body>

<h2>Warehouse Management</h2>

<div class="button-container">
    <button class="button" onclick="showAll()">All</button>
    <button class="button" onclick="showStation('A')">Station A</button>
    <button class="button" onclick="showStation('B')">Station B</button>
    <button class="button" onclick="showStation('C')">Station C</button>
    <button class="button" onclick="showStation('D')">Station D</button>
    <button class="button" onclick="showStation('E')">Station E</button>
    <button class="button" onclick="showStation('F')">Station F</button>
    <button class="button" onclick="showStation('G')">Station G</button>
</div>

<div class="container" id="warehouseData">
    <!-- Các bảng và biểu đồ sẽ được hiển thị ở đây -->
</div>

<script>
function showAll() {
    document.getElementById('warehouseData').innerHTML = `<?php echo getAllData(); ?>`;
}

function showStation(station) {
    document.getElementById('warehouseData').innerHTML = `<?php echo getStationData(station); ?>`;
}

// Hàm PHP để lấy dữ liệu cho tất cả các trạm
function getAllData() {
    // Thực hiện truy vấn và trả về HTML cho tất cả các trạm
    $html = '';
    foreach (range('A', 'G') as $station) {
        $html .= '<h3>Warehouse Station ' . $station . '</h3>';
        // Thực hiện truy vấn để lấy dữ liệu của từng trạm
        // (Thêm mã truy vấn tương tự như đã làm cho trạm A)
    }
    return $html;
}

// Hàm PHP để lấy dữ liệu cho một trạm cụ thể
function getStationData($station) {
    $sql = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM dbo.stored_warehouse WHERE RFID LIKE '$station%'";
    // Thực hiện truy vấn và tạo HTML tương tự như ở trên
}
</script>

</body>
</html>
