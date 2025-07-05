<?php
// Include database connection
include('db.php');
session_start();

// Handle filtering
$filter = isset($_POST['filter']) ? $_POST['filter'] : '';

// Pagination setup
$limit = 10; // Entries per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch stock in records with filtering and pagination
$stockInRecords = [];
$filterSql = $filter ? " WHERE Stock_In_Id LIKE '%$filter%'" : '';
$result = $conn->query("SELECT * FROM stock_in" . $filterSql . " LIMIT $limit OFFSET $offset");
while ($row = $result->fetch_assoc()) {
    $stockInRecords[] = $row;
}

// Count total records for pagination
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM stock_in" . $filterSql);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $limit);

// Fetch stock in items
$stockInItems = [];
foreach ($stockInRecords as $record) {
    $stockInId = $record['Stock_In_Id'];
    $itemResult = $conn->query("SELECT * FROM stock_in_items WHERE Stock_In_Id = '$stockInId'");
    while ($itemRow = $itemResult->fetch_assoc()) {
        $stockInItems[$stockInId][] = $itemRow;
    }
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_in_report.csv"');
    $output = fopen('php://output', 'w');
    
    // Header for main stock in records
    fputcsv($output, ['Stock In ID', 'Bill No', 'Date In', 'Vendor ID', 'Vendor Name', 'Total In', 'Remarks', 'Item Id', 'Item Name', 'UOM', 'Quantity']); // Include item columns

    foreach ($stockInRecords as $record) {
        $stockInId = $record['Stock_In_Id'];
        $items = $stockInItems[$stockInId] ?? [];

        if (count($items) > 0) {
            foreach ($items as $item) {
                // Combine stock in data with item data
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
    <title>Stock In Report</title>
    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="styleSR.css">
    <script>
        function exportToCSV() {
            const filter = document.getElementById('filter').value;
            window.location.href = '?export=csv&filter=' + encodeURIComponent(filter);
        }

        function printReport() {
            window.print();
        }

        function toggleItems(stockInId) {
            var itemsDiv = document.getElementById('items_' + stockInId);
            itemsDiv.style.display = itemsDiv.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="container report-container">
        <h1><i class="fas fa-box"></i> Stock In Report</h1>

        <form method="POST" class="filter-form">
            <div class="d-flex align-items-center">
                <input type="text" name="filter" id="filter" class="form-control" placeholder="Filter by Stock In ID" value="<?php echo htmlspecialchars($filter); ?>">
                <button type="submit" class="btn btn-primary ml-2">Filter</button>
            </div>
            <div class="no-print">
                <button type="button" class="btn btn-secondary" onclick="exportToCSV()">Export to CSV</button>
                <button type="button" class="btn btn-secondary" onclick="printReport()">Print</button>
            </div>
        </form>

        <?php if (count($stockInRecords) > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Stock In ID</th>
                        <th>Bill No</th>
                        <th>Date In</th>
                        <th>Vendor ID</th>
                        <th>Vendor Name</th>
                        <th>Total In</th>
                        <th>Remarks</th>
                        <th>Items</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockInRecords as $index => $record): ?>
                        <tr>
                            <td><?php echo ($page - 1) * $limit + $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($record['Stock_In_Id']); ?></td>
                            <td><?php echo htmlspecialchars($record['Bill_No']); ?></td>
                            <td><?php echo htmlspecialchars($record['Date_In']); ?></td>
                            <td><?php echo htmlspecialchars($record['Vendor_Id']); ?></td>
                            <td><?php echo htmlspecialchars($record['Vendor_Name']); ?></td>
                            <td><?php echo htmlspecialchars($record['Total_In']); ?></td>
                            <td><?php echo htmlspecialchars($record['Remarks']); ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="toggleItems('<?php echo $record['Stock_In_Id']; ?>')">View Items</button>
                                <div id="items_<?php echo $record['Stock_In_Id']; ?>" class="item-table">
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
                                            <?php foreach ($stockInItems[$record['Stock_In_Id']] as $item): ?>
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
            <div class="alert alert-warning">No stock in records found.</div>
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
</body>
</html>
