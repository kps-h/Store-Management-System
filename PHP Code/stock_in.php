<?php
// Include the external database connection
include('db.php');
//session_start();

// Generate Stock In ID
$stock_in_id = 'SID#';
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(Stock_In_Id, 5) AS UNSIGNED)) AS max_id FROM stock_in");
if ($row = $result->fetch_assoc()) {
    $next_id = (int)$row['max_id'] + 1;
    $stock_in_id .= str_pad($next_id, 3, '0', STR_PAD_LEFT); // Auto-generate ID with leading zeros
}

// Fetch vendors
$vendors = [];
$vendor_result = $conn->query("SELECT Vendor_Id, Vendor_Name FROM vendor");
while ($row = $vendor_result->fetch_assoc()) {
    $vendors[] = $row;
}

// Fetch items
$items = [];
$item_result = $conn->query("SELECT Item_Id, Item_Name, UOM FROM item");
while ($row = $item_result->fetch_assoc()) {
    $items[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['stock_in'])) {
        // Prepare and bind for the stock_in table
        $stmt = $conn->prepare("INSERT INTO stock_in (Stock_In_Id, Bill_No, Date_In, Vendor_Id, Vendor_Name, Total_In, Remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssis", $stock_in_id, $bill_no, $date_in, $vendor_id, $vendor_name, $total_in, $remarks);

        // Get the posted values
        $bill_no = $_POST['bill_no'];
        $date_in = date('Y-m-d'); // Use current date
        $vendor_id = $_POST['vendor_id'];
        $vendor_name = $_POST['vendor_name'];
        $total_in = $_POST['total_in'];
        $remarks = $_POST['remarks'];

        // Execute the statement for stock_in
        if ($stmt->execute()) {
            // Prepare for stock_in_items
            $stmt_items = $conn->prepare("INSERT INTO stock_in_items (Stock_In_Id, Item_Id, Item_Name, UOM, Qty) VALUES (?, ?, ?, ?, ?)");
            $stmt_items->bind_param("ssssi", $stock_in_id, $item_id, $item_name, $uom, $qty);

            // Prepare for ledger1
            $stmt_ledger = $conn->prepare("INSERT INTO ledger1 (Item_Id, Item_Name, Stock_In_Id, Date_In, Bill_No, in_qty) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_ledger->bind_param("sssssi", $item_id, $item_name, $stock_in_id, $date_in, $bill_no, $qty);

            // Loop through items and insert each one
            $items_inserted = 0; // Counter for inserted items
            foreach ($_POST['item_id'] as $index => $item_id) {
                $item_name = $_POST['item_name'][$index];
                $uom = $_POST['uom'][$index];
                $qty = $_POST['qty'][$index];

                // Execute statement for stock_in_items
                if ($stmt_items->execute()) {
                    // Execute statement for ledger1
                    $stmt_ledger->execute();
                    $items_inserted++;
                }
            }
            $stmt_items->close();
            $stmt_ledger->close();

            // Show the modal after successful form submission
            echo '<script>
                    window.onload = function() {
                        document.getElementById("successModal").style.display = "block";
                        setTimeout(function() {
                            window.location.href = "stock_in.php"; // Redirect to another page after 3 seconds
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
    <title>Stock Management</title>
    <!-- Include External CSS -->
    <link rel="stylesheet" href="styleS.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                <td><button type="button" class="btn-remove" onclick="removeItemRow(this)">Remove</button></td>
            `;
        }

         //Remove an item row
        function removeItemRow(button) {
            const row = button.closest('tr');
            row.remove();
            calculateTotalQty();
        }
		
        // Calculate the total quantity of all items
        function calculateTotalQty() {
            let totalQty = 0;
            const qtyInputs = document.querySelectorAll('input[name="qty[]"]');
            qtyInputs.forEach(input => {
                totalQty += parseInt(input.value) || 0;
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
	<!-- Modal for Success Message -->
<div id="successModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h3>Form Submitted Successfully!</h3>
       
    </div>
</div>
</body>
</html>
