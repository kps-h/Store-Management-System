<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejection Form</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>
<style>
/* General Body Styling */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f7fc;
    margin: 0;
    padding: 0;
}

/* Form Container Styling */
.form-container {
    background-color: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    width: 80%;
    max-width: 800px;
    margin: 50px auto;
    padding: 30px;
    box-sizing: border-box;
}

/* Header Styling */
h2 {
    text-align: center;
    font-size: 24px;
    margin-bottom: 30px;
    color: #333;
}

/* Label and Input Styling */
label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
    color: #333;
}

input[type="text"],
input[type="date"],
input[type="number"],
select {
    width: 100%;
    padding: 10px;
    margin: 5px 0 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
}

input[readonly] {
    background-color: #f0f0f0;
}

select {
    padding: 10px;
    font-size: 14px;
    color: #555;
}

/* Section Styling */
.stock_section {
    margin-bottom: 20px;
}

.item-details {
    margin-top: 30px;
}

.item-details table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.item-details th,
.item-details td {
    padding: 10px;
    text-align: center;
    border: 1px solid #ddd;
}

.item-details th {
    background-color: #f4f7fc;
    font-weight: bold;
}

.item-details td {
    font-size: 14px;
}

/* Form Actions Styling */
.form-actions {
    text-align: center;
    margin-top: 20px;
}
/* Add button should align to the left */
.form-actions #add-btn {
    background-color: #5bc0de; /* Add color for Add button */
    color: #fff;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s ease;
    width: 100%;
    text-align: center;
}
.form-actions #add-btn:hover {
    background-color: #31b0d5;
}
/* Style for Cancel and Submit buttons (side by side) */
.form-actions .side-by-side {
    display: flex;
    justify-content: center;
    gap: 20px;
}
/* Cancel and Submit Buttons */
.form-actions .side-by-side button {
    background-color: #d9534f;
    color: white;
    border: none;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    border-radius: 5px;
    width: 100%;
    max-width: 150px;
    transition: background-color 0.3s ease;
}
.form-actions .side-by-side button[type="button"] {
    background-color: #d9534f;
}
.form-actions .side-by-side button[type="button"]:hover {
    background-color: #c9302c;
}
.form-actions .side-by-side button[type="submit"] {
    background-color: #5cb85c;
}
.form-actions .side-by-side button[type="submit"]:hover {
    background-color: #4cae4c;
}
/* Total Remaining Quantity Section */
#total_remaining_qty {
    font-size: 18px;
    font-weight: bold;
    color: #5bc0de;
    margin-top: 20px;
    text-align: center;
}

</style>
<body>
    <div class="form-container">
        <h2>Rejection Form</h2>
        <form id="rejection-form" action="submit_rejection.php" method="POST">
            <div>
                <label for="stock_type">Select Stock Type:</label>
                <select id="stock_type" name="stock_type" required>
                    <option value="stock_in">Stock In</option>
                    <option value="stock_out">Stock Out</option>
                </select>
            </div>

            <div id="stock_in_section" class="stock_section">
                <label for="stock_in_id">Stock In Id:</label>
                <select id="stock_in_id" name="stock_in_id" required>
                    <option value="">Select Stock In</option>
                    <!-- Options will be populated dynamically -->
                </select>

                <label for="bill_no">Bill No:</label>
                <input type="text" id="bill_no" name="bill_no" readonly>

                <label for="date_in">Date In:</label>
                <input type="date" id="date_in" name="date_in" readonly>

                <label for="item_id_in">Item Id:</label>
                <input type="text" id="item_id_in" name="item_id_in" readonly>

                <label for="item_name_in">Item Name:</label>
                <input type="text" id="item_name_in" name="item_name_in" readonly>

                <label for="available_qty_in">Available Qty:</label>
                <input type="number" id="available_qty_in" name="available_qty_in" readonly>
            </div>

            <div id="stock_out_section" class="stock_section" style="display: none;">
                <label for="stock_out_id">Stock Out Id:</label>
                <select id="stock_out_id" name="stock_out_id" required>
                    <option value="">Select Stock Out</option>
                    <!-- Options will be populated dynamically -->
                </select>

                <label for="voucher_no">Voucher No:</label>
                <input type="text" id="voucher_no" name="voucher_no" readonly>

                <label for="date_out">Date Out:</label>
                <input type="date" id="date_out" name="date_out" readonly>

                <label for="item_id_out">Item Id:</label>
                <input type="text" id="item_id_out" name="item_id_out" readonly>

                <label for="item_name_out">Item Name:</label>
                <input type="text" id="item_name_out" name="item_name_out" readonly>

                <label for="available_qty_out">Available Qty:</label>
                <input type="number" id="available_qty_out" name="available_qty_out" readonly>
            </div>

            <div class="item-details">
    <label for="rejected_qty">Rejected Qty:</label>
    <input type="number" id="rejected_qty" name="rejected_qty" required oninput="updateRemainingQty()">

    <label for="remaining_qty">Remaining Qty:</label>
    <input type="number" id="remaining_qty" name="remaining_qty" readonly>

    <div id="remaining_qty_list">
        <table id="remaining_qty_table">
            <tr>
                <th>Item Id</th>
                <th>Item Name</th>
                <th>Available Qty</th>
                <th>Rejected Qty</th>
                <th>Remaining Qty</th>
            </tr>
        </table>
    </div>
</div>

