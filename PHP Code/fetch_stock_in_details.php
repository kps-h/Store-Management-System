<?php
include('db.php');

if (isset($_POST['stock_in_id'])) {
    $stock_in_id = $_POST['stock_in_id'];

    // Fetching relevant data for Stock In from ledger table
    $query = "SELECT Item_Id, Item_Name, Balance, Bill_No, Date_In FROM ledger WHERE Stock_In_Id = '$stock_in_id' LIMIT 1";
    $result = mysqli_query($conn, $query);

    // Check if a row is returned
    if ($row = mysqli_fetch_assoc($result)) {
        $data = array(
            'bill_no' => $row['Bill_No'],
            'date_in' => $row['Date_In'],
            'item_id' => $row['Item_Id'],
            'item_name' => $row['Item_Name'],
            'available_qty' => $row['Balance'] // This should be the Available Qty (Balance)
        );
        echo json_encode($data);  // Sending data back to frontend
    } else {
        echo json_encode(['error' => 'No data found for Stock In ID']);
    }
}
?>
