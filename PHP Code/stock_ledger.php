<?php
// Include the external database connection
include('db.php');

// Generate Stock In ID
$stock_in_id = 'SID#';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(Stock_In_Id, 5) AS UNSIGNED)) AS max_id FROM stock_transactions");
if ($row = $result->fetch_assoc()) {
    $next_id = (int)$row['max_id'] + 1;
    $stock_in_id .= str_pad($next_id, 3, '0', STR_PAD_LEFT); // Auto-generate ID with leading zeros
}

// Fetch vendors and items
$vendors = [];
$vendor_result = $conn->query("SELECT Vendor_Id, Vendor_Name FROM vendor");
while ($row = $vendor_result->fetch_assoc()) {
    $vendors[] = $row;
}

$items = [];
$item_result = $conn->query("SELECT Item_Id, Item_Name, UOM FROM item");
while ($row = $item_result->fetch_assoc()) {
    $items[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['stock_in'])) {
        // Prepare and bind for the stock_transactions table
        $stmt = $conn->prepare("INSERT INTO stock_transactions (Stock_In_Id, Bill_No, Date_In, Total_In, Remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $stock_in_id, $bill_no, $date_in, $total_in, $remarks);

        // Get the posted values
        $bill_no = $_POST['bill_no'];
        $date_in = date('Y-m-d'); // Use current date
        $total_in = $_POST['total_in'];
        $remarks = $_POST['remarks'];

        // Execute the statement for stock_transactions
        if ($stmt->execute()) {
            // Prepare for stock_transaction_items
            $stmt_items = $conn->prepare("INSERT INTO stock_transaction_items (Stock_In_Id, Item_Id, Item_Name, UOM, Qty, Status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_items->bind_param("ssssis", $stock_in_id, $item_id, $item_name, $uom, $qty, $status);

            // Prepare for stock_ledger
            $stmt_ledger = $conn->prepare("INSERT INTO stock_ledger (Item_Id, Item_Name, Date_In, Bill_No, In_Qty, Rejected_Qty) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ledger->bind_param("ssssdd", $item_id, $item_name, $date_in, $bill_no, $in_qty, $rejected_qty);

            // Loop through items and insert each one
            $items_inserted = 0; // Counter for inserted items
            foreach ($_POST['item_id'] as $index => $item_id) {
                $item_name = $_POST['item_name'][$index];
                $uom = $_POST['uom'][$index];
                $qty = $_POST['qty'][$index];
                $status = $_POST['status'][$index];  // Get the status (Accepted/Rejected)

                // Set quantity based on status
                if ($status == 'Accepted') {
                    $in_qty = (float)$qty;
                    $rejected_qty = 0.00;
                } else {  // For Rejected status
                    $in_qty = 0.00;
                    $rejected_qty = (float)$qty;
                }

                // Execute statement for stock_transaction_items
                if ($stmt_items->execute()) {
                    // If the item is accepted or rejected, execute statement for stock_ledger
                    $stmt_ledger->execute();
                }
                $items_inserted++;
            }

            // Close prepared statements
            $stmt_items->close();
            $stmt_ledger->close();

            // Show the modal after successful form submission
            echo '<script>
                    window.onload = function() {
                        document.getElementById("successModal").style.display = "block";
                        setTimeout(function() {
                            window.location.href = "stock_ledger.php"; // Redirect to another page after 3 seconds
                        }, 3000);
                    };
                  </script>';
        } else {
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
    <title>Stock In Form</title>
    <link rel="stylesheet" href="styleS.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
        .status-select {
            width: 150px;
        }
    </style>
    <script>
        // Fetch vendor name dynamically
        function fetchVendorName(vendorId) {
            let vendorName = document.getElementById("vendor_name");
            const vendors = <?php echo json_encode($vendors); ?>;
            const selectedVendor = vendors.find(vendor => vendor.Vendor_Id === vendorId);
            vendorName.value = selectedVendor ? selectedVendor.Vendor_Name : '';
        }

        // Fetch item details dynamically
        function fetchItemDetails(itemId, rowIndex) {
            const items = <?php echo json_encode($items); ?>;
            const selectedItem = items.find(item => item.Item_Id === itemId);
            
            if (selectedItem) {
                document.getElementById(`item_name_${rowIndex}`).value = selectedItem.Item_Name;
                document.getElementById(`uom_${rowIndex}`).value = selectedItem.UOM;
            } else {
                document.getElementById(`item_name_${rowIndex}`).value = '';
                document.getElementById(`uom_${rowIndex}`).value = '';
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
                        <?php foreach ($items as $item): ?>
                            <option value="<?php echo $item['Item_Id']; ?>"><?php echo $item['Item_Id']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="text" name="item_name[]" id="item_name_${rowCount}" readonly class="form-control"></td>
                <td><input type="text" name="uom[]" id="uom_${rowCount}" readonly class="form-control"></td>
                <td><input type="number" name="qty[]" oninput="calculateTotalQty()" class="form-control" required></td>
                <td>
                    <select name="status[]" id="status_${rowCount}" class="status-select">
                        <option value="Accepted">Accepted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </td>
                <td><button type="button" class="btn-remove" onclick="removeItemRow(this)">Remove</button></td>
            `;
        }

        // Remove an item row
        function removeItemRow(button) {
            const row = button.closest('tr');
            row.remove();
            calculateTotalQty();
        }

        // Calculate the total quantity of all accepted items
        function calculateTotalQty() {
            let totalQty = 0;
            const qtyInputs = document.querySelectorAll('input[name="qty[]"]');
            qtyInputs.forEach((input, index) => {
                const status = document.querySelector(`#status_${index}`).value;
                if (status === 'Accepted') {
                    totalQty += parseInt(input.value) || 0;
                }
            });
            document.getElementById('total_in').value = totalQty;
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="text-center">Stock In Form</h2>
            <form method="post">
                <div class="form-group">
                    <label for="stock_in_id">Stock In Id:</label>
                    <input type="text" name="stock_in_id" value="<?php echo $stock_in_id; ?>" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label for="bill_no">Bill No:</label>
                    <input type="text" name="bill_no" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="date_in">Date In:</label>
                    <input type="date" name="date_in" value="<?php echo date('Y-m-d'); ?>" class="form-control" readonly required>
                </div>
                <div class="form-group">
                    <label for="vendor_id">Vendor Id:</label>
                    <select name="vendor_id" id="vendor_id" onchange="fetchVendorName(this.value)" class="form-control" required>
                        <option value="">Select Vendor</option>
                        <?php foreach ($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['Vendor_Id']; ?>"><?php echo $vendor['Vendor_Id']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="vendor_name">Vendor Name:</label>
                    <input type="text" name="vendor_name" id="vendor_name" readonly class="form-control">
                </div>

                <h3>Items</h3>
                <table class="table item-table" id="item_grid">
                    <thead>
                        <tr>
                            <th>Item Id</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <select name="item_id[]" id="item_id_0" onchange="fetchItemDetails(this.value, 0)" class="form-control" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['Item_Id']; ?>"><?php echo $item['Item_Id']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input type="text" name="item_name[]" id="item_name_0" readonly class="form-control"></td>
                            <td><input type="text" name="uom[]" id="uom_0" readonly class="form-control"></td>
                            <td><input type="number" name="qty[]" oninput="calculateTotalQty()" class="form-control" required></td>
                            <td>
                                <select name="status[]" id="status_0" class="status-select">
                                    <option value="Accepted">Accepted</option>
                                    <option value="Rejected">Rejected</option>
                                </select>
                            </td>
                            <td><button type="button" class="btn-remove" onclick="removeItemRow(this)">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary" onclick="addItemRow()">Add Item</button>
                <br><br>
                <div class="form-group">
                    <label for="total_in">Total In:</label>
                    <input type="number" name="total_in" id="total_in" readonly class="form-control" style="width: 150px;">
                </div>
                <div class="form-group">
                    <label for="remarks">Remarks:</label>
                    <input type="text" name="remarks" class="form-control" rows="3" placeholder="Enter any remarks here...">
                </div>
                <button type="reset" class="btn btn-cancel">Cancel</button>
                <input type="submit" name="stock_in" class="btn btn-success" value="Submit" style="display: block; margin: 0 auto;">
            </form>
        </div>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h4>Success!</h4>
            <p>Your stock in record has been successfully submitted.</p>
        </div>
    </div>
    <script>
        // Close success modal
        function closeModal() {
            document.getElementById("successModal").style.display = "none";
        }
    </script>
</body>
</html>
