<?php
require_once 'db.php';
if (isset($_GET['Stock_In_Id'])) {
    $Stock_In_Id = $_GET['Stock_In_Id'];
    $stmt = $conn->prepare("SELECT Item_Id, Item_Name FROM stock_in_items WHERE Stock_In_Id = ?");
    $stmt->bind_param("s", $Stock_In_Id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    $stmt->close();
}
?>
