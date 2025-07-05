<?php
include('db.php');

if (isset($_GET['stock_in_id'])) {
    $stock_in_id = $_GET['stock_in_id'];

    // Fetch Bill No, Vendor Info based on Stock In ID
    $sql = "SELECT Bill_No, Vendor_Id, Vendor_Name FROM stock_in WHERE Stock_In_Id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $stock_in_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock_in_data = $result->fetch_assoc();

    // Fetch associated items for this Stock In ID
    $sql_items = "SELECT Item_Id FROM stock_in_items WHERE Stock_In_Id = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param('s', $stock_in_id);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }

    // Send the data back as JSON
    echo json_encode([
        'bill_no' => $stock_in_data['Bill_No'],
        'vendor_id' => $stock_in_data['Vendor_Id'],
        'vendor_name' => $stock_in_data['Vendor_Name'],
        'items' => $items
    ]);
}
?>
