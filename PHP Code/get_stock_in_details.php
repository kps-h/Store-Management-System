<?php
include('db.php');
if (isset($_GET['stock_in_id'])) {
    $stock_in_id = $_GET['stock_in_id'];
    $query = "SELECT Bill_No, Vendor_Id FROM stock_in WHERE Stock_In_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $stock_in_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    }
}
?>