<?php
// Include database connection
include('db.php');
session_start();

// Check if user is logged in
/*if (!isset($_SESSION['username'])) {
    header("Location: login.php?redirected=true"); // Redirect to login page
    exit();
}*/

// Handle filtering
$filter = isset($_POST['filter']) ? $_POST['filter'] : '';

// Pagination setup
$limit = 10; // Entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch stock out records with filtering and pagination
$stockOutRecords = [];
$filterSql = $filter ? " WHERE Stock_Out_Id LIKE '%$filter%'" : '';
$result = $conn->query("SELECT * FROM stock_out" . $filterSql . " LIMIT $limit OFFSET $offset");
while ($row = $result->fetch_assoc()) {
    $stockOutRecords[] = $row;
}

// Count total records for pagination
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM stock_out" . $filterSql);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch stock out items
$stockOutItems = [];
foreach ($stockOutRecords as $record) {
    $stockOutId = $record['Stock_Out_Id'];
    $itemResult = $conn->query("SELECT * FROM stock_out_items WHERE Stock_Out_Id = '$stockOutId'");
    while ($itemRow = $itemResult->fetch_assoc()) {
        $stockOutItems[$stockOutId][] = $itemRow;
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="test14.csv"');
    $output = fopen('php://output', 'w');
    
    // Header for main stock out records
    fputcsv($output, ['Stock Out ID', 'Voucher No', 'Date Out', 'Recipient ID', 'Recipient Name', 'Total Out', 'Remarks', 'Item Id', 'Item Name', 'UOM', 'Quantity']); // Include item columns

    foreach ($stockOutRecords as $record) {
        $stockOutId = $record['Stock_Out_Id'];
        // Get item details
        $items = $stockOutItems[$stockOutId] ?? [];
        
        if (count($items) > 0) {
            foreach ($items as $item) {
                // Combine stock out data with item data
                fputcsv($output, array_merge($record, [
                    $item['Item_Id'], 
                    $item['Item_Name'], 
                    $item['UOM'], 
                    $item['Qty']
                ]));
            }
        } else {
            fputcsv($output, array_merge($record, ['', '', '', ''])); // Empty item columns if no items
        }
    }

    fclose($output);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out Report</title>
    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="styleSR.css">
    <script>
        function exportToCSV() {
            const filter = document.getElementById('filter').value;
            const exportUrl = `?export=csv&filter=${encodeURIComponent(filter)}`;
            window.location.href = exportUrl;
        }

        function printReport() {
            window.print();
        }

        function toggleItems(stockOutId) {
            var itemsDiv = document.getElementById('items_' + stockOutId);
            itemsDiv.style.display = itemsDiv.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container report-container">
        <h1><i class="fas fa-box"></i> Stock Out Report</h1>

        <form method="POST" class="filter-form">
            <div class="d-flex align-items-center">
                <input type="text" name="filter" id="filter" class="form-control" placeholder="Filter by Stock Out ID" value="<?php echo htmlspecialchars($filter); ?>">
                <button type="submit" class="btn btn-primary ml-2">Filter</button>
            </div>
            <div class="no-print">
                <button type="button" class="btn btn-secondary" onclick="exportToCSV()">Export to CSV</button>
                <button type="button" class="btn btn-secondary" onclick="printReport()">Print</button>
            </div>
        </form>

        <?php if (count($stockOutRecords) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Stock Out ID</th>
                        <th>Voucher No</th>
                        <th>Date Out</th>
                        <th>Recipient ID</th>
                        <th>Recipient Name</th>
                        <th>Total Out</th>
                        <th>Remarks</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockOutRecords as $index => $record): ?>
                        <tr>
                            <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($record['Stock_Out_Id']); ?></td>
                            <td><?php echo htmlspecialchars($record['Voucher_No']); ?></td>
                            <td><?php echo htmlspecialchars($record['Date_Out']); ?></td>
                            <td><?php echo htmlspecialchars($record['Recipient_Id']); ?></td>
                            <td><?php echo htmlspecialchars($record['Recipient_Name']); ?></td>
                            <td><?php echo htmlspecialchars($record['Total_Out']); ?></td>
                            <td><?php echo htmlspecialchars($record['Remarks']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="toggleItems('<?php echo $record['Stock_Out_Id']; ?>')">View Items</button>
                                <div id="items_<?php echo $record['Stock_Out_Id']; ?>" class="item-table">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Item Id</th>
                                                <th>Item Name</th>
                                                <th>UOM</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stockOutItems[$record['Stock_Out_Id']] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['Item_Id']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['UOM']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['Qty']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No stock out records found.</div>
        <?php endif; ?>

        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
