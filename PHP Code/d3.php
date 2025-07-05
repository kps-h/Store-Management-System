<?php
// Include the external database connection file
include('db.php'); // Your database connection (db.php)

// Initialize variables
$start_date = '';
$end_date = '';
$item_filter = ''; // Will filter by Item Id and Item Name
$search_input = '';
$rows = []; // Initialize rows
$item_names = []; // To hold Item Names for each Item Id
$current_balances = []; // To store balances for both Item Id and Item Name

// Check if filters are set
if (isset($_POST['start_date'])) {
    $start_date = $_POST['start_date'];
}

if (isset($_POST['end_date'])) {
    $end_date = $_POST['end_date'];
}

if (isset($_POST['item_filter'])) {
    $item_filter = $_POST['item_filter'];
}

if (isset($_POST['search_input'])) {
    $search_input = $_POST['search_input'];
}

// Fetching data from ledger1 with optional filters
$query = "
    SELECT 
        Item_Id,
        Item_Group_Id,
        Item_Group_Name,
        Item_Name,
        Stock_In_Id,
        Date_In,
        Bill_No,
        COALESCE(in_qty, 0) AS total_in_qty,
        Stock_Out_Id,
        Date_Out,
        Voucher_No,
        COALESCE(out_qty, 0) AS total_out_qty,
        DATE(COALESCE(Date_In, Date_Out)) AS entry_date
    FROM ledger1
";

$conditions = [];

// Date range filter
if ($start_date && $end_date) {
    $conditions[] = "(Date_In BETWEEN '$start_date' AND '$end_date' OR Date_Out BETWEEN '$start_date' AND '$end_date')";
}

// Item filter for both Item Id and Item Name
if ($item_filter) {
    $conditions[] = "(Item_Id = '$item_filter' OR Item_Name LIKE '%$item_filter%')";
}

// Search filter for multiple fields
if ($search_input) {
    $conditions[] = "(Item_Name LIKE '%$search_input%' OR Item_Group_Name LIKE '%$search_input%' OR Bill_No LIKE '%$search_input%' OR Voucher_No LIKE '%$search_input%')";
}

if ($conditions) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

$query .= " ORDER BY Item_Id, entry_date";

$result = $conn->query($query);

// Populate $rows if results are found
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

// Grouping transactions by Item_Id and Item_Name and calculating balances
$grouped_rows = [];

// Initialize balances for each Item and Item Name
foreach ($rows as $row) {
    $itemId = $row['Item_Id'];
    $itemName = $row['Item_Name']; // Capture Item Name
    if (!isset($current_balances[$itemId])) {
        $current_balances[$itemId] = 0; // Initialize balance for Item_Id
    }
    if (!isset($current_balances[$itemName])) {
        $current_balances[$itemName] = 0; // Initialize balance for Item_Name
    }
    if (!isset($item_names[$itemId])) {
        $item_names[$itemId] = $itemName; // Initialize Item Name for each Item_Id
    }

    // Update balance for Item_Id and Item_Name
    $current_balances[$itemId] += $row['total_in_qty'];
    $current_balances[$itemId] -= $row['total_out_qty'];
    
    $current_balances[$itemName] += $row['total_in_qty'];
    $current_balances[$itemName] -= $row['total_out_qty'];

    // Store the row with the current balance
    $row['current_balance'] = $current_balances[$itemId];
    $grouped_rows[$itemId][] = $row; // Group by Item_Id
}

// Export to CSV Functionality
if (isset($_POST['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="ledger_export.xls"');

    // Re-run the same query for CSV export, applying balance calculation
    $result = $conn->query($query);
    
    // Print the headers for CSV
    echo "Date\tItem Group Id\tItem Group Name\tItem Name\tStock In Id\tDate In\tBill No\tIn Qty\tStock Out Id\tDate Out\tVoucher No\tOut Qty\tBalance\n";

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate balance before printing
            $itemId = $row['Item_Id'];
            $row['current_balance'] = $current_balances[$itemId]; // Get the balance for Item_Id
            echo implode("\t", array_map('htmlspecialchars', $row)) . "\n";
        }
    }
    exit;
}

// Generate PDF Functionality
if (isset($_POST['pdf'])) {
    require('fpdf.php'); // Ensure you have this library

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);

    // Add table headers
    $pdf->Cell(40, 10, 'Date', 1);
    $pdf->Cell(30, 10, 'Item Group Id', 1);
    $pdf->Cell(50, 10, 'Item Group Name', 1);
    $pdf->Cell(40, 10, 'Item Name', 1);
    $pdf->Cell(30, 10, 'In Qty', 1);
    $pdf->Cell(30, 10, 'Out Qty', 1);
    $pdf->Cell(30, 10, 'Balance', 1);
    $pdf->Ln();

    // Re-run the same query for PDF generation, applying balance calculation
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $itemId = $row['Item_Id'];
        $row['current_balance'] = $current_balances[$itemId]; // Get the balance for Item_Id
        $pdf->Cell(40, 10, $row['entry_date'], 1);
        $pdf->Cell(30, 10, $row['Item_Group_Id'], 1);
        $pdf->Cell(50, 10, $row['Item_Group_Name'], 1);
        $pdf->Cell(40, 10, $row['Item_Name'], 1);
        $pdf->Cell(30, 10, $row['total_in_qty'], 1);
        $pdf->Cell(30, 10, $row['total_out_qty'], 1);
        $pdf->Cell(30, 10, $row['current_balance'], 1);
        $pdf->Ln();
    }

    $pdf->Output();
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Ledger</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            margin: 0;
        }
        .form-container {
            padding: 40px;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 10px; 
            max-width: 1500px;
            margin-left: auto;
            margin-right: auto;
        }
        h1 {
            text-align: center;
            color: #fff;
            padding: 15px;
            border-radius: 10px;
            background-color: #488AC7; 
        }
        .alert {
            margin: 20px 0;
            text-align: center;
            font-size: 1.2rem; /* Adjust font size */
        }
        .table-responsive {
            margin-top: 30px;
            overflow-x: hidden;
            margin-bottom: 30px;
        }
        .btn-small {
            padding: 15px 15px;
            font-size: 0.9rem;
            background-color: #7b7d7d;
            border: none;
            color: white;
        }
        .button-group {
            margin-bottom: 20px;
            text-align: left;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #488AC7;
            color: white;
        }
        .in-qty {
            color: green;
			font-weight: bold;

        }
        .out-qty {
            color: red;
			font-weight: bold;

        }
        .balance {
            color: black;
			font-weight: bold;

        }
		@media print {
    .button-group,
    .btn-small {
        display: none; /* Hide buttons when printing */
    }

    /* Optionally, adjust other elements for printing */
    body {
        font-size: 12px;
    }
    }
    </style>
