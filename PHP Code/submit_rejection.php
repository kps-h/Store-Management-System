<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "startup"; // Replace with your actual database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Get form data
$stock_type = $_POST['stock_type'];
$stock_in_id = $stock_type == 'stock_in' ? $_POST['stock_in_id'] : null;
$stock_out_id = $stock_type == 'stock_out' ? $_POST['stock_out_id'] : null;
$bill_no = $stock_type == 'stock_in' ? $_POST['bill_no'] : null;
$voucher_no = $stock_type == 'stock_out' ? $_POST['voucher_no'] : null;
$date_in = $stock_type == 'stock_in' ? $_POST['date_in'] : null;
$date_out = $stock_type == 'stock_out' ? $_POST['date_out'] : null;
$item_id = $stock_type == 'stock_in' ? $_POST['item_id_in'] : $_POST['item_id_out'];
$item_name = $stock_type == 'stock_in' ? $_POST['item_name_in'] : $_POST['item_name_out'];
$available_qty = $stock_type == 'stock_in' ? $_POST['available_qty_in'] : $_POST['available_qty_out'];
$rejected_qty = $_POST['rejected_qty'];
$remaining_qty = $_POST['remaining_qty'];

// Prepare SQL insert statement
$sql = "INSERT INTO rejection_form_data (stock_type, stock_in_id, stock_out_id, bill_no, voucher_no, date_in, date_out, item_id, item_name, available_qty, rejected_qty, remaining_qty)
        VALUES ('$stock_type', '$stock_in_id', '$stock_out_id', '$bill_no', '$voucher_no', '$date_in', '$date_out', '$item_id', '$item_name', '$available_qty', '$rejected_qty', '$remaining_qty')";

// Execute the query
if (mysqli_query($conn, $sql)) {
    echo "Rejection form submitted successfully!";
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}

// Close the connection
mysqli_close($conn);
?>
