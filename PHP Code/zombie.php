<?php
include('db.php');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch dynamic data for Inventory Chart (Aggregating Quantities)
$inventory_query = "SELECT sii.Item_Name, SUM(sii.Qty) AS Total_Qty
                    FROM stock_in si
                    JOIN stock_in_items sii ON si.Stock_In_Id = sii.Stock_In_Id
                    GROUP BY sii.Item_Name
                    ORDER BY Total_Qty DESC";
$inventory_result = $conn->query($inventory_query);

$item_names = [];
$quantities = [];

if ($inventory_result->num_rows > 0) {
    while($row = $inventory_result->fetch_assoc()) {
        $item_names[] = $row['Item_Name'];
        $quantities[] = $row['Total_Qty'];
    }
}
// Fetch dynamic data for Sales Over Time
$sales_query = "SELECT 
                    si.Date_In AS Date, 
                    SUM(si.Total_In) AS Total_In, 
                    SUM(so.Total_Out) AS Total_Out
                FROM stock_in si
                LEFT JOIN stock_out so ON DATE(si.Date_In) = DATE(so.Date_Out)
                GROUP BY si.Date_In
                ORDER BY si.Date_In ASC";
                
$sales_result = $conn->query($sales_query);

$dates = [];
$total_in = [];
$total_out = [];

