<?php
include('db.php'); // Ensure database connection is established.

if (isset($_GET['id'])) {
    $stock_out_id = $_GET['id']; // Get the Stock_Out_Id from the URL parameter.
} else {
    die("Stock Out Id is missing from the URL.");
}

// Query to fetch the stock out details.
$stock_out_sql = "SELECT * FROM stock_out WHERE Stock_Out_Id = ?";
$stmt = $conn->prepare($stock_out_sql);
$stmt->bind_param("s", $stock_out_id);
$stmt->execute();
$stock_out_result = $stmt->get_result();

if ($stock_out_result->num_rows > 0) {
    $stock_out_data = $stock_out_result->fetch_assoc();
} else {
    die("Stock Out details not found for Stock Out Id: " . htmlspecialchars($stock_out_id));
}

// Query to fetch the stock out items.
$items_sql = "SELECT * FROM stock_out_items WHERE Stock_Out_Id = ?";
$item_stmt = $conn->prepare($items_sql);
$item_stmt->bind_param("s", $stock_out_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out Details</title>
    <!-- Link to external CSS -->
    <link rel="stylesheet" type="text/css" href="new_style.css">

    <!-- Link to DataTable CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

    <!-- Include jQuery and DataTable JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

    <!-- CSS for hiding the print button during printing -->
    <style>
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Stock Out Details</h1>

    <!-- Print Button (hidden during print) -->
    <button onclick="window.print();" class="btn btn-secondary no-print">Print</button>

    <!-- Stock Out Details in Table -->
    <div class="details">
        <table>
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Stock Out Id:</strong></td>
                    <td><?php echo htmlspecialchars($stock_out_data['Stock_Out_Id']); ?></td>
                </tr>
                <tr>
                    <td><strong>Voucher No:</strong></td>
                    <td><?php echo htmlspecialchars($stock_out_data['Voucher_No']); ?></td>
                </tr>
                <tr>
                    <td><strong>Date Out:</strong></td>
                    <td><?php echo htmlspecialchars($stock_out_data['Date_Out']); ?></td>
                </tr>
                <tr>
                    <td><strong>Recipient Id:</strong></td>
                    <td><?php echo htmlspecialchars($stock_out_data['Recipient_Id']); ?></td>
                </tr>
                <tr>
                    <td><strong>Recipient Name:</strong></td>
                    <td><?php echo htmlspecialchars($stock_out_data['Recipient_Name']); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h2 style="text-align: left;">Items</h2>

    <!-- Display items in a table -->
    <div class="items">
        <table id="items_table" class="display">
            <thead>
                <tr>
                    <th>Item Id</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($item_result->num_rows > 0) {
                    while ($item = $item_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['Item_Id']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['Item_Name']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['UOM']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['Qty']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No items found for this Stock Out Id</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Display Total Out and Remarks -->
    <div class="remarks">
        <p><strong>Total Out:</strong> <?php echo htmlspecialchars($stock_out_data['Total_Out']); ?></p>
        <p><strong>Remarks:</strong> <?php echo htmlspecialchars($stock_out_data['Remarks']); ?></p>
    </div>
</div>

<!-- Initialize DataTable for the items table -->
<script>
$(document).ready(function() {
    $('#items_table').DataTable(); // Initialize the DataTable
    pagination: false,
    searching: false,
});
</script>

</body>
</html>

<?php
// Close the statements and connection
$stmt->close();
$item_stmt->close();
$conn->close();
?>
