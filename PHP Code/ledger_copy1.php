<?php
include('db.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// SQL query to fetch data from the ledger table
$sql = "SELECT * FROM ledger ORDER BY Item_Id";
$result = $conn->query($sql);
$groupedData = [];

// Group data by Item_Id and calculate the cumulative balance for each transaction
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $itemId = $row['Item_Id'];

        if (!isset($groupedData[$itemId])) {
            $groupedData[$itemId] = [
                'Item_Name' => $row['Item_Name'],
                'transactions' => [],
                'cumulative_balance' => 0
            ];
        }

        // Calculate balance for the transaction based on the formula
        $balance = $row['In_qty'] - $row['Out_qty'] - $row['qty'];
        $groupedData[$itemId]['cumulative_balance'] += $balance;

        // Add the transaction to the 'transactions' array for that item
        $groupedData[$itemId]['transactions'][] = [
            'transaction' => $row,
            'balance' => $groupedData[$itemId]['cumulative_balance']
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Table with DataTables</title>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Same styles as before */
    </style>
</head>
<body>

<div class="container">
    <h1>Ledger Table with DataTables</h1>

    <div class="filter-section">
        <!-- Filters Section, same as before -->
    </div>

    <div id="stockInHandMessage"></div>

    <?php
    // Loop through the grouped data and display transactions for each Item
    foreach ($groupedData as $itemId => $itemData) {
        echo "<div class='item-group'>";
        // Make item name clickable link to item details page
        echo "<div class='item-title'><a href='item_details.php?itemId=$itemId'>$itemId - " . $itemData['Item_Name'] . "</a></div>";
        echo "<table id='datatable-" . $itemId . "' class='display'>
                <thead>
                    <tr>
                        <th>Item Id</th>
                        <th>Item Name</th>
                        <th>Date In</th>
                        <th>Date Out</th>
                        <th>Stock In Id</th>
                        <th>Stock Out Id</th>
                        <th>Bill No</th>
                        <th>Voucher No</th>
                        <th>In Qty</th>
                        <th>Out Qty</th>
                        <th>Date</th>
                        <th>Rejected Id</th>
                        <th>Qty</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($itemData['transactions'] as $transactionData) {
            $transaction = $transactionData['transaction'];
            $balance = $transactionData['balance'];

            echo "<tr>
                    <td>" . $transaction["Item_Id"] . "</td>
                    <td>" . $transaction["Item_Name"] . "</td>
                    <td>" . $transaction["Date_In"] . "</td>
                    <td>" . $transaction["Date_Out"] . "</td>
                    <td>" . $transaction["Stock_In_Id"] . "</td>
                    <td>" . $transaction["Stock_Out_Id"] . "</td>
                    <td>" . $transaction["Bill_No"] . "</td>
                    <td>" . $transaction["Voucher_No"] . "</td>
                    <td>" . $transaction["In_qty"] . "</td>
                    <td>" . $transaction["Out_qty"] . "</td>
                    <td>" . $transaction["date"] . "</td>
                    <td>" . $transaction["rejected_id"] . "</td>
                    <td>" . $transaction["qty"] . "</td>
                    <td>" . $balance . "</td>
                  </tr>";
        }

        echo "</tbody></table>";
        echo "</div>";
    }
    ?>

    <!-- Export buttons -->
    <button id="printBtn">Print</button>
    <button id="csvBtn">Export to CSV</button>
    <button id="pdfBtn">Export to PDF</button>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTables for each table
        <?php
        foreach ($groupedData as $itemId => $itemData) {
            echo "$('#datatable-" . $itemId . "').DataTable();";
        }
        ?>

        // Print functionality
        $("#printBtn").click(function() {
            window.print();
        });

        // CSV Export functionality
        $("#csvBtn").click(function() {
            $('.display').each(function() {
                var table = $(this);
                table.DataTable().button('.buttons-csv').trigger();
            });
        });

        // PDF Export functionality
        $("#pdfBtn").click(function() {
            var doc = new jsPDF();
            doc.autoTable({ html: 'table' });
            doc.save('ledger-table.pdf');
        });

        // Date filters
        flatpickr("#startDateFilter", {
            dateFormat: "Y-m-d",
            onChange: filterTable
        });

        flatpickr("#endDateFilter", {
            dateFormat: "Y-m-d",
            onChange: filterTable
        });

        // Filter function
        function filterTable() {
            // Your filtering logic here
        }
    });
</script>

</body>
</html>

