<?php
// Include the external database connection file
include('db.php'); // This will include your db connection

// Initialize variables
$item_summary = [];
$transactions = [];
$selected_item_id = '';
$item_name = '';

// Fetching summary data for all items
$query = "
    SELECT 
        Item_Id,
        Item_Name,
        SUM(COALESCE(in_qty, 0)) AS total_in_qty,
        SUM(COALESCE(out_qty, 0)) AS total_out_qty
    FROM ledger1
    GROUP BY Item_Id, Item_Name
    ORDER BY Item_Id
";

$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['balance'] = $row['total_in_qty'] - $row['total_out_qty'];
        $item_summary[] = $row;
    }
}

// Fetch transactions if an item is selected
if (isset($_GET['item_id'])) {
    $selected_item_id = $_GET['item_id'];
    
    // Fetch item name for the selected item ID
    $query_item_name = "SELECT Item_Name FROM ledger1 WHERE Item_Id = '$selected_item_id' LIMIT 1";
    $result_item_name = $conn->query($query_item_name);
    if ($result_item_name && $result_item_name->num_rows > 0) {
        $item_row = $result_item_name->fetch_assoc();
        $item_name = $item_row['Item_Name'];
    }

    // Fetch transaction details and keep them separate for in_qty and out_qty
    $query_transactions = "
        SELECT 
            COALESCE(Date_In, Date_Out) AS Date,
            Bill_No,
            Voucher_No,
            in_qty,
            out_qty
        FROM ledger1
        WHERE Item_Id = '$selected_item_id'
        ORDER BY Date_In DESC, Date_Out DESC
    ";

    $result_transactions = $conn->query($query_transactions);
    if ($result_transactions && $result_transactions->num_rows > 0) {
        $running_balance = 0; // Initialize the running balance to 0
        
        // Loop through each transaction and calculate the running balance for each row
        while ($row = $result_transactions->fetch_assoc()) {
            // If there's incoming quantity, update the balance
            if ($row['in_qty'] > 0) {
                $running_balance += $row['in_qty'];
                $row['balance'] = $running_balance; // Update the balance
                $transactions[] = $row;
            }
            
            // If there's outgoing quantity, update the balance
            if ($row['out_qty'] > 0) {
                $running_balance -= $row['out_qty'];
                $row['balance'] = $running_balance; // Update the balance
                $transactions[] = $row;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Ledger</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
    <style>
        body {
            background-color:  #ffffff; 
            margin: 0; 
        }
        .form-container {
            padding: 40px; 
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin: 40px auto; 
            max-width: 1200px;
            width: 90%; 
            margin-left: auto;
            margin-right: auto;
        }
        h1 {
            text-align: center;
            color: white; 
            background-color: #488AC7; 
            padding: 15px;
            border-radius: 10px;
        }
        h2 {
            color: #488AC7;
            margin-top: 20px;
        }
        .no-records {
            text-align: center;
            color: #dc3545; 
        }
        @media (max-width: 768px) {
            .form-container {
                padding: 20px; 
            }
            h1 {
                font-size: 1.5rem; 
            }
            h2 {
                font-size: 1.25rem; 
            }
        }
        .in-qty {
            color: green;
            font-weight: bold;
        }
        .out-qty {
            color: red;
            font-weight: bold;
        }
        .balance-field {
            color: #000000;
            font-weight: bold;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
    <script>
    $(document).ready(function() {
        // DataTable for Item Summary with sorting enabled only on the "Items" column
        <?php if (empty($selected_item_id)): ?>
            $('#itemSummaryTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv',
                        text: 'Export CSV',
                        title: 'Item Summary Report'
                    },
                    {
                        extend: 'pdf',
                        text: 'Export PDF',
                        title: 'Item Summary Report'
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        title: 'Item Summary Report'
                    }
                ],
                searching: true, // Disable search bar
                "columnDefs": [
                    {
                        "targets": [1, 2, 3], // Disable sorting on all columns except the first one
                        "orderable": false
                    }
                ],
                "order": [[0, 'asc']] // Default sorting on "Items" column
            });
        <?php endif; ?>

        // DataTable for Transaction Report with sorting only on the "Date" column and no pagination
        <?php if ($selected_item_id && !empty($transactions)): ?>
            $('#transactionsTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csv',
                        text: 'Export CSV',
                        title: `Transaction Report - Item ID: <?php echo htmlspecialchars($selected_item_id); ?> - Item Name: <?php echo htmlspecialchars($item_name); ?>`
                    },
                    {
                        extend: 'pdf',
                        text: 'Export PDF',
                        title: `Transaction Report - Item ID: <?php echo htmlspecialchars($selected_item_id); ?> - Item Name: <?php echo htmlspecialchars($item_name); ?>`
                    },
                    {
                        extend: 'print',
                        text: 'Print',
                        title: 'Transaction Report',
                        message: `Item ID: <?php echo htmlspecialchars($selected_item_id); ?><br>
                                   Item Name: <?php echo htmlspecialchars($item_name); ?><br>
                                   Date of Print: <?php echo date("Y-m-d"); ?>`
                    }
                ],
                searching: false, // Disable search bar
                "ordering": true, // Enable sorting
                "order": [[0, 'desc']], // Default sorting on the "Date" column (descending)
                "paging": true, // Disable pagination
                "columnDefs": [
                    {
                        "targets": [1, 2, 3, 4], // Disable sorting on columns other than "Date"
                        "orderable": false
                    }
                ]
            });
        <?php endif; ?>
    });
</script>
</head>
<body>
    <div class="form-container">
        <h1>Stock Ledger</h1>

        <?php if (empty($selected_item_id)): ?>
            <h2>Items Summary</h2>
            <table id="itemSummaryTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Items</th>
                        <th>In Qty</th>
                        <th>Out Qty</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($item_summary)): ?>
                        <?php foreach ($item_summary as $item): ?>
                            <tr>
                                <td>
                                    <a href="?item_id=<?php echo htmlspecialchars($item['Item_Id']); ?>">
                                        <?php echo htmlspecialchars($item['Item_Name']); ?>
                                    </a>
                                </td>
                                <td class="in-qty"><?php echo htmlspecialchars($item['total_in_qty']); ?></td>
                                <td class="out-qty"><?php echo htmlspecialchars($item['total_out_qty']); ?></td>
                                <td class="balance-field"><?php echo htmlspecialchars($item['balance']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-records">No items found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php if (!empty($transactions)): ?>
                <div class="mb-3 print-header">
                    <h2>Transaction Report</h2>
                    <strong>Item ID: </strong> <span><?php echo htmlspecialchars($selected_item_id); ?></span><br>
                    <strong>Item Name: </strong> <span><?php echo htmlspecialchars($item_name); ?></span><br>
                    <strong>Date of Print: </strong> <span><?php echo date("Y-m-d"); ?></span>
                </div>
                <table id="transactionsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bill No</th>
                            <th>Voucher No</th>
                            <th>In Qty</th>
                            <th>Out Qty</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['Date']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['Bill_No']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['Voucher_No']); ?></td>
                                <td class="in-qty"><?php echo htmlspecialchars($transaction['in_qty']); ?></td>
                                <td class="out-qty"><?php echo htmlspecialchars($transaction['out_qty']); ?></td>
                                <td class="balance-field"><?php echo htmlspecialchars($transaction['balance']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">Final Balance:</th>
                            <th class="balance-field"><?php echo htmlspecialchars($transaction['balance']); ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p class="no-records">No transactions found for this item.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close the database connection at the end of the script
$conn->close();
?>
