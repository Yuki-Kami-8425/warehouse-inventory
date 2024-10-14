<?php
header('Content-Type: application/json');

$serverName = "eiusmartwarehouse.database.windows.net";  // Thay thế bằng thông tin thực tế
$username = "eiuadmin";
$password = "Khoa123456789";
$database = "eiu_warehouse_24";

try {
    // Kết nối với SQL Server
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Truy xuất dữ liệu hàng hóa
    $query = "SELECT * FROM inventory";  // Thay đổi tên bảng nếu cần
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Trả về JSON
    echo json_encode($result);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;

