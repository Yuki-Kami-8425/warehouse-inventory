<?php
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
); 
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

// Truy vấn lấy bản ghi mới nhất
$sql = "SELECT TOP 1 SOCT, NGAYCT, MAKH, TENKH, MASP, TENSP, DONVI, LUONG_PALLET, RFID, PALLET_status
        FROM dbo.stored_warehouse
        ORDER BY NGAYCT DESC, SOCT DESC";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_close($conn);

// Trả về JSON chứa thông tin bản ghi mới nhất
echo json_encode([
    'lastRecord' => $row
]);