if ($sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $dates[] = $row['Date'];
        $total_in[] = $row['Total_In'] ? $row['Total_In'] : 0;
        $total_out[] = $row['Total_Out'] ? $row['Total_Out'] : 0;
    }
} else {
    echo "No sales data found.";
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
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management Dashboard</title>
	<link rel="stylesheet" href="zombie.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .content {
            padding: 30px;
        }

        .card {
            border-radius: 10px;
        }

        .chart-container {
            position: relative;
            height: 350px;
        }

        .navbar {
            background-color: #007bff;
            color: white;
        }

        .navbar-nav .nav-link {
            color: white;
        }

        .navbar-nav .nav-link:hover {
            background-color: #0056b3;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .dashboard-card {
            border-radius: 15px;
        }

        .footer {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 767px) {
            .content {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<!-- Main Content Area -->
<div class="content">
    <!-- Dashboard Stats (All Cards in Single Row) -->
    <div class="row g-3 mb-4">
    <!-- New Items Card -->
    <div class="col">
        <div class="card dashboard-card shadow-sm bg-blue">
            <div class="card-body">
                <i class="fas fa-box-open icon"></i>
                <h5 class="card-title">New Items</h5>
                <p><?php echo htmlspecialchars($newItemsCount); ?></p> <!-- Display the count of new items -->
            </div>
        </div>
    </div>

    <!-- Low Items Card -->
    <div class="col">
        <div class="card dashboard-card shadow-sm bg-orange">
            <div class="card-body">
                <i class="fas fa-exclamation-triangle icon"></i>
                <h5 class="card-title">Low Items</h5>
                <p><?php echo htmlspecialchars($lowStockQty); ?> - <?php echo htmlspecialchars($lowStockItemName); ?></p> <!-- Display low item and quantity -->
            </div>
        </div>
    </div>

    <!-- Dead Items Card -->
    <div class="col">
        <div class="card dashboard-card shadow-sm bg-red">
            <div class="card-body">
                <i class="fas fa-times-circle icon"></i>
                <h5 class="card-title">Dead Items</h5>
                <p><?php echo htmlspecialchars($deadItemsCount); ?> </p> <!-- Display dead item count -->
            </div>
        </div>
    </div>

    <!-- Most Consumed Items Card -->
    <div class="col">
        <div class="card dashboard-card shadow-sm bg-green">
            <div class="card-body">
                <i class="fas fa-chart-bar icon"></i>
                <h5 class="card-title">Most Consumed Items</h5>
                <p><?php echo htmlspecialchars($mostConsumedCount); ?></p> <!-- Display the count of most consumed items -->
            </div>
        </div>
    </div>

    <!-- Total Items Card -->
    <div class="col">
        <div class="card dashboard-card shadow-sm bg-purple">
            <div class="card-body">
                <i class="fas fa-cogs icon"></i>
                <h5 class="card-title">Total Items</h5>
                <p><?php echo htmlspecialchars($totalItems); ?></p> <!-- Display the total number of items -->
            </div>
        </div>
    </div>
</div>

    <!-- Sales and Inventory Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Stock Over Time</h5>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Inventory Levels</h5>
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock In and Stock Out Reports -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Stock In Report</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Stock In ID</th>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                                <th>Bill No</th>
                                <th>Vendor Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include ('db.php');
                            // Stock In Report Query (Limit to 10 latest entries)
                            $stock_in_query = "SELECT si.Stock_In_Id, si.Date_In AS Date, sii.Item_Name, sii.Qty, si.Bill_No, si.Vendor_Name
                                               FROM stock_in si
                                               JOIN stock_in_items sii ON si.Stock_In_Id = sii.Stock_In_Id
                                               ORDER BY si.Date_In DESC
                                               LIMIT 10";  
                            $stock_in_result = $conn->query($stock_in_query);

                            // Display Stock In Report
                            if ($stock_in_result->num_rows > 0) {
                                while($row = $stock_in_result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . $row['Stock_In_Id'] . "</td>
                                            <td>" . $row['Date'] . "</td>
                                            <td>" . $row['Item_Name'] . "</td>
                                            <td>" . $row['Qty'] . "</td>
                                            <td>" . $row['Bill_No'] . "</td>
                                            <td>" . $row['Vendor_Name'] . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No records found</td></tr>";
                            }

                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Stock Out Report</h5>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Stock Out ID</th>
                                <th>Date</th>
                                <th>Item Name</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            include ('db.php');
                            // Stock Out Report Query (Limit to 10 latest entries)
                            $stock_out_query = "SELECT so.Stock_Out_Id, so.Date_Out AS Date, soi.Item_Name, soi.Qty
                                                FROM stock_out so
                                                JOIN stock_out_items soi ON so.Stock_Out_Id = soi.Stock_Out_Id
                                                ORDER BY so.Date_Out DESC
                                                LIMIT 10";  
                            $stock_out_result = $conn->query($stock_out_query);

                            // Display Stock Out Report
                            if ($stock_out_result->num_rows > 0) {
                                while($row = $stock_out_result->fetch_assoc()) {
                                    echo "<tr>
                                            <td>" . $row['Stock_Out_Id'] . "</td>
                                            <td>" . $row['Date'] . "</td>
                                            <td>" . $row['Item_Name'] . "</td>
                                            <td>" . $row['Qty'] . "</td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No records found</td></tr>";
                            }

                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
		
		
    </div>
</div>

<!-- JavaScript for Charts -->
<script>
    // Sales Over Time Chart Data (Dynamic)
    const salesData = {
        labels: <?php echo json_encode($dates); ?>,  // Dynamic Dates (X-axis)
        datasets: [
            {
                label: 'Total In',
                data: <?php echo json_encode($total_in); ?>,  // Dynamic Total In Quantities (Y-axis)
                borderColor: 'rgba(75, 192, 192, 1)',  // Line color for Total In
                fill: false,
                tension: 0.1
            },
            {
                label: 'Total Out',
                data: <?php echo json_encode($total_out); ?>,  // Dynamic Total Out Quantities (Y-axis)
                borderColor: 'rgba(255, 99, 132, 1)',  // Line color for Total Out
                fill: false,
                tension: 0.1
            }
        ]
    };

    const salesConfig = {
        type: 'line',
        data: salesData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    type: 'category', // X-axis is categorical (dates)
                    title: {
                        display: true,
                        text: 'Date'
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
    };

    // Create Sales Over Time Chart
    window.onload = function() {
        new Chart(document.getElementById('salesChart'), salesConfig);
    };

    // Inventory Chart (Dynamic Data from PHP)
    const inventoryData = {
        labels: <?php echo json_encode($item_names); ?>,  // Dynamic Item Names
        datasets: [{
            label: 'Inventory Levels',
            data: <?php echo json_encode($quantities); ?>,  // Dynamic Quantities
            backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#F3FF33', '#FF33A8'],  // Random Colors
            borderWidth: 1
        }]
    };

    const inventoryConfig = {
        type: 'bar',
        data: inventoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    };

    // Create Charts
    window.onload = function() {
        new Chart(document.getElementById('salesChart'), salesConfig);
        new Chart(document.getElementById('inventoryChart'), inventoryConfig);
    };
	
</script>

</body>
</html>
