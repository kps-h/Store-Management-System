<?php
// Include database connection
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

// Fetch available quantities, considering previous stock-out transactions
$available_query = "
    SELECT 
        sii.Item_Id,
        sii.Item_Name,
        COALESCE(SUM(sii.Qty), 0) - COALESCE(SUM(ri.Rejected_Qty), 0) - COALESCE(SUM(soi.Qty), 0) AS Available_Qty
    FROM 
        stock_in_items sii
    LEFT JOIN 
        rejected_items ri ON sii.Item_Id = ri.Item_Id
    LEFT JOIN
        stock_out_items soi ON sii.Item_Id = soi.Item_Id
    GROUP BY 
        sii.Item_Id, sii.Item_Name
";
$available_result = $conn->query($available_query);
$available_quantities = [];
if ($available_result) {
    while ($row = $available_result->fetch_assoc()) {
        $available_quantities[$row['Item_Id']] = $row['Available_Qty'];
    }
}
$available_quantities_json = json_encode($available_quantities);


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
        .available-qty-text {
            color: green;
            font-weight: bold;
        }
    </style>
    <script>
        // Pass the available quantities from PHP to JavaScript
        const availableQuantities = <?php echo $available_quantities_json; ?>;

        // Fetch item details dynamically when item is selected
        function fetchItemDetails(itemId, index) {
            const selectedItem = <?php echo json_encode($items_list); ?>.find(item => item.Item_Id === itemId);
            
            if (selectedItem) {
                // Update item name and UOM
                document.getElementById(`item_name_${index}`).value = selectedItem.Item_Name;
                document.getElementById(`uom_${index}`).value = selectedItem.UOM;
                
                // Display available quantity for the selected item
                const availableQty = availableQuantities[itemId] || 0;
                document.getElementById(`available_qty_${index}`).innerText = `Available: ${availableQty}`;
            } else {
                // Reset fields if no item is selected
                document.getElementById(`item_name_${index}`).value = '';
                document.getElementById(`uom_${index}`).value = '';
                document.getElementById(`available_qty_${index}`).innerText = '';
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
                <td>
                    <span id="available_qty_${rowCount}" class="available-qty-text">Available: 0</span>
                    <button type="button" class="btn btn-danger" onclick="removeItemRow(this)">Remove</button>
                </td>
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
    <div class="container mt-4">
        <h2>Stock Out Form</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="stock_out_id">Stock Out ID</label>
                <input type="text" class="form-control" id="stock_out_id" name="stock_out_id" value="<?php echo $stock_out_id; ?>" readonly>
            </div>
            <div class="form-group">
                <label for="voucher_no">Voucher No</label>
                <input type="text" class="form-control" id="voucher_no" name="voucher_no" required>
            </div>
            <div class="form-group">
                <label for="recipient_id">Recipient</label>
                <select name="recipient_id" id="recipient_id" class="form-control" onchange="fetchRecipientName(this.value)" required>
                    <option value="">Select Recipient</option>
                    <?php foreach ($recipients_list as $recipient): ?>
                        <option value="<?php echo $recipient['recipient_id']; ?>"><?php echo $recipient['recipient_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="recipient_name">Recipient Name</label>
                <input type="text" class="form-control" id="recipient_name" name="recipient_name" readonly>
            </div>
            <div id="item_grid">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th>Quantity</th>
                            <th>Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="item_id[]" id="item_id_0" onchange="fetchItemDetails(this.value, 0)" class="form-control" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($items_list as $item): ?>
                                        <option value="<?php echo $item['Item_Id']; ?>"><?php echo $item['Item_Id']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="item_name[]" id="item_name_0" readonly class="form-control"></td>
                            <td><input type="text" name="uom[]" id="uom_0" readonly class="form-control"></td>
                            <td><input type="number" name="qty[]" class="form-control" required oninput="calculateTotalOut()"></td>
                            <td>
                                <span id="available_qty_0" class="available-qty-text">Available: 0</span>
                                <button type="button" class="btn btn-danger" onclick="removeItemRow(this)">Remove</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary" onclick="addItemRow()">Add Item</button>
            </div>
            <div class="form-group mt-4">
                <label for="total_out">Total Quantity</label>
                <input type="text" class="form-control" id="total_out" name="total_out" value="0" readonly>
            </div>
            <div class="form-group">
                <label for="remarks">Remarks</label>
                <textarea class="form-control" name="remarks" id="remarks" rows="3"><?php echo $remarks; ?></textarea>
            </div>
            <button type="submit" name="stock_out" class="btn btn-success">Submit</button>
        </form>
        
        <!-- Success Modal -->
        <div id="successModal" class="modal" style="display: none;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-body">
                        <p><?php echo $success_message; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
