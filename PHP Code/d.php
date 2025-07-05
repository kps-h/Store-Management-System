<?php
// Include database connection
include('db.php');

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

// Fetch latest 10 stock-in entries for the dashboard
$dashboardLimit = 5; // Display latest 10 records
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

// Fetch latest 10 stock-out entries for the dashboard
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

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Store Management System</title>

    <!-- Link to external stylesheet for dashboard design -->
    <link rel="stylesheet" href="dashboard.css">

    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Chart.js for graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Include jQuery (for modal functionality) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        /* CSS for Modal Popup */
        .modal {
            display: none;
            position: absolute;
            background-color: #fff;
            width: 300px;
            max-width: 90%;
            padding: 15px;
            border-radius: 8px;
            color: black;
            z-index: 1000;
            font-size: 14px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease-in-out;
        }

        .modal-content {
            background-color: #E5E4E2;
            padding: 20px;
            border-radius: 8px;
        }

        .close-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 18px;
            cursor: pointer;
            color: black;
        }

        .close-btn:hover {
            color: #ff6347;
        }

        .modal h3 {
            margin-top: 0;
        }

        .modal p {
            margin: 5px 0;
        }

        /* Cards Section */
        .cards-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .card {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            width: 30%;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .card h4 {
            margin: 0;
            font-size: 18px;
        }

        .card p {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }

        .card i {
            font-size: 40px;
            color: #007bff;
        }

        /* Other styling for page layout */
        .dashboard-container {
            padding: 20px;
        }

        .dashboard-header h1 {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .chart-report-container,
        .reports-container {
            margin-bottom: 20px;
        }

        .report-container h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f1f1f1;
        }

        .stock-in-link,
        .stock-out-link {
            text-decoration: none;
            color: #007bff;
            cursor: pointer;
        }

        .stock-in-link:hover,
        .stock-out-link:hover {
            text-decoration: underline;
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
                <i class="fas fa-arrow-up"></i>
                <h3>New Items</h3>
                <p>120</p>
            </div>

            <div class="card">
                <i class="fas fa-arrow-down"></i>
                <h3>Low Items</h3>
                <p>50</p>
            </div>

            <div class="card">
                <i class="fas fa-ban"></i>
                <h3>Dead Items</h3>
                <p>30</p>
            </div>

            <div class="card">
                <i class="fas fa-cogs"></i>
                <h3>More Consumed Items</h3>
                <p>200</p>
            </div>

            <div class="card">
                <i class="fas fa-boxes"></i>
                <h3>Total Items</h3>
                <p>2,350</p>
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

    <!-- Modal for displaying Stock In / Stock Out details -->
    <div id="stock-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3>Stock Details</h3>
            <p><strong>Stock In ID:</strong> <span id="stock-in-id"></span></p>
            <p><strong>Item ID:</strong> <span id="item-id"></span></p>
            <p><strong>Item Name:</strong> <span id="item-name"></span></p>
            <p><strong>Quantity:</strong> <span id="quantity"></span></p>
        </div>
    </div>

    <!-- JavaScript to render charts and handle modal -->
    <script>
        const ctxSales = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June'],
                datasets: [{
                        label: 'Stock In (Units)',
                        data: [3000, 5000, 8000, 11000, 13000, 15000],
                        fill: false,
                        borderColor: '#28a745',
                        tension: 0.1
                    },
                    {
                        label: 'Stock Out (Units)',
                        data: [2500, 4500, 7000, 9000, 11000, 13000],
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
                    y: {
                        beginAtZero: true
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
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Handle modal display
        $('.stock-in-link, .stock-out-link').on('click', function (e) {
            const stockId = $(this).data('stock-in-id') || $(this).data('stock-out-id');
            const itemId = $(this).data('item-id');
            const itemName = $(this).data('item-name');
            const quantity = $(this).data('quantity');

            // Fill modal content
            $('#stock-in-id').text(stockId);
            $('#item-id').text(itemId);
            $('#item-name').text(itemName);
            $('#quantity').text(quantity);

            // Position the modal near the clicked link
            const offset = $(this).offset();
            const modal = $('#stock-details-modal');
            modal.css({
                top: offset.top + 20, // 20px below the clicked item
                left: offset.left - (modal.width() / 2) + ($(this).width() / 2) // Centered over the link
            });

            // Show the modal
            modal.show();
        });

        // Close the modal
        $('.close-btn').on('click', function () {
            $('#stock-details-modal').hide();
        });
    </script>
</body>

</html>
