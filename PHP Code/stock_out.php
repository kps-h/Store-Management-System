<?php
// Include database connection and navbar
include('db.php');

// Initialize variables
$success_message = '';
$error_message = '';

// Generate Stock Out ID
$stock_out_id = 'SOD#';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(Stock_Out_Id, 5) AS UNSIGNED)) AS max_id FROM stock_out");
if ($row = $result->fetch_assoc()) {
    $next_id = (int)$row['max_id'] + 1;
    $stock_out_id .= str_pad($next_id, 3, '0', STR_PAD_LEFT); // Auto-generate ID with leading zeros
}

// Get current date
$current_date = date('Y-m-d');

// Initialize form data
$voucher_no = $recipient_id = $recipient_name = $remarks = '';
$total_out = 0;
$items = [['item_id' => '', 'item_name' => '', 'uom' => '', 'qty' => '']];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['stock_out'])) {
    // Prepare and bind for the stock_out table
    $stmt = $conn->prepare("INSERT INTO stock_out (Stock_Out_Id, Voucher_No, Date_Out, Recipient_Id, Recipient_Name, Total_Out, Remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssis", $stock_out_id, $voucher_no, $date_out, $recipient_id, $recipient_name, $total_out, $remarks);

    // Get posted values
    $voucher_no = $_POST['voucher_no'] ?? null;
    $date_out = $current_date; // Use the current date
    $recipient_id = $_POST['recipient_id'] ?? null;
    $recipient_name = $_POST['recipient_name'] ?? null;
    $total_out = $_POST['total_out'] ?? 0; // Total quantity of items
    $remarks = $_POST['remarks'] ?? ''; // Remarks field

    // Validate fields
    if ($voucher_no && $recipient_id && $recipient_name) {
        // Execute statement for stock_out
        if ($stmt->execute()) {
            // Prepare for stock_out_items
            $stmt_items = $conn->prepare("INSERT INTO stock_out_items (Stock_Out_Id, Item_Id, Item_Name, UOM, Qty) VALUES (?, ?, ?, ?, ?)");
            $stmt_items->bind_param("ssssi", $stock_out_id, $item_id, $item_name, $uom, $qty);

            // Prepare for ledger1
            $stmt_ledger = $conn->prepare("INSERT INTO ledger1 (Item_Id, Item_Name, Stock_Out_Id, Date_Out, Voucher_No, out_qty) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ledger->bind_param("sssssi", $item_id, $item_name, $stock_out_id, $date_out, $voucher_no, $qty);

            // Loop through items and insert each one
            foreach ($_POST['item_id'] as $index => $item_id) {
                $item_name = $_POST['item_name'][$index];
                $uom = $_POST['uom'][$index];
                $qty = $_POST['qty'][$index];

                // Execute statement for each item
                if ($stmt_items->execute()) {
                    // Execute ledger insertion
                    $stmt_ledger->execute();
                } else {
                    $error_message = "Error inserting item: " . $stmt_items->error;
                }
            }

            // Close statements
            $stmt_items->close();
            $stmt_ledger->close();

            // Success message and reset form fields
            $success_message = "Form Submitted Successfully!";
            $voucher_no = $recipient_id = $recipient_name = $remarks = '';
            $items = [['item_id' => '', 'item_name' => '', 'uom' => '', 'qty' => '']];
            
            // JavaScript redirection after success
            echo "<script>
                window.onload = function() {
                    document.getElementById('successModal').style.display = 'block'; // Show modal
                    setTimeout(function() {
                        window.location.href = 'stock_out.php'; // Redirect after 3 seconds
                    }, 3000);
                };
            </script>";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
    } else {
        $error_message = "Missing required fields. Please fill all the fields.";
    }

    $stmt->close();
}

// Fetch all items for dropdown
$item_query = "SELECT Item_Id, Item_Name, UOM FROM item";
$item_result = $conn->query($item_query);
$items_list = [];
if ($item_result) {
    while ($row = $item_result->fetch_assoc()) {
        $items_list[] = $row;
    }
}

// Fetch all recipients for dropdown
$recipient_query = "SELECT recipient_id, recipient_name FROM recipient";
$recipient_result = $conn->query($recipient_query);
$recipients_list = [];
if ($recipient_result) {
    while ($row = $recipient_result->fetch_assoc()) {
        $recipients_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="styleS.css"> <!-- External CSS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
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
    </style>
    <script>
        // Fetch item details dynamically when item is selected
        function fetchItemDetails(itemId, index) {
            const items = <?php echo json_encode($items_list); ?>;
            const selectedItem = items.find(item => item.Item_Id === itemId);
            
            if (selectedItem) {
                document.getElementById(`item_name_${index}`).value = selectedItem.Item_Name;
                document.getElementById(`uom_${index}`).value = selectedItem.UOM;
            } else {
                document.getElementById(`item_name_${index}`).value = '';
                document.getElementById(`uom_${index}`).value = '';
            }
        }

        // Add a new item row
        function addItemRow() {
            const itemGrid = document.getElementById('item_grid').getElementsByTagName('tbody')[0];
            const rowCount = itemGrid.rows.length;
            const newRow = itemGrid.insertRow(rowCount);
            
            newRow.innerHTML = `
                <td>
                    <select name="item_id[]" id="item_id_${rowCount}" onchange="fetchItemDetails(this.value, ${rowCount})" class="form-control" required>
                        <option value="">Select Item</option>
                        <?php foreach ($items_list as $item): ?>
                            <option value="<?php echo $item['Item_Id']; ?>"><?php echo $item['Item_Id']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="item_name[]" id="item_name_${rowCount}" readonly class="form-control"></td>
                <td><input type="text" name="uom[]" id="uom_${rowCount}" readonly class="form-control"></td>
                <td><input type="number" name="qty[]" class="form-control" required oninput="calculateTotalOut()"></td>
                <td><button type="button" class="btn btn-danger" onclick="removeItemRow(this)">Remove</button></td>
            `;
        }

        // Remove item row
        function removeItemRow(button) {
            const row = button.closest('tr');
            row.remove();
            calculateTotalOut();
        }

        // Calculate total quantity of items
        function calculateTotalOut() {
            let totalQty = 0;
            const qtyInputs = document.querySelectorAll('input[name="qty[]"]');
            qtyInputs.forEach(input => {
                totalQty += parseInt(input.value) || 0;
            });
            document.getElementById('total_out').value = totalQty;
        }

        // Fetch recipient name when recipient id is selected
        function fetchRecipientName(recipientId) {
            const recipients = <?php echo json_encode($recipients_list); ?>;
            const selectedRecipient = recipients.find(recipient => recipient.recipient_id === recipientId);

            if (selectedRecipient) {
                document.getElementById('recipient_name').value = selectedRecipient.recipient_name;
            } else {
                document.getElementById('recipient_name').value = '';
            }
        }
    </script>
</head>
<body>
<div id="stock_out_form" class="form-container">
    <h2>Stock Out Form</h2>
    <form method="post">
        <!-- Form Fields -->
        <div class="form-group">
            <label for="stock_out_id">Stock Out Id:</label>
            <input type="text" name="stock_out_id" class="form-control" value="<?php echo $stock_out_id; ?>" readonly>
        </div>
		
		<div class="form-group">
            <label for="voucher_no">Voucher No:</label>
            <input type="text" name="voucher_no" class="form-control" value="<?php echo $voucher_no; ?>" required>
        </div>
		
        <div class="form-group">
            <label for="date_out">Date Out:</label>
            <input type="date" name="date_out" class="form-control" value="<?php echo $current_date; ?>" disabled>
        </div>

        <div class="form-group">
            <label for="recipient_id">Recipient:</label>
            <select name="recipient_id" id="recipient_id" class="form-control" onchange="fetchRecipientName(this.value)" required>
                <option value="">Select Recipient</option>
                <?php foreach ($recipients_list as $recipient): ?>
                    <option value="<?php echo $recipient['recipient_id']; ?>"><?php echo $recipient['recipient_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="recipient_name">Recipient Name:</label>
            <input type="text" name="recipient_name" id="recipient_name" class="form-control" value="<?php echo $recipient_name; ?>" readonly>
        </div>
        
		<h3>Items</h3>
        <table class="table item-table" id="item_grid" style="table-layout: fixed;">
            <thead>
                <tr>
                    <th>Item Id</th>
                    <th>Item Name</th>
                    <th>UOM</th>
                    <th>Qty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td>
                        <select name="item_id[]" id="item_id_<?php echo $index; ?>" onchange="fetchItemDetails(this.value, <?php echo $index; ?>)" class="form-control" required>
                            <option value="">Select Item</option>
                            <?php foreach ($items_list as $item_option): ?>
                                <option value="<?php echo $item_option['Item_Id']; ?>"><?php echo $item_option['Item_Id']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="item_name[]" id="item_name_<?php echo $index; ?>" readonly class="form-control" value="<?php echo $item['item_name']; ?>"></td>
                    <td><input type="text" name="uom[]" id="uom_<?php echo $index; ?>" readonly class="form-control" value="<?php echo $item['uom']; ?>"></td>
                    <td><input type="number" name="qty[]" oninput="calculateTotalOut()" class="form-control" required value="<?php echo $item['qty']; ?>"></td>
                    <td><button type="button" class="btn btn-danger" onclick="removeItemRow(this)">Remove</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="button" class="btn btn-primary" onclick="addItemRow()">Add Item</button>
        <br><br>

        <div class="form-group">
            <label for="total_out">Total Out:</label>
            <input type="number" name="total_out" id="total_out" readonly class="form-control" style="width: 150px;">
        </div>

        <div class="form-group">
            <label for="remarks">Remarks:</label>
            <textarea name="remarks" id="remarks" class="form-control" rows="2" placeholder="Enter any remarks here..."><?php echo $remarks; ?></textarea>
        </div>
		<button type="reset" class="btn btn-cancel">Cancel</button>
		<input type="submit" name="stock_out" class="btn btn-success" value="Submit" style="display: block; margin: 0 auto;">
    </form>

</div>

<!-- Modal for Success Message -->
<div id="successModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Form Submitted Successfully!</h3>
    </div>
</div>

<!-- JS Code for Modal -->
<script>
    // Close the modal when the user clicks the "X" button
    function closeModal() {
        document.getElementById("successModal").style.display = "none";
    }
</script>

</body>
</html>