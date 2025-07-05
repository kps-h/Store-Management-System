<?php
include('db.php');
if (isset($_GET['stock_in_id'])) {
    $stock_in_id = $_GET['stock_in_id'];
    $query = "SELECT Item_Id, Item_Name, Available_Qty FROM stock_in_items WHERE Stock_In_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $stock_in_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = array();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
}
?>
