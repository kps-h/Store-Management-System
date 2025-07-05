<?php
include('db.php');

if (isset($_GET['item_id']) && isset($_GET['stock_in_id'])) {
    $item_id = $_GET['item_id'];
    $stock_in_id = $_GET['stock_in_id'];

    // Fetch Item Name and UOM for the selected Item ID
    $sql = "SELECT Item_Name, UOM FROM stock_in_items WHERE Stock_In_Id = ? AND Item_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $stock_in_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item_data = $result->fetch_assoc();

    // Send the data back as JSON
    echo json_encode([
        'item_name' => $item_data['Item_Name'],
        'uom' => $item_data['UOM']
    ]);
}

?>
