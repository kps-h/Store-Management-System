<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the external database connection file
include('db.php'); // Ensure the db.php file is correct

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

// Fetching data from ledger with optional filters
$query = "
    SELECT 
        Item_Id,
        Item_Name,
        Stock_In_Id,
        Date_In,
        Bill_No,
        In_qty,
        Stock_Out_Id,
        Date_Out,
        Voucher_No,
        Out_qty,
        Balance,
        date AS entry_date
    FROM ledger
";

// Array to hold conditions based on filters
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
    $conditions[] = "(Item_Name LIKE '%$search_input%' OR Bill_No LIKE '%$search_input%' OR Voucher_No LIKE '%$search_input%')";
}

// If conditions are set, add them to the query
if ($conditions) {
    $query .= " WHERE " . implode(' AND ', $conditions);
}

// Order by Item_Id and entry_date
$query .= " ORDER BY Item_Id, entry_date";

// Debugging: Display the query to ensure it's correct
//echo "<pre>" . htmlspecialchars($query) . "</pre>"; // Uncomment to check query

// Execute the query
$result = $conn->query($query);

// Check if query returns results
if ($result && $result->num_rows > 0) {
    // Populate $rows if results are found
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
} else {
    echo "No results found."; // Debugging message if there are no results
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

    // Update balance for Item_Id and Item_Name based on In_qty and Out_qty
    $current_balances[$itemId] += $row['In_qty'];
    $current_balances[$itemId] -= $row['Out_qty'];
    
    $current_balances[$itemName] += $row['In_qty'];
    $current_balances[$itemName] -= $row['Out_qty'];

    // Store the row with the current balance
    $row['current_balance'] = $current_balances[$itemId];
    $grouped_rows[$itemId][] = $row; // Group by Item_Id
}

// Export to Excel Functionality
if (isset($_POST['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="ledger_export.xls"');

    // Re-run the same query
    $result = $conn->query($query);
    
    echo "Date\tItem Id\tItem Name\tStock In Id\tDate In\tBill No\tIn Qty\tStock Out Id\tDate Out\tVoucher No\tOut Qty\tBalance\n";

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
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
    $pdf->Cell(30, 10, 'Item Id', 1);
    $pdf->Cell(50, 10, 'Item Name', 1);
    $pdf->Cell(30, 10, 'In Qty', 1);
    $pdf->Cell(30, 10, 'Out Qty', 1);
    $pdf->Cell(30, 10, 'Balance', 1);
    $pdf->Ln();

    // Re-run the same query
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(40, 10, $row['entry_date'], 1);
        $pdf->Cell(30, 10, $row['Item_Id'], 1);
        $pdf->Cell(50, 10, $row['Item_Name'], 1);
        $pdf->Cell(30, 10, $row['In_qty'], 1);
        $pdf->Cell(30, 10, $row['Out_qty'], 1);
        $pdf->Cell(30, 10, $row['current_balance'], 1);
        $pdf->Ln();
    }

    $pdf->Output();
    exit;
}
$narration_message = '';

// Check if the filters are applied and there's an item filter
if ($item_filter) {
    // Get the item name from the filtered results (only for the first result)
    $filtered_item = $rows[0]['Item_Name']; // Taking the first row's item name
    $filtered_item_balance = $current_balances[$filtered_item]; // Getting the balance for that item
    // Generate the narration message
    $narration_message = "Stock In Hand of $filtered_item is : $filtered_item_balance";
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
            background-color: #007bff; 
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
            padding: 9px 60px;
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
            background-color: #007bff;
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
    </style>
</head>
<body>

<div class="container form-container">
    <h1>Stock Ledger</h1>
    
    <!-- Form for Filters and Export Buttons -->
<form method="POST">
    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" value="<?= $start_date ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" value="<?= $end_date ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label for="item_filter">Item Filter (Id or Name)</label>
            <input type="text" name="item_filter" id="item_filter" value="<?= $item_filter ?>" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <button type="submit" class="btn btn-primary btn-small" style="margin-top: 31px;">Apply Filters</button>
        </div>
    </div>

    <div class="button-group">
        <button type="submit" name="export" class="btn btn-success btn-small">Export to Excel</button>
        <button type="submit" name="pdf" class="btn btn-danger btn-small">Export to PDF</button>
    </div>
</form>

<!-- Display the narration message if it exists -->
<?php if ($narration_message): ?>
    <div class="alert alert-info text-center" style="margin-top: 20px; font-size: 1.2rem;">
        <?= $narration_message ?>
    </div>
<?php endif; ?>


    <div class="table-responsive">
        <table id="stockLedgerTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Id</th>
                    <th>Item Name</th>
                    <th>Stock In Id</th>
                    <th>Date In</th>
                    <th>Bill No</th>
                    <th>In Qty</th>
                    <th>Stock Out Id</th>
                    <th>Date Out</th>
                    <th>Voucher No</th>
                    <th>Out Qty</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($grouped_rows)): ?>
                    <tr>
                        <td colspan="12" class="alert alert-warning">No data found for the selected filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($grouped_rows as $group): ?>
                        <?php foreach ($group as $row): ?>
                            <tr>
                                <td><?= $row['entry_date'] ?></td>
                                <td><?= $row['Item_Id'] ?></td>
                                <td><?= $row['Item_Name'] ?></td>
                                <td><?= $row['Stock_In_Id'] ?></td>
                                <td><?= $row['Date_In'] ?></td>
                                <td><?= $row['Bill_No'] ?></td>
                                <td class="in-qty"><?= $row['In_qty'] ?></td>
                                <td><?= $row['Stock_Out_Id'] ?></td>
                                <td><?= $row['Date_Out'] ?></td>
                                <td><?= $row['Voucher_No'] ?></td>
                                <td class="out-qty"><?= $row['Out_qty'] ?></td>
                                <td class="balance"><?= $row['current_balance'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Include JS and DataTables for functionality -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script>
    $(document).ready(function() {
        $('#stockLedgerTable').DataTable();
    });
</script>
</body>
</html>