<!-- Add+ Button Below the Table -->
<div class="form-actions">
    <button type="button" id="add-btn" onclick="addRow()">Add+</button>
</div>

<div>
<h3>Total Remaining Qty: <span id="total_remaining_qty">0</span></h3>
</div>
		
<!-- Cancel and Submit Buttons Side by Side -->
<div class="form-actions">
    <div class="side-by-side">
        <button type="button" onclick="cancelForm()">Cancel</button>
        <button type="submit" onclick="submitForm()">Submit</button>
    </div>
</div>
        </form>   
    </div>

    <script>
        $(document).ready(function() {
            // Initial fetch of Stock In and Stock Out options
            loadStockInOptions();
            loadStockOutOptions();

            // Event listener for Stock Type selection
            $('#stock_type').change(function() {
                if ($(this).val() === 'stock_in') {
                    $('#stock_in_section').show();
                    $('#stock_out_section').hide();
                } else {
                    $('#stock_in_section').hide();
                    $('#stock_out_section').show();
                }
            });

            // Event listener for Stock In selection
            $('#stock_in_id').change(function() {
                var stock_in_id = $(this).val();
                if (stock_in_id) {
                    fetchStockInDetails(stock_in_id);
                }
            });

            // Event listener for Stock Out selection
            $('#stock_out_id').change(function() {
                var stock_out_id = $(this).val();
                if (stock_out_id) {
                    fetchStockOutDetails(stock_out_id);
                }
            });
        });

        function loadStockInOptions() {
            $.ajax({
                url: 'fetch_stock_in_options.php',
                type: 'GET',
                success: function(response) {
                    $('#stock_in_id').html(response);
                }
            });
        }

        function loadStockOutOptions() {
            $.ajax({
                url: 'fetch_stock_out_options.php',
                type: 'GET',
                success: function(response) {
                    $('#stock_out_id').html(response);
                }
            });
        }

        function fetchStockInDetails(stock_in_id) {
        $.ajax({
        url: 'fetch_stock_in_details.php',
        type: 'POST',
        data: { stock_in_id: stock_in_id },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.error) {
                alert(data.error);  // Show an error if no data is found
            } else {
                $('#bill_no').val(data.bill_no);
                $('#date_in').val(data.date_in);
                $('#item_id_in').val(data.item_id);
                $('#item_name_in').val(data.item_name);
                $('#available_qty_in').val(data.available_qty);  // Setting Available Qty (Balance)
            }
        }
    });
}

function fetchStockOutDetails(stock_out_id) {
    $.ajax({
        url: 'fetch_stock_out_details.php',
        type: 'POST',
        data: { stock_out_id: stock_out_id },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.error) {
                alert(data.error);  // Show an error if no data is found
            } else {
                $('#voucher_no').val(data.voucher_no);
                $('#date_out').val(data.date_out);
                $('#item_id_out').val(data.item_id);
                $('#item_name_out').val(data.item_name);
                $('#available_qty_out').val(data.available_qty);  // Setting Available Qty (Balance)
            }
        }
    });
}


        function updateRemainingQty() {
            var available_qty = 0;
            var rejected_qty = $('#rejected_qty').val();

            if ($('#stock_type').val() === 'stock_in') {
                available_qty = $('#available_qty_in').val();
            } else {
                available_qty = $('#available_qty_out').val();
            }

            var remaining_qty = available_qty - rejected_qty;
            $('#remaining_qty').val(remaining_qty);

            updateTotalRemainingQty();
        }

        function updateTotalRemainingQty() {
            var total_remaining_qty = 0;

            $('#remaining_qty_table tr').each(function() {
                var row_remaining_qty = $(this).find('td:last').text();
                total_remaining_qty += parseInt(row_remaining_qty || 0);
            });

            $('#total_remaining_qty').text(total_remaining_qty);
        }

        function addRow() {
            var item_id = $('#item_id_in').val() || $('#item_id_out').val();
            var item_name = $('#item_name_in').val() || $('#item_name_out').val();
            var available_qty = $('#available_qty_in').val() || $('#available_qty_out').val();
            var rejected_qty = $('#rejected_qty').val();
            var remaining_qty = $('#remaining_qty').val();

            if (item_id && rejected_qty && remaining_qty) {
                var row = `<tr>
                    <td>${item_id}</td>
                    <td>${item_name}</td>
                    <td>${available_qty}</td>
                    <td>${rejected_qty}</td>
                    <td>${remaining_qty}</td>
                </tr>`;
                $('#remaining_qty_table').append(row);
                updateTotalRemainingQty();
            }
        }

        function cancelForm() {
            $('#rejection-form')[0].reset();
            $('#total_remaining_qty').text('0');
        }

        function submitForm() {
    var formData = {
        stock_type: $('#stock_type').val(),
        stock_in_id: $('#stock_in_id').val(),  // Always send stock_in_id
        stock_out_id: $('#stock_out_id').val(),  // Always send stock_out_id
        rejected_qty: $('#rejected_qty').val(),
        remaining_qty: $('#remaining_qty').val(),
    };

    // Log form data for debugging purposes
    console.log(formData);

    $.ajax({
        url: 'submit_rejection.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            alert(response);
            cancelForm();  // Reset the form after successful submission
        },
        error: function(xhr, status, error) {
            alert("An error occurred: " + error);
        }
    });
}
    </script>
</body>
</html>
	