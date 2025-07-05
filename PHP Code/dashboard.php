<?php
// Include database connection
include('db.php');

// Fetch dynamic weekly stock-in and stock-out data for the sales chart
$salesChartData = [
    'stock_in' => [],
    'stock_out' => [],
    'week_dates' => []  // Store the start date of each week
];

// SQL query to get weekly stock-in totals with the start date of each week
$stockInQuery = "
    SELECT 
        DATE_FORMAT(DATE_SUB(Date_In, INTERVAL WEEKDAY(Date_In) DAY), '%Y-%m-%d') AS week_start_date, 
        SUM(Qty) AS total_qty 
    FROM stock_in_items 
    JOIN stock_in ON stock_in_items.Stock_In_Id = stock_in.Stock_In_Id 
    GROUP BY WEEK(Date_In, 1)
    ORDER BY week_start_date";
$stockInResult = $conn->query($stockInQuery);
while ($row = $stockInResult->fetch_assoc()) {
    $salesChartData['stock_in'][] = $row['total_qty'];
    $salesChartData['week_dates'][] = $row['week_start_date'];  // Store start date of each week
}

// SQL query to get weekly stock-out totals with the start date of each week
$stockOutQuery = "
    SELECT 
        DATE_FORMAT(DATE_SUB(Date_Out, INTERVAL WEEKDAY(Date_Out) DAY), '%Y-%m-%d') AS week_start_date, 
        SUM(Qty) AS total_qty 
    FROM stock_out_items 
    JOIN stock_out ON stock_out_items.Stock_Out_Id = stock_out.Stock_Out_Id 
    GROUP BY WEEK(Date_Out, 1)
    ORDER BY week_start_date";
$stockOutResult = $conn->query($stockOutQuery);
while ($row = $stockOutResult->fetch_assoc()) {
    $salesChartData['stock_out'][] = $row['total_qty'];
}


// Fetch stock-in items for the pie chart
$pieChartDataStockIn = [];
$pieChartQueryStockIn = "SELECT Item_Name, SUM(Qty) AS TotalQty FROM stock_in_items GROUP BY Item_Name ORDER BY TotalQty DESC LIMIT 10";
$pieChartResultStockIn = $conn->query($pieChartQueryStockIn);

while ($row = $pieChartResultStockIn->fetch_assoc()) {
    $pieChartDataStockIn[] = $row;
}

// Fetch stock-out items for the pie chart
$pieChartDataStockOut = [];
$pieChartQueryStockOut = "SELECT Item_Name, SUM(Qty) AS TotalQty FROM stock_out_items GROUP BY Item_Name ORDER BY TotalQty DESC LIMIT 10";
$pieChartResultStockOut = $conn->query($pieChartQueryStockOut);

while ($row = $pieChartResultStockOut->fetch_assoc()) {
    $pieChartDataStockOut[] = $row;
}

// Fetch latest 5 stock-in entries for the dashboard
$dashboardLimit = 5;
$dashboardQuery = "SELECT * FROM stock_in ORDER BY Stock_In_Id DESC LIMIT $dashboardLimit";
$dashboardResult = $conn->query($dashboardQuery);
$latestStockInRecords = [];
while ($row = $dashboardResult->fetch_assoc()) {
    $latestStockInRecords[] = $row;
}

// Fetch stock-in items for each stock-in record
$stockInItems = [];
foreach ($latestStockInRecords as $record) {
    $stockInId = $record['Stock_In_Id'];
    $itemResult = $conn->query("SELECT * FROM stock_in_items WHERE Stock_In_Id = '$stockInId'");
    while ($itemRow = $itemResult->fetch_assoc()) {
        $stockInItems[$stockInId][] = $itemRow;
    }
}

// Fetch latest 5 stock-out entries for the dashboard
$dashboardQueryStockOut = "SELECT * FROM stock_out ORDER BY Stock_Out_Id DESC LIMIT $dashboardLimit";
$dashboardResultStockOut = $conn->query($dashboardQueryStockOut);
$latestStockOutRecords = [];
while ($row = $dashboardResultStockOut->fetch_assoc()) {
    $latestStockOutRecords[] = $row;
}

// Fetch stock-out items for each stock-out record
$stockOutItems = [];
foreach ($latestStockOutRecords as $record) {
    $stockOutId = $record['Stock_Out_Id'];
    $itemResult = $conn->query("SELECT * FROM stock_out_items WHERE Stock_Out_Id = '$stockOutId'");
    while ($itemRow = $itemResult->fetch_assoc()) {
        $stockOutItems[$stockOutId][] = $itemRow;
    }
}

