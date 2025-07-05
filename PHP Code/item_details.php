<?php
include ('db.php');

// Check if we are on the transaction details page (when an item is clicked)
$item_id = isset($_GET['id']) ? $_GET['id'] : '';

if ($item_id) {
    // Fetch the transactions for the selected item
    $sql = "SELECT Date_In, Date_Out, Bill_No, Voucher_No, In_qty, Out_qty, qty, (In_qty - Out_qty - qty) AS Balance
            FROM ledger
            WHERE Item_Id = '$item_id'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<div class='container mt-4'>
                <h2>Transaction Details for Item: $item_id</h2>
                <table class='table table-bordered table-striped'>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Bill No</th>
                            <th>Voucher No</th>
                            <th>In Qty</th>
                            <th>Out Qty</th>
                            <th>Qty</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>";

        while ($row = $result->fetch_assoc()) {
            $date = $row['Date_In'] ? $row['Date_In'] : $row['Date_Out'];
            $balance = $row['In_qty'] - $row['Out_qty'] - $row['qty'];
            echo "<tr>
                    <td>" . $date . "</td>
                    <td>" . $row['Bill_No'] . "</td>
                    <td>" . $row['Voucher_No'] . "</td>
                    <td>" . $row['In_qty'] . "</td>
                    <td>" . $row['Out_qty'] . "</td>
                    <td>" . $row['qty'] . "</td>
                    <td>" . $balance . "</td>
                  </tr>";
        }

        echo "</tbody>
              </table>
              </div>";
    } else {
        echo "<div class='container mt-4'>
                <p>No transaction details found for this item.</p>
              </div>";
    }
} else {
    // Display the main ledger table
    $sql = "SELECT 
                Item_Name, 
                SUM(In_qty) AS Total_In_Qty, 
                SUM(Out_qty) AS Total_Out_Qty, 
                SUM(qty) AS Total_Qty, 
                (SUM(In_qty) - SUM(Out_qty) - SUM(qty)) AS Balance, 
                Item_Id
            FROM ledger
            GROUP BY Item_Name, Item_Id";
    
    $result = $conn->query($sql);

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Ledger Item Details</title>
        <link rel='stylesheet' href='https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css'>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'>
        <style>
            body {
                font-family: Arial, sans-serif;
            }
            table {
                width: 100%;
                margin: 20px 0;
            }
            .container {
                padding: 20px;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                border: 1px solid #ddd;
                padding: 5px 10px;
                cursor: pointer;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
                background-color: #f1f1f1;
            }
        </style>
    </head>
    <body>
        <div class='container mt-4'>
            <h2 class='text-center'>Ledger Item Details</h2>
            <table id='ledgerTable' class='table table-bordered table-striped'>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Total In Qty</th>
                        <th>Total Out Qty</th>
                        <th>Total Qty</th>
                        <th>Balance</th>
                    </tr>
                </thead>
                <tbody>";

    // Check if there are items in the ledger
    if ($result->num_rows > 0) {
        // Output each row of data
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td><a href='ledger.php?id=" . $row['Item_Id'] . "'>" . $row['Item_Name'] . "</a></td>
                    <td>" . $row['Total_In_Qty'] . "</td>
                    <td>" . $row['Total_Out_Qty'] . "</td>
                    <td>" . $row['Total_Qty'] . "</td>
                    <td>" . $row['Balance'] . "</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='5' class='text-center'>No data found</td></tr>";
    }

    // Final balance calculation
    $result->data_seek(0); // Reset result pointer for final balance calculation
    $finalBalance = 0;
    while ($row = $result->fetch_assoc()) {
        $finalBalance += $row['Balance'];
    }

    echo "</tbody>
          <tfoot>
            <tr>
                <th colspan='4'>Final Balance</th>
                <th>" . $finalBalance . "</th>
            </tr>
          </tfoot>
        </table>
    </div>";

    echo "<!-- jQuery and DataTables JS -->
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js'></script>
    <script src='https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js'></script>

    <script>
        $(document).ready(function() {
            $('#ledgerTable').DataTable({
                'responsive': true,
                'paging': true,
                'searching': true,
                'ordering': true,
                'info': true
            });
        });
    </script>
    </body>
    </html>";
}

// Close the database connection
$conn->close();
?>
