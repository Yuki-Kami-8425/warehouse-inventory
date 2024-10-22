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

// Lấy danh sách khách hàng ở trạm A
$sqlCustomers = "SELECT DISTINCT TENKH FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmtCustomers = sqlsrv_query($conn, $sqlCustomers);
$customerCount = 0;

if ($stmtCustomers !== false) {
    while (sqlsrv_fetch($stmtCustomers) !== false) {
        $customerCount++;
    }
}

// Tính số ô đã sử dụng
$occupiedSlots = 0;
$sqlOccupied = "SELECT COUNT(*) AS occupied_count FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmtOccupied = sqlsrv_query($conn, $sqlOccupied);
if ($stmtOccupied !== false) {
    $row = sqlsrv_fetch_array($stmtOccupied, SQLSRV_FETCH_ASSOC);
    $occupiedSlots = $row['occupied_count'];
}

// Tính tổng số ô
$totalSlots = 196;

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Warehouse A Statistics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #003366; /* Màu nền xanh đậm */
            color: white; /* Màu chữ trắng */
            font-family: Arial, sans-serif;
        }
        .table-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        table {
            width: 45%;
            margin: 10px;
            border-collapse: collapse;
        }
        th, td {
            width: 14.28%; /* Chia đều 7 cột */
            padding: 10px;
            text-align: center;
            border: 1px solid white; /* Viền trắng */
        }
        .highlight {
            background-color: #ffcc00; /* Màu high-light cho ô có RFID */
        }
        canvas {
            width: 45%; /* Thay đổi kích thước biểu đồ */
            height: 200px; /* Chiều cao biểu đồ */
        }
    </style>
</head>
<body>

<h2>Warehouse A Statistics</h2>

<div class="table-container">
    <div>
        <h3>Left Rack</h3>
        <table>
            <tr>
                <td class="highlight">AL85</td>
                <td>AL86</td>
                <td>AL87</td>
                <td>AL88</td>
                <td>AL89</td>
                <td>AL90</td>
                <td>AL91</td>
            </tr>
            <tr>
                <td>AL71</td>
                <td>AL72</td>
                <td>AL73</td>
                <td>AL74</td>
                <td>AL75</td>
                <td>AL76</td>
                <td>AL77</td>
            </tr>
            <tr>
                <td>AL57</td>
                <td>AL58</td>
                <td>AL59</td>
                <td>AL60</td>
                <td>AL61</td>
                <td>AL62</td>
                <td>AL63</td>
            </tr>
            <tr>
                <td>AL43</td>
                <td>AL44</td>
                <td>AL45</td>
                <td>AL46</td>
                <td>AL47</td>
                <td>AL48</td>
                <td>AL49</td>
            </tr>
            <tr>
                <td>AL29</td>
                <td>AL30</td>
                <td>AL31</td>
                <td>AL32</td>
                <td>AL33</td>
                <td>AL34</td>
                <td>AL35</td>
            </tr>
            <tr>
                <td>AL15</td>
                <td>AL16</td>
                <td>AL17</td>
                <td>AL18</td>
                <td>AL19</td>
                <td>AL20</td>
                <td>AL21</td>
            </tr>
            <tr>
                <td>AL01</td>
                <td>AL02</td>
                <td>AL03</td>
                <td>AL04</td>
                <td>AL05</td>
                <td>AL06</td>
                <td>AL07</td>
            </tr>
        </table>
    </div>

    <div>
        <h3>Right Rack</h3>
        <table>
            <tr>
                <td>AR98</td>
                <td>AR97</td>
                <td>AR96</td>
                <td>AR95</td>
                <td>AR94</td>
                <td>AR93</td>
                <td>AR92</td>
            </tr>
            <tr>
                <td>AR84</td>
                <td>AR83</td>
                <td>AR82</td>
                <td>AR81</td>
                <td>AR80</td>
                <td>AR79</td>
                <td>AR78</td>
            </tr>
            <tr>
                <td>AR70</td>
                <td>AR69</td>
                <td>AR68</td>
                <td>AR67</td>
                <td>AR66</td>
                <td>AR65</td>
                <td>AR64</td>
            </tr>
            <tr>
                <td>AR56</td>
                <td>AR55</td>
                <td>AR54</td>
                <td>AR53</td>
                <td>AR52</td>
                <td>AR51</td>
                <td>AR50</td>
            </tr>
            <tr>
                <td>AR42</td>
                <td>AR41</td>
                <td>AR40</td>
                <td>AR39</td>
                <td>AR38</td>
                <td>AR37</td>
                <td>AR36</td>
            </tr>
            <tr>
                <td>AR28</td>
                <td>AR27</td>
                <td>AR26</td>
                <td>AR25</td>
                <td>AR24</td>
                <td>AR23</td>
                <td>AR22</td>
            </tr>
            <tr>
                <td>AR14</td>
                <td>AR13</td>
                <td>AR12</td>
                <td>AR11</td>
                <td>AR10</td>
                <td>AR09</td>
                <td>AR08</td>
            </tr>
        </table>
    </div>
</div>

<div class="table-container">
    <canvas id="barChart"></canvas>
    <canvas id="pieChart"></canvas>
</div>

<script>
    const occupiedSlots = <?php echo $occupiedSlots; ?>;
    const totalSlots = <?php echo $totalSlots; ?>;
    const customerCount = <?php echo $customerCount; ?>;

    const ctxBar = document.getElementById('barChart').getContext('2d');
    const ctxPie = document.getElementById('pieChart').getContext('2d');

    // Biểu đồ cột
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Trạm A'],
            datasets: [{
                label: 'Số khách hàng',
                data: [customerCount],
                backgroundColor: 'rgba(255, 0, 0, 0.7)', // Màu đỏ
                borderColor: 'white', // Viền trắng
                borderWidth: 2
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: 'white' // Màu chữ trục y
                    }
                },
                x: {
                    ticks: {
                        color: 'white' // Màu chữ trục x
                    }
                }
            }
        }
    });

    // Biểu đồ tròn
    const pieChart = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Đã sử dụng', 'Còn lại'],
            datasets: [{
                data: [occupiedSlots, totalSlots - occupiedSlots],
                backgroundColor: ['rgba(0, 0, 255, 0.7)', 'rgba(255, 255, 0, 0.7)'], // Màu xanh và vàng
                borderColor: 'white', // Viền trắng
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // Màu chữ legend
                    }
                }
            }
        }
    });
</script>
</body>
</html>