// Fetch items inserted in the last 7 days
$newItemsQuery = "SELECT COUNT(*) AS new_items_count FROM item WHERE Date >= CURDATE() - INTERVAL 7 DAY";
$newItemsResult = $conn->query($newItemsQuery);
$newItemsCount = 0;

if ($newItemsResult) {
    $row = $newItemsResult->fetch_assoc();
    $newItemsCount = $row['new_items_count'];
}

// Fetch items with low stock (less than a threshold, e.g., 10)
$lowStockThreshold = 40;  // You can change this threshold as needed
$lowStockQuery = "SELECT Item_Name, SUM(Qty) AS TotalQty FROM stock_in_items GROUP BY Item_Name HAVING SUM(Qty) < $lowStockThreshold ORDER BY TotalQty ASC LIMIT 1"; // Fetch lowest stock item
$lowStockResult = $conn->query($lowStockQuery);
$lowStockItem = $lowStockResult->fetch_assoc();
$lowStockItemName = $lowStockItem['Item_Name'] ?? 'N/A';  // Handle case where no low stock items are found
$lowStockQty = $lowStockItem['TotalQty'] ?? 0;

// Query to find items that have not been used in stock-out transactions for the last 6 months
$deadItemsQuery = "
    SELECT COUNT(DISTINCT soi.Item_Id) AS dead_items_count
    FROM stock_out_items soi
    LEFT JOIN stock_out so ON soi.Stock_Out_Id = so.Stock_Out_Id
    WHERE so.Date_Out < CURDATE() - INTERVAL 6 MONTH
";

// Execute the query
$deadItemsResult = $conn->query($deadItemsQuery);
$deadItemsCount = 0;  // Default to 0 if no result is found

if ($deadItemsResult) {
    $row = $deadItemsResult->fetch_assoc();
    $deadItemsCount = $row['dead_items_count'];
}

$mostConsumedQuery = "
    SELECT soi.Item_Id, soi.Item_Name, SUM(soi.Qty) AS total_qty
    FROM stock_out_items soi
    JOIN stock_out so ON soi.Stock_Out_Id = so.Stock_Out_Id
    WHERE so.Date_Out >= CURDATE() - INTERVAL 1 MONTH
    GROUP BY soi.Item_Id
    ORDER BY total_qty DESC
    LIMIT 5;  -- Limit to top 5 most consumed items
";

// Query to count the number of distinct items Most consumed in the last 1 month
$mostConsumedCountQuery = "
    SELECT COUNT(DISTINCT soi.Item_Id) AS num_items
    FROM stock_out_items soi
    JOIN stock_out so ON soi.Stock_Out_Id = so.Stock_Out_Id
    WHERE so.Date_Out >= CURDATE() - INTERVAL 1 MONTH;
";

// Execute the query
$mostConsumedCountResult = $conn->query($mostConsumedCountQuery);
$mostConsumedCount = 0;  // Default to 0 if no results

if ($mostConsumedCountResult) {
    $row = $mostConsumedCountResult->fetch_assoc();
    $mostConsumedCount = $row['num_items'];  // Get the count of distinct items
}

// Query to count the total number of distinct items in the item table
$totalItemsQuery = "SELECT COUNT(DISTINCT Item_Id) AS total_items FROM item";

// Execute the query
$totalItemsResult = $conn->query($totalItemsQuery);
$totalItems = 0;  // Default to 0 if no results

