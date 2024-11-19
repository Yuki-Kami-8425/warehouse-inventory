<?php
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(json_encode(["error" => sqlsrv_errors()]));
}

// Tính toán checksum cho toàn bộ cột
$sql = "SELECT CHECKSUM_AGG(BINARY_CHECKSUM(SOCT, NGAYCT, MAKH, TENKH, MASP, TENSP, DONVI, LUONG_PALLET, RFID, PALLET_status)) AS checksum
        FROM dbo.stored_warehouse";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(json_encode(["error" => sqlsrv_errors()]));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_close($conn);

echo json_encode(["checksum" => $row['checksum']]);
?>
