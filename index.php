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

// Lấy dữ liệu từ RFID
$sql = "SELECT DISTINCT MAKH FROM dbo.stored_warehouse WHERE RFID LIKE 'A%'";
$stmt = sqlsrv_query($conn, $sql);

// Kiểm tra lỗi khi truy vấn
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Lấy tên khách hàng
$customers = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $customers[] = $row['MAKH'];
}

// Đóng kết nối
sqlsrv_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management - Station A</title>
    <style>
        body {
            background-color: #001F3F; /* Xanh đậm */
            color: white; /* Màu chữ trắng */
            font-size: 8px; /* Kích thước chữ */
        }
        .container {
            display: flex; /* Sử dụng flexbox để bố trí các phần tử */
            justify-content: space-around; /* Căn giữa các bảng */
            margin: 20px; /* Giãn cách giữa các bảng và biểu đồ */
        }
        table {
            width: 30%; /* Mỗi bảng chiếm 1/5 màn hình */
            border-collapse: collapse;
        }
        th, td {
            border: 2px solid white; /* Đường viền trắng */
            padding: 3px; /* Giảm padding để bảng nhỏ hơn */
            text-align: center;
        }
        td.highlight {
            background-color: #32CD32; /* Màu xanh lục cho ô được highlight */
        }
        .chart-container {
            width: 30%; /* Chiếm 2/5 màn hình cho biểu đồ */
            margin: 20px; /* Giãn cách giữa các biểu đồ */
        }
        .charts {
            display: flex; /* Bố trí 2 biểu đồ nằm ngang */
            justify-content: space-around; /* Căn giữa các biểu đồ */
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h2 style="text-align: center;">Warehouse Station A</h2>

<div class="container">
    <!-- Bảng Left Rack -->
    <table>
        <caption style="caption-side: top;">Left Rack</caption>
        <tr>
            <td>AL01</td><td>AL02</td><td>AL03</td><td>AL04</td><td>AL05</td>
            <td>AL06</td><td>AL07</td><td>AL08</td><td>AL09</td><td>AL10</td>
            <td>AL11</td><td>AL12</td><td>AL13</td><td>AL14</td>
        </tr>
        <tr>
            <td>AL15</td><td>AL16</td><td>AL17</td><td>AL18</td><td>AL19</td>
            <td>AL20</td><td>AL21</td><td>AL22</td><td>AL23</td><td>AL24</td>
            <td>AL25</td><td>AL26</td><td>AL27</td><td>AL28</td>
        </tr>
        <tr>
            <td>AL29</td><td>AL30</td><td>AL31</td><td>AL32</td><td>AL33</td>
            <td>AL34</td><td>AL35</td><td>AL36</td><td>AL37</td><td>AL38</td>
            <td>AL39</td><td>AL40</td><td>AL41</td><td>AL42</td>
        </tr>
        <tr>
            <td>AL43</td><td>AL44</td><td>AL45</td><td>AL46</td><td>AL47</td>
            <td>AL48</td><td>AL49</td><td>AL50</td><td>AL51</td><td>AL52</td>
            <td>AL53</td><td>AL54</td><td>AL55</td><td>AL56</td>
        </tr>
        <tr>
            <td>AL57</td><td>AL58</td><td>AL59</td><td>AL60</td><td>AL61</td>
            <td>AL62</td><td>AL63</td><td>AL64</td><td>AL65</td><td>AL66</td>
            <td>AL67</td><td>AL68</td><td>AL69</td><td>AL70</td>
        </tr>
        <tr>
            <td>AL71</td><td>AL72</td><td>AL73</td><td>AL74</td><td>AL75</td>
            <td>AL76</td><td>AL77</td><td>AL78</td><td>AL79</td><td>AL80</td>
            <td>AL81</td><td>AL82</td><td>AL83</td><td>AL84</td>
        </tr>
        <tr>
            <td>AL85</td><td>AL86</td><td>AL87</td><td>AL88</td><td>AL89</td>
            <td>AL90</td><td>AL91</td><td>AL92</td><td>AL93</td><td>AL94</td>
            <td>AL95</td><td>AL96</td><td>AL97</td><td>AL98</td>
        </tr>
    </table>

    <!-- Bảng Right Rack -->
    <table>
        <caption style="caption-side: top;">Right Rack</caption>
        <tr>
            <td>AR01</td><td>AR02</td><td>AR03</td><td>AR04</td><td>AR05</td>
            <td>AR06</td><td>AR07</td><td>AR08</td><td>AR09</td><td>AR10</td>
            <td>AR11</td><td>AR12</td><td>AR13</td><td>AR14</td>
        </tr>
        <tr>
            <td>AR15</td><td>AR16</td><td>AR17</td><td>AR18</td><td>AR19</td>
            <td>AR20</td><td>AR21</td><td>AR22</td><td>AR23</td><td>AR24</td>
            <td>AR25</td><td>AR26</td><td>AR27</td><td>AR28</td>
        </tr>
        <tr>
            <td>AR29</td><td>AR30</td><td>AR31</td><td>AR32</td><td>AR33</td>
            <td>AR34</td><td>AR35</td><td>AR36</td><td>AR37</td><td>AR38</td>
            <td>AR39</td><td>AR40</td><td>AR41</td><td>AR42</td>
        </tr>
        <tr>
            <td>AR43</td><td>AR44</td><td>AR45</td><td>AR46</td><td>AR47</td>
            <td>AR48</td><td>AR49</td><td>AR50</td><td>AR51</td><td>AR52</td>
            <td>AR53</td><td>AR54</td><td>AR55</td><td>AR56</td>
        </tr>
        <tr>
            <td>AR57</td><td>AR58</td><td>AR59</td><td>AR60</td><td>AR61</td>
            <td>AR62</td><td>AR63</td><td>AR64</td><td>AR65</td><td>AR66</td>
            <td>AR67</td><td>AR68</td><td>AR69</td><td>AR70</td>
        </tr>
        <tr>
            <td>AR71</td><td>AR72</td><td>AR73</td><td>AR74</td><td>AR75</td>
            <td>AR76</td><td>AR77</td><td>AR78</td><td>AR79</td><td>AR80</td>
            <td>AR81</td><td>AR82</td><td>AR83</td><td>AR84</td>
        </tr>
        <tr>
            <td>AR85</td><td>AR86</td><td>AR87</td><td>AR88</td><td>AR89</td>
            <td>AR90</td><td>AR91</td><td>AR92</td><td>AR93</td><td>AR94</td>
            <td>AR95</td><td>AR96</td><td>AR97</td><td>AR98</td>
        </tr>
    </table>
</div>

<!-- Biểu đồ -->
<div class="charts">
    <!-- Biểu đồ cột -->
    <div class="chart-container">
        <canvas id="barChart"></canvas>
    </div>

    <!-- Biểu đồ tròn -->
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>
</div>

<script>
    // Dữ liệu biểu đồ
    const customerCount = <?= count($customers) ?>; // Số khách hàng
    const totalSlots = 196; // Tổng số ô (98x2)
    const filledSlots = <?= count(array_filter($customers, fn($c) => str_starts_with($c, 'A'))) ?>; // Số ô đã sử dụng

    // Biểu đồ cột
    const ctxBar = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: ['Customers'],
            datasets: [{
                label: 'Number of Customers',
                data: [customerCount],
                backgroundColor: 'rgba(54, 162, 235, 1)', // Màu lam tươi
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // Màu chữ trắng cho legend
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'white' // Màu đường lưới trắng
                    }
                },
                x: {
                    grid: {
                        color: 'white' // Màu đường lưới trắng
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
            labels: ['Filled', 'Available'],
            datasets: [{
                data: [filledSlots, totalSlots - filledSlots],
                backgroundColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'], // Màu đỏ và xanh
                borderColor: 'white', // Đường viền trắng
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    labels: {
                        color: 'white' // Màu chữ trắng cho legend
                    }
                }
            }
        }
    });
</script>

</body>
</html>