</head>
<body>

    <div class="container">
        <div class="form-container">
            <h1>Stock Ledger Report</h1>
            <form method="POST" class="mb-4" action="ledger.php" id="searchForm">
                <div class="form-row align-items-end">
                    <div class="col-md-3 col-12 mb-2">
                        <label for="start_date" class="col-form-label">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="col-md-3 col-12 mb-2">
                        <label for="end_date" class="col-form-label">End Date:</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="col-md-3 col-12 mb-2">
                        <label for="item_filter" class="col-form-label">Item Id/Name:</label>
                        <input type="text" id="item_filter" name="item_filter" class="form-control" value="<?php echo htmlspecialchars($item_filter); ?>">
                    </div>
                    <div class="col-md-3 col-12 mb-2">
                        <button type="submit" class="btn btn-primary w-100 btn-small">Filter</button>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" name="export" class="btn btn-small">Export CSV</button>
                    <button type="submit" name="pdf" class="btn btn-small">PDF</button>
                    <button type="button" class="btn btn-small" onclick="window.print()">Print</button>
                </div>
            </form>

            <!-- Stock In Hand Display -->
            <?php if ($item_filter): ?>
                <div class="alert alert-info text-center">
                    <?php if (isset($item_names[$item_filter])): ?>
                        Stock In Hand of Item <strong><?php echo htmlspecialchars($item_names[$item_filter]); ?></strong> is <strong>
                        <?php echo htmlspecialchars($current_balances[$item_names[$item_filter]] ?? 0); ?>
                        </strong>.
                    <?php else: ?>
                        Stock In Hand of Item <strong><?php echo htmlspecialchars($item_filter); ?></strong> is <strong>
                        <?php echo htmlspecialchars($current_balances[$item_filter] ?? 0); ?>
                        </strong>.<
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <?php foreach ($grouped_rows as $itemId => $itemTransactions): ?>
                    <h3>Item Id: <?php echo htmlspecialchars($itemId); ?> / Item Name: <?php echo htmlspecialchars($item_names[$itemId]); ?></h3>
                    <table class="table table-bordered table-striped" id="ledgerTable_<?php echo htmlspecialchars($itemId); ?>">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item Group Id</th>
                                <th>Item Group Name</th>
                                <th>Item Name</th>
                                <th>Stock In Id</th>
                                <th>Bill No</th>
                                <th>In Qty</th>
                                <th>Stock Out Id</th>
                                <th>Voucher No</th>
                                <th>Out Qty</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itemTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['entry_date']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Item_Group_Id']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Item_Group_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Item_Name']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Stock_In_Id']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Bill_No']); ?></td>
                                    <td class="in-qty"><?php echo htmlspecialchars($transaction['total_in_qty']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Stock_Out_Id']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Voucher_No']); ?></td>
                                    <td class="out-qty"><?php echo htmlspecialchars($transaction['total_out_qty']); ?></td>
                                    <td class="balance"><?php echo htmlspecialchars($transaction['current_balance']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with pagination
            $('table').DataTable({
                "paging": true, // Enable pagination
                "searching": true // Enable search feature
            });
        });

        // Custom print functionality to only print the table with heading
    function printTable(itemId) {
    // Find the specific table for the itemId being printed
    var table = document.getElementById('ledgerTable_' + itemId);

    // If the table exists, proceed to print
    if (table) {
        var tableHTML = table.outerHTML;
        var tableHeading = document.querySelector('h3').innerHTML;  // Get the heading for the item

        // Create a new HTML content for the print view
        var printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write('<html><head><title>Print Stock Ledger</title>');
        printWindow.document.write('<link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }');
        printWindow.document.write('h3 { color: #488AC7; font-size: 16px; text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 10px; }');
        printWindow.document.write('th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }');
        printWindow.document.write('th { background-color: #488AC7; color: white; }');
        printWindow.document.write('td { font-size: 12px; }');
        printWindow.document.write('@media print { .button-group, .btn-small { display: none; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h3>' + tableHeading + '</h3>');
        printWindow.document.write(tableHTML); // Insert the table HTML
        printWindow.document.write('</body></html>');

        // Print the content
        printWindow.document.close();
        printWindow.print();
    } else {
        alert('Table not found for Item ID: ' + itemId);
    }
}
    </script>
</body>
</html>
