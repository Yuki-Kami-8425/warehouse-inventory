<?php
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

// Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch warehouse data
$sql = "SELECT * FROM warehouse_products";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo "<h2>Warehouse Dashboard</h2>";
echo "<table border='1'>
<tr>
<th>Product ID</th>
<th>Product Name</th>
<th>Quantity</th>
</tr>";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['product_id'] . "</td>";
    echo "<td>" . $row['product_name'] . "</td>";
    echo "<td>" . $row['quantity'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Close the connection
sqlsrv_close($conn);
?>
