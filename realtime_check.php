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

$sql = "SELECT last_modified FROM dbo.change_status";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(json_encode(["error" => sqlsrv_errors()]));
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo json_encode(["last_modified" => $row['last_modified']]);

sqlsrv_close($conn);
?>
