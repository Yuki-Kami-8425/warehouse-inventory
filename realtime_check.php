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

// Truy vấn tổng hợp để tính checksum
$sql = "SELECT COUNT(*) AS record_count, SUM(CHECKSUM(MAKH, TENKH, LUONG_PALLET, RFID)) AS checksum FROM dbo.stored_warehouse";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_close($conn);

// Trả về checksum dưới dạng JSON
echo json_encode([
    'record_count' => $result['record_count'],
    'checksum' => $result['checksum']
]);
