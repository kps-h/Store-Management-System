<?php
include('db.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Fetch Stock_In_Id from stock_in table
$sql = "SELECT Stock_In_Id FROM stock_in";
$result = $conn->query($sql);

$stock_in_ids = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stock_in_ids[] = $row['Stock_In_Id'];
    }
}
// Get the last rejected_id from the database and increment it
$sql = "SELECT rejected_id FROM rejected ORDER BY rejected_id DESC LIMIT 1";
$result = $conn->query($sql);

// Initialize the next rejected_id
$next_rejected_id = 'RJD#001';  // Default value if no records exist
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // Extract the numeric part of the last rejected_id
    $last_rejected_id = $row['rejected_id'];
    $last_number = (int) substr($last_rejected_id, 4);  // Remove "RJD#" and get the number
    $next_number = $last_number + 1;
    $next_rejected_id = 'RJD#' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejection Form</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
            padding: 10px;
            border-radius: 15px 15px 0 0;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control {
            border-radius: 10px;
            height: 45px;
        }

        .form-select {
            border-radius: 10px;
            height: 45px;
        }

        .btn {
            border-radius: 25px;
            padding: 10px 20px;
        }

        .btn-primary {
            background-color: #007bff;
            border: none;
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .modal-backdrop {
            z-index: 1050;
        }

        .modal-content {
            border-radius: 10px;
        }

        .custom-row {
            margin-bottom: 15px;
        }

        /* Hover effect on table rows */
        #items_table tbody tr:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }

        /* Custom button for Add Item */
        #add_item_btn {
            border-radius: 25px;
            padding: 10px 25px;
            background-color: #28a745;
            color: white;
        }

        #add_item_btn:hover {
            background-color: #218838;
        }

        .text-muted {
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="text-center mb-4">Rejection Form</h2>
        <form action="submit_rejection.php" method="post">
            <!-- Display the Rejected ID -->
            <div class="row mb-3">
                <label for="rejected_id" class="col-sm-2 col-form-label">Rejected ID</label>
                <div class="col-sm-10">
                    <input type="text" id="rejected_id" name="rejected_id" class="form-control" value="<?= $next_rejected_id ?>" readonly required>
                </div>
            </div>
			<div class="row mb-3">
                <label for="date" class="col-sm-2 col-form-label">Date</label>
                <div class="col-sm-10">
                    <input type="date" id="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            
            <div class="row mb-3">
                <label for="stock_in_id" class="col-sm-2 col-form-label">Stock In ID</label>
                <div class="col-sm-10">
                    <select id="stock_in_id" name="stock_in_id" class="form-select" required>
                        <option value="">Select Stock In ID</option>
                        <?php foreach ($stock_in_ids as $stock_in_id): ?>
                            <option value="<?= $stock_in_id ?>"><?= $stock_in_id ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <label for="bill_no" class="col-sm-2 col-form-label">Bill No</label>
                <div class="col-sm-10">
                    <input type="text" id="bill_no" name="bill_no" class="form-control" readonly required>
                </div>
            </div>

            <div class="row mb-3">
                <label for="vendor_id" class="col-sm-2 col-form-label">Vendor ID</label>
                <div class="col-sm-10">
                    <input type="text" id="vendor_id" name="vendor_id" class="form-control" readonly required>
                </div>
            </div>

            <div class="row mb-3">
                <label for="vendor_name" class="col-sm-2 col-form-label">Vendor Name</label>
                <div class="col-sm-10">
                    <input type="text" id="vendor_name" name="vendor_name" class="form-control" readonly required>
                </div>
            </div>

            <table class="table" id="items_table">
                <thead>
                    <tr>
                        <th>Item ID</th>
                        <th>Item Name</th>
                        <th>UOM</th>
                        <th>Quantity Rejected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="items_body">
                    <!-- Dynamic rows will be added here -->
                </tbody>
            </table>

            <button type="button" class="btn btn-secondary" id="add_item_btn">Add Item</button>

            <!-- Total Rejected Quantity -->
            <div class="row mb-3">
                <label for="total_rejected_qty" class="col-sm-2 col-form-label">Total Rejected Quantity</label>
                <div class="col-sm-10">
                    <input type="number" id="total_rejected_qty" name="total_rejected_qty" class="form-control" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <label for="remarks" class="col-sm-2 col-form-label">Remarks</label>
                <div class="col-sm-10">
                    <textarea id="remarks" name="remarks" class="form-control"></textarea>
                </div>
            </div>

            <div class="row mb-3 text-center">
                <div class="col">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='fetch_i.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Bootstrap JS (optional, for interactivity) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery AJAX for dynamic updates -->
    <script>
        $(document).ready(function() {
    // Handle change event for Stock In ID
    $('#stock_in_id').change(function() {
        var stockInId = $(this).val();
        
        if (stockInId) {
            $.ajax({
                url: 'fetch_data.php',  // Endpoint to fetch Bill No, Vendor Info based on Stock In ID
                method: 'GET',
                data: { stock_in_id: stockInId },
                dataType: 'json',
                success: function(data) {
                    // Populate Bill No, Vendor ID, Vendor Name
                    $('#bill_no').val(data.bill_no);
                    $('#vendor_id').val(data.vendor_id);
                    $('#vendor_name').val(data.vendor_name);

                    // Populate the item ID dropdown dynamically
                    var items = data.items;
                    $('#item_id').empty().append('<option value="">Select Item ID</option>');
                    $.each(items, function(index, item) {
                        $('#item_id').append('<option value="'+ item.Item_Id +'">'+ item.Item_Id +'</option>');
                    });
                }
            });
        }
    });

    // Handle item selection for the dynamically added rows
    $(document).on('change', '.item_id', function() {
        var itemId = $(this).val();
        var row = $(this).closest('tr');
        var stockInId = $('#stock_in_id').val();  // Get Stock In ID

        if (itemId) {
            $.ajax({
                url: 'fetch_item_details.php',  // Fetch item details based on Item ID and Stock In ID
                method: 'GET',
                data: { item_id: itemId, stock_in_id: stockInId },
                dataType: 'json',
                success: function(data) {
                    row.find('.item_name').val(data.item_name);
                    row.find('.uom').val(data.uom);
                }
            });
        }
    });

    // Add functionality to add a row with Item ID, Item Name, and UOM dynamically
    $('#add_item_btn').click(function() {
        var stockInId = $('#stock_in_id').val();
        if (stockInId) {
            $.ajax({
                url: 'fetch_data.php',  // Endpoint to get Item Info
                method: 'GET',
                data: { stock_in_id: stockInId },
                dataType: 'json',
                success: function(data) {
                    // New row for item details
                    var newRow = `
                        <tr class="item_row">
                            <td>
                                <select name="item_id[]" class="form-select item_id" required>
                                    <option value="">Select Item ID</option>
                                    ${data.items.map(item => `<option value="${item.Item_Id}">${item.Item_Id}</option>`).join('')}
                                </select>
                            </td>
                            <td><input type="text" name="item_name[]" class="form-control item_name" readonly></td>
                            <td><input type="text" name="uom[]" class="form-control uom" readonly></td>
                            <td><input type="number" name="qty[]" class="form-control qty" required></td>
                            <td><button type="button" class="btn btn-danger remove_item">Remove</button></td>
                        </tr>
                    `;
                    $('#items_body').append(newRow);
                }
            });
        } else {
            alert("Please select a Stock In ID first.");
        }
    });

    // Function to update the Total Rejected Quantity
    $(document).on('input', '.qty', function() {
        updateTotalRejectedQuantity();
    });

    // Function to update Total Rejected Quantity when quantities are entered or removed
    function updateTotalRejectedQuantity() {
        var totalQty = 0;
        $('.qty').each(function() {
            totalQty += parseFloat($(this).val()) || 0;
        });
        $('#total_rejected_qty').val(totalQty);
    }

    // Remove row functionality
    $(document).on('click', '.remove_item', function() {
        $(this).closest('tr').remove();
        updateTotalRejectedQuantity();
    });
});
// Handle form submission success
            $('#rejection_form').submit(function(e) {
                e.preventDefault();
                $('#submit_btn').attr('disabled', true);

                // Show success modal
                $('#successModal').modal('show');
                setTimeout(function() {
                    $('#successModal').modal('hide');
                    $('#submit_btn').attr('disabled', false);
                }, 2000);
            });

    </script>
	<!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <h5>Form Submitted Successfully!</h5>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