if ($totalItemsResult) {
    $row = $totalItemsResult->fetch_assoc();
    $totalItems = $row['total_items'];  // Get the total count of items
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Store Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    /* Basic Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Body and Container Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #ffffff;
    padding: 20px;
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    max-width: 1500px;
    margin-left: auto;
    margin-right: auto;
    gap: 20px;
}

/* Header */
.dashboard-header {
    text-align: center;
    padding: 20px;
    background-color: #4CAF50;
    color: white;
    border-radius: 8px;
}

/* Card Section */
.cards-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.card i {
    font-size: 40px;
    margin-bottom: 15px;
    color: #333;
}

.card h3 {
    font-size: 1.2rem;
    margin-bottom: 10px;
}

.card p {
    font-size: 1.5rem;
    color: #4CAF50;
}

/* Chart Section (Side by Side on Desktop) */
.chart-report-container {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;  /* Three equal columns */
    gap: 30px;
    margin-top: 30px;
}

.chart-container, .report-container {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.chart-container h3, .report-container h3 {
    margin-bottom: 15px;
    color: #333;
}

/* Responsive Design for Chart Container */
@media (max-width: 768px) {
    .chart-report-container {
        grid-template-columns: 1fr;  /* Stacked vertically on smaller screens */
    }
}

/* Stock Reports Section - Side by Side */
.reports-container {
    display: grid;
    grid-template-columns: 1fr 1fr;  /* Two equal columns for Stock In and Stock Out Reports */
    gap: 30px;
    margin-top: 30px;
}

.reports-container .report {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Responsive Design for Stock Reports Section */
@media (max-width: 768px) {
    .reports-container {
        grid-template-columns: 1fr;  /* Stacked vertically on smaller screens */
    }
}

/* Tables for Reports */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background-color: #f7f7f7;
}

tr:nth-child(even) {
    background-color: #f9f9f9;
}

tr:hover {
    background-color: #e7f7e7;
}

/* Modal Styles */
#stock-details-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
    width: 300px;
}

.modal-content {
    font-size: 1rem;
    margin-bottom: 20px;
}

.modal-content span {
    font-weight: bold;
}

.close-btn {
    font-size: 20px;
    cursor: pointer;
    color: #333;
    float: right;
}

    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1>Store Management System</h1>
        </div>

        <!-- Statistical Cards Section -->
        <div class="cards-container">
            <div class="card">
                <i class="fa fa-arrow-up"></i>
                <h3>New Items</h3>
                <p><?php echo htmlspecialchars($newItemsCount); ?></p> <!-- Display the count of new items -->
            </div>

            <div class="card">
                <i class="fa fa-arrow-down"></i>
                <h3>Low Items</h3>
                <p><?php echo htmlspecialchars($lowStockQty); ?> - <?php echo htmlspecialchars($lowStockItemName); ?></p> <!-- Display low item and quantity -->
            </div>

            <div class="card">
                <i class="fa fa-ban"></i>
                <h3>Dead Items</h3>
                <p><?php echo htmlspecialchars($deadItemsCount); ?> </p> <!-- Display dead item count -->
            </div>

            <div class="card">
                <i class="fa fa-cogs"></i>
                <h3>Most Consumed Items</h3>
                <p><?php echo htmlspecialchars($mostConsumedCount); ?></p> <!-- Display the count of most consumed items -->
            </div>

            <div class="card">
                <i class="fa fa-boxes"></i>
                <h3>Total Items</h3>
                <p><?php echo htmlspecialchars($totalItems); ?></p> <!-- Display the total number of items -->
            </div>
        </div>

        <!-- Line Chart Section & Pie Charts -->
        <div class="chart-report-container">
            <div class="chart-container">
                <h3>Stock In vs Stock Out</h3>
                <canvas id="salesChart"></canvas>
            </div>

            <div class="report-container">
                <h3>Item Distribution (Stock In)</h3>
                <canvas id="pieChartStockIn"></canvas>
            </div>

            <div class="report-container">
                <h3>Item Distribution (Stock Out)</h3>
                <canvas id="pieChartStockOut"></canvas>
            </div>
        </div>

        <!-- Stock In / Stock Out Reports -->
        <div class="reports-container">
            <!-- Stock In Report -->
            <div class="report">
                <h3>Stock In Report</h3>
                <table>
                    <tr>
                        <th>Stock In ID</th>
                        <th>Date</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                    </tr>
                    <?php foreach ($latestStockInRecords as $record): ?>
                        <?php
                            $items = $stockInItems[$record['Stock_In_Id']] ?? [];
                            foreach ($items as $item):
                        ?>
                            <tr>
                                <td><a href="javascript:void(0)" class="stock-in-link" data-stock-in-id="<?php echo htmlspecialchars($record['Stock_In_Id']); ?>" data-item-name="<?php echo htmlspecialchars($item['Item_Name']); ?>" data-quantity="<?php echo htmlspecialchars($item['Qty']); ?>" data-item-id="<?php echo htmlspecialchars($item['Item_Id']); ?>" data-date-in="<?php echo htmlspecialchars($record['Date_In']); ?>"><?php echo htmlspecialchars($record['Stock_In_Id']); ?></a></td>
                                <td><?php echo htmlspecialchars($record['Date_In']); ?></td>
                                <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Qty']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Stock Out Report -->
            <div class="report">
                <h3>Stock Out Report</h3>
                <table>
                    <tr>
                        <th>Stock Out ID</th>
                        <th>Date</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                    </tr>
                    <?php foreach ($latestStockOutRecords as $record): ?>
                        <?php
                            $items = $stockOutItems[$record['Stock_Out_Id']] ?? [];
                            foreach ($items as $item):
                        ?>
                            <tr>
                                <td><a href="javascript:void(0)" class="stock-out-link" data-stock-out-id="<?php echo htmlspecialchars($record['Stock_Out_Id']); ?>" data-item-name="<?php echo htmlspecialchars($item['Item_Name']); ?>" data-quantity="<?php echo htmlspecialchars($item['Qty']); ?>" data-item-id="<?php echo htmlspecialchars($item['Item_Id']); ?>" data-date-out="<?php echo htmlspecialchars($record['Date_Out']); ?>"><?php echo htmlspecialchars($record['Stock_Out_Id']); ?></a></td>
                                <td><?php echo htmlspecialchars($record['Date_Out']); ?></td>
                                <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['Qty']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for displaying stock details -->
    <div id="stock-details-modal">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <p><span>Stock In ID:</span> <span id="stock-in-id"></span></p>
            <p><span>Item Name:</span> <span id="item-name"></span></p>
            <p><span>Quantity:</span> <span id="quantity"></span></p>
            <p><span>Date In:</span> <span id="date-in"></span></p>
        </div>
    </div>


    <!-- Modal for displaying stock details -->
    <div id="stock-details-modal">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <div class="modal-content">
            <p><span>Stock In ID:</span> <span id="stock-in-id"></span></p>
            <p><span>Item Name:</span> <span id="item-name"></span></p>
            <p><span>Quantity:</span> <span id="quantity"></span></p>
            <p><span>Date In:</span> <span id="date-in"></span></p>
        </div>
    </div>

    <script>
        // Modal show and hide functionality
        $(document).ready(function() {
            $(".stock-in-link").click(function() {
                var stockInId = $(this).data("stock-in-id");
                var itemName = $(this).data("item-name");
                var quantity = $(this).data("quantity");
                var dateIn = $(this).data("date-in");

                $("#stock-in-id").text(stockInId);
                $("#item-name").text(itemName);
                $("#quantity").text(quantity);
                $("#date-in").text(dateIn);
                $("#stock-details-modal").fadeIn();
            });

            $(".stock-out-link").click(function() {
                var stockOutId = $(this).data("stock-out-id");
                var itemName = $(this).data("item-name");
                var quantity = $(this).data("quantity");
                var dateOut = $(this).data("date-out");

                $("#stock-in-id").text(stockOutId);
                $("#item-name").text(itemName);
                $("#quantity").text(quantity);
                $("#date-in").text(dateOut);
                $("#stock-details-modal").fadeIn();
            });

            // Close modal
            window.closeModal = function() {
                $("#stock-details-modal").fadeOut();
            };
        });
		const ctxSales = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($salesChartData['week_dates']); ?>,  // Week start dates as X-axis labels
                datasets: [
                    {
                        label: 'Stock In (Units)',
                        data: <?php echo json_encode($salesChartData['stock_in']); ?>,  // Dynamic Stock In data
                        fill: false,
                        borderColor: '#28a745',
                        tension: 0.1
                    },
                    {
                        label: 'Stock Out (Units)',
                        data: <?php echo json_encode($salesChartData['stock_out']); ?>,  // Dynamic Stock Out data
                        fill: false,
                        borderColor: '#ff6347',
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Week Start Date'
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10  // Control the number of ticks shown
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity'
                        }
                    }
                }
            }
        });
        // Pie Chart for Item Distribution (Stock In)
        const ctxPieStockIn = document.getElementById('pieChartStockIn').getContext('2d');
        const pieDataStockIn = <?php echo json_encode($pieChartDataStockIn); ?>;
        
        const labelsStockIn = pieDataStockIn.map(item => item.Item_Name);
        const quantitiesStockIn = pieDataStockIn.map(item => item.TotalQty);

        const pieChartStockIn = new Chart(ctxPieStockIn, {
            type: 'pie',
            data: {
                labels: labelsStockIn,
                datasets: [{
                    label: 'Item Distribution (Stock In)',
                    data: quantitiesStockIn,
                    backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#ff9f40', '#c9cbcf', '#ff6600', '#ff3399', '#33ccff', '#6600cc'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Pie Chart for Item Distribution (Stock Out)
        const ctxPieStockOut = document.getElementById('pieChartStockOut').getContext('2d');
        const pieDataStockOut = <?php echo json_encode($pieChartDataStockOut); ?>;
        
        const labelsStockOut = pieDataStockOut.map(item => item.Item_Name);
        const quantitiesStockOut = pieDataStockOut.map(item => item.TotalQty);

        const pieChartStockOut = new Chart(ctxPieStockOut, {
            type: 'pie',
            data: {
                labels: labelsStockOut,
                datasets: [{
                    label: 'Item Distribution (Stock Out)',
                    data: quantitiesStockOut,
                    backgroundColor: ['#ff6347', '#36a2eb', '#ffcd56', '#4bc0c0', '#ff9f40', '#c9cbcf', '#ff6600', '#ff3399', '#33ccff', '#6600cc'],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
				maintainAspectRatio: true, // Allow the chart to stretch vertically
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>
