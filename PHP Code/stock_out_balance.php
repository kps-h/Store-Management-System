<?php
// Include the external database connection
include('db.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate Stock Out ID
$stock_out_id = 'SOUT#';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(Stock_Out_Id, 6) AS UNSIGNED)) AS max_id FROM stock_out");
if ($row = $result->fetch_assoc()) {
    $next_id = (int)$row['max_id'] + 1;
    $stock_out_id .= str_pad($next_id, 3, '0', STR_PAD_LEFT); // Auto-generate ID with leading zeros
}

// Fetch recipients
$recipients = [];
$recipient_result = $conn->query("SELECT Recipient_Id, Recipient_Name FROM recipient");
while ($row = $recipient_result->fetch_assoc()) {
    $recipients[] = $row;
}

// Fetch items
$items = [];
$item_result = $conn->query("SELECT Item_Id, Item_Name, UOM FROM item");
while ($row = $item_result->fetch_assoc()) {
    $items[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['stock_out'])) {
        // Prepare and bind for the stock_out table
        $stmt = $conn->prepare("INSERT INTO stock_out (Stock_Out_Id, Voucher_No, Date_Out, Recipient_Id, Recipient_Name, Total_Out, Remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $stock_out_id, $voucher_no, $date_out, $recipient_id, $recipient_name, $total_out, $remarks);

        // Get the posted values
        $voucher_no = $_POST['voucher_no'];
        $date_out = date('Y-m-d'); // Use current date
        $recipient_id = $_POST['recipient_id'];
        $recipient_name = $_POST['recipient_name'];
        $total_out = $_POST['total_out'];
        $remarks = $_POST['remarks'];

        // Execute the statement for stock_out
        if ($stmt->execute()) {
            // Prepare for stock_out_items
            $stmt_items = $conn->prepare("INSERT INTO stock_out_items (Stock_Out_Id, Item_Id, Item_Name, Qty, Rejected_qty, Out_qty) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_items->bind_param("sssiii", $stock_out_id, $item_id, $item_name, $uom, $rejected_qty, $out_qty);

            // Prepare for ledger1
            $stmt_ledger = $conn->prepare("INSERT INTO ledger (Item_Id, Item_Name, Stock_Out_Id, Date_Out, Voucher_No, Out_qty) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ledger->bind_param("sssssi", $item_id, $item_name, $stock_out_id, $date_out, $voucher_no, $out_qty);

            // Loop through items and insert each one
            foreach ($_POST['item_id'] as $index => $item_id) {
                $item_name = $_POST['item_name'][$index];
                $qty = $_POST['qty'][$index];
                $rejected_qty = $_POST['rejected_qty'][$index];
                $out_qty = $_POST['out_qty'][$index]; // Get the Out Quantity

                // Insert into stock_out_items
                $stmt_items->execute();

                // Insert into ledger1 for out quantity
                $stmt_ledger->execute();
            }

            $stmt_items->close();
            $stmt_ledger->close();
        } else {
            // Show error message if the form submission fails
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out Form</title>
    <!-- Include External CSS -->
    <link rel="stylesheet" href="styleS.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

    <style>
        .btn-cancel {
            background-color: #7f8c8d;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            max-width: 100px;
        }
        .btn-cancel:hover {
            background-color: #95a5a6;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }
        .modal-dialog {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
        }
    </style>

    <script>
        // Fetch item details including balance dynamically
        function fetchItemDetails(itemId, rowIndex) {
            $.ajax({
                url: 'fetch_it.php',  // Adjust the file path
                method: 'POST',
                data: {
                    item_id: itemId  // Send the Item ID to fetch item details
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    // Update item details in the row
                    document.getElementById(`item_name_${rowIndex}`).value = data.item_name;
                    document.getElementById(`uom_${rowIndex}`).value = data.uom;
                    document.getElementById(`balance_${rowIndex}`).value = data.balance;
                }
            });
        }

        // Add Item Row with Balance Display
        function addItemRow() {
            const itemGrid = document.getElementById('item_grid').getElementsByTagName('tbody')[0];
            const rowCount = itemGrid.rows.length;
            const newRow = itemGrid.insertRow(rowCount);

            newRow.innerHTML = `
                <td>
                    <select name="item_id[]" id="item_id_${rowCount}" onchange="fetchItemDetails(this.value, ${rowCount})" class="form-control" required>
                        <option value="">Select Item</option>
                        <!-- Options will be dynamically added here from PHP -->
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['Item_Id']; ?>"><?php echo $item['Item_Id']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="item_name[]" id="item_name_${rowCount}" readonly class="form-control"></td>
                <td><input type="number" name="qty[]" id="qty_${rowCount}" class="form-control" required oninput="calculateOutQty(${rowCount})"></td>
                <td><input type="number" name="rejected_qty[]" id="rejected_qty_${rowCount}" class="form-control" oninput="calculateOutQty(${rowCount})" required></td>
                <td><input type="number" name="out_qty[]" id="out_qty_${rowCount}" class="form-control" readonly></td>
                <td><input type="text" name="balance[]" id="balance_${rowCount}" class="form-control" readonly></td> <!-- Balance column -->
                <td><button type="button" class="btn-remove" onclick="removeItemRow(this)">Remove</button></td>
            `;
        }

        // Calculate Out Qty (Qty - Rejected Qty)
        function calculateOutQty(rowIndex) {
            const qty = parseInt(document.getElementById(`qty_${rowIndex}`).value) || 0;
            const rejectedQty = parseInt(document.getElementById(`rejected_qty_${rowIndex}`).value) || 0;
            const outQty = qty - rejectedQty;

            document.getElementById(`out_qty_${rowIndex}`).value = outQty;
            calculateTotalOut();  // Recalculate total out
        }

        // Calculate Total Out Qty including all rows
        function calculateTotalOut() {
            let totalOut = 0;
            const outQtyInputs = document.querySelectorAll('input[name="out_qty[]"]');

            outQtyInputs.forEach(input => {
                const outQty = parseInt(input.value) || 0;
                totalOut += outQty;
            });

            document.getElementById('total_out').value = totalOut; // Update Total Out field
        }

        // Trigger success message after form submission
        function showConfirmation() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'block';
            setTimeout(function() {
                modal.style.display = 'none';
            }, 3000); // Hide after 3 seconds
        }

        // Form submission with confirmation
        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault();
            showConfirmation(); // Show modal
            setTimeout(() => {
                event.target.submit();  // Submit the form after a short delay
            }, 1000);
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Stock Out Form</h2>
            <form method="post">
                <div class="form-group">
                    <label for="stock_out_id">Stock Out Id:</label>
                    <input type="text" class="form-control" name="stock_out_id" id="stock_out_id" value="SOUT#001" readonly>
                </div>
                <div class="form-group">
                    <label for="voucher_no">Voucher No:</label>
                    <input type="text" class="form-control" name="voucher_no" required>
                </div>
                <div class="form-group">
                    <label for="date_out">Date Out:</label>
                    <input type="date" name="date_out" value="<?php echo date('Y-m-d'); ?>" class="form-control" readonly required>
                </div>
                <div class="form-group">
                    <label for="recipient_id">Recipient:</label>
                    <select name="recipient_id" class="form-control" required>
                        <option value="">Select Recipient</option>
                        <!-- Populate with recipient options -->
                        <?php foreach ($recipients as $recipient): ?>
                            <option value="<?php echo $recipient['Recipient_Id']; ?>"><?php echo $recipient['Recipient_Name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h3>Items</h3>
                <table class="table table-bordered" id="item_grid">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th>Rejected Qty</th>
                            <th>Out Qty</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Item rows will be dynamically inserted here -->
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary" onclick="addItemRow()">Add Item</button>
                <div class="form-group">
                    <label for="total_out">Total Out Qty:</label>
                    <input type="number" class="form-control" id="total_out" name="total_out" readonly>
                </div>
                <button type="submit" class="btn btn-success">Submit</button>
            </form>
        </div>
    </div>

    <!-- Modal for confirmation -->
    <div id="confirmationModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <p class="text-center">Form submitted successfully!</p>
            </div>
        </div>
    </div>
</body>
</html>
