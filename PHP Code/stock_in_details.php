<?php
include('db.php'); // Ensure database connection is established.

if (isset($_GET['id'])) {
    $stock_in_id = $_GET['id']; // Get the Stock_In_Id from the URL parameter.
} else {
    die("Stock In Id is missing from the URL.");
}

// Query to fetch the stock in details.
$stock_in_sql = "SELECT * FROM stock_in WHERE Stock_In_Id = ?";
$stmt = $conn->prepare($stock_in_sql);
$stmt->bind_param("s", $stock_in_id);
$stmt->execute();
$stock_in_result = $stmt->get_result();

if ($stock_in_result->num_rows > 0) {
    $stock_in_data = $stock_in_result->fetch_assoc();
} else {
    die("Stock In details not found for Stock In Id: " . htmlspecialchars($stock_in_id));
}

// Query to fetch the stock in items.
$items_sql = "SELECT * FROM stock_in_items WHERE Stock_In_Id = ?";
$item_stmt = $conn->prepare($items_sql);
$item_stmt->bind_param("s", $stock_in_id);
$item_stmt->execute();
$item_result = $item_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In Details</title>
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
    <h1>Stock In Details</h1>
    
    <!-- Print Button (hidden during print) -->
    <button onclick="window.print();" class="btn btn-secondary no-print">Print</button>

    <!-- Stock In Details in Table -->
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
                    <td><strong>Stock In Id:</strong></td>
                    <td><?php echo htmlspecialchars($stock_in_data['Stock_In_Id']); ?></td>
                </tr>
                <tr>
                    <td><strong>Bill No:</strong></td>
                    <td><?php echo htmlspecialchars($stock_in_data['Bill_No']); ?></td>
                </tr>
                <tr>
                    <td><strong>Date In:</strong></td>
                    <td><?php echo htmlspecialchars($stock_in_data['Date_In']); ?></td>
                </tr>
                <tr>
                    <td><strong>Vendor Id:</strong></td>
                    <td><?php echo htmlspecialchars($stock_in_data['Vendor_Id']); ?></td>
                </tr>
                <tr>
                    <td><strong>Vendor Name:</strong></td>
                    <td><?php echo htmlspecialchars($stock_in_data['Vendor_Name']); ?></td>
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
                    echo "<tr><td colspan='4'>No items found for this Stock In Id</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Display Total In and Remarks -->
    <div class="remarks">
        <p><strong>Total In:</strong> <?php echo htmlspecialchars($stock_in_data['Total_In']); ?></p>
        <p><strong>Remarks:</strong> <?php echo htmlspecialchars($stock_in_data['Remarks']); ?></p>
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
