<?php
include('db.php');

if (isset($_POST['stock_out_id'])) {
    $stock_out_id = $_POST['stock_out_id'];

    // Fetching relevant data for Stock Out from ledger table
    $query = "SELECT Item_Id, Item_Name, Balance, Voucher_No, Date_Out FROM ledger WHERE Stock_Out_Id = '$stock_out_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    // Check if a row is returned
    if ($row = mysqli_fetch_assoc($result)) {
        $data = array(
            'voucher_no' => $row['Voucher_No'],
            'date_out' => $row['Date_Out'],
            'item_id' => $row['Item_Id'],
            'item_name' => $row['Item_Name'],
            'available_qty' => $row['Balance'] // This should be the Available Qty (Balance)
        );
        echo json_encode($data);  // Sending data back to frontend
    } else {
        echo json_encode(['error' => 'No data found for Stock Out ID']);
    }
}
?>
