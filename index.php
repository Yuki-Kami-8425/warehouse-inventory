<?php 
// Thông tin kết nối cơ sở dữ liệu Azure SQL
$serverName = "eiusmartwarehouse.database.windows.net";
$connectionOptions = array(
    "Database" => "eiu_warehouse_24",
    "Uid" => "eiuadmin",
    "PWD" => "Khoa123456789"
);

// Kết nối đến cơ sở dữ liệu
$conn = sqlsrv_connect($serverName, $connectionOptions);

// Kiểm tra kết nối
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Truy vấn để lấy dữ liệu cho trạm A
$query = "SELECT MAKH, TENKH, LUONG_PALLET, RFID FROM YourTableName WHERE RFID LIKE 'A%'";
$result = sqlsrv_query($conn, $query);

// Lưu dữ liệu vào mảng
$data = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $data[] = $row;
}

// Tính số lượng pallet theo khách hàng
$customerData = [];
foreach ($data as $row) {
    $makh = $row['MAKH'];
    $tenkh = $row['TENKH'];
    $luong_pallet = $row['LUONG_PALLET'];

    if (!isset($customerData[$makh])) {
        $customerData[$makh] = [
            'tenkh' => $tenkh,
            'total' => 0,
        ];
    }
    $customerData[$makh]['total'] += $luong_pallet;
}

// Biểu đồ cột
$labels = [];
$values = [];
foreach ($customerData as $customer) {
    $labels[] = $customer['tenkh'];
    $values[] = $customer['total'];
}

// Biểu đồ tròn tổng số kho
$totalStorage = 196; // Tổng số kho cho trạm A

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý trạm A</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #003366; /* Màu nền xanh đậm */
            color: white; /* Màu chữ trắng */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid white;
            padding: 10px;
            text-align: center;
        }
        .highlight {
            background-color: #FFD700; /* Màu sáng cho ô phát sáng */
        }
    </style>
</head>
<body>
    <h1 style="font-size: 24px;">Warehouse Management - Station A</h1>

    <table>
        <thead>
            <tr>
                <th>RFID</th>
                <th>Tên Khách Hàng</th>
                <th>Số Lượng Pallet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr class="<?= in_array($row['RFID'], array_column($data, 'RFID')) ? 'highlight' : ''; ?>">
                    <td><?= $row['RFID']; ?></td>
                    <td><?= $row['TENKH']; ?></td>
                    <td><?= $row['LUONG_PALLET']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <canvas id="barChart" width="400" height="200"></canvas>
    <canvas id="pieChart" width="400" height="200"></canvas>

    <script>
        // Biểu đồ cột
        const ctxBar = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels); ?>,
                datasets: [{
                    label: 'Số Lượng Pallet',
                    data: <?= json_encode($values); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Chia đơn vị là 1
                        }
                    }
                }
            }
        });

        // Biểu đồ tròn
        const ctxPie = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Sử dụng kho', 'Còn trống'],
                datasets: [{
                    label: 'Tổng Số Kho',
                    data: [<?= array_sum($values); ?>, <?= $totalStorage - array_sum($values); ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });
    </script>
</body>
</html>

<?php
// Đóng kết nối
sqlsrv_close($conn);
?>
