<?php
// Fetch item details including balance from ledger based on Item_Id
if (isset($_POST['item_id'])) {
    $item_id = $_POST['item_id'];  // Get selected Item ID
    $stmt = $conn->prepare("SELECT Item_Name, Balance FROM ledger WHERE Item_Id = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("s", $item_id);
    $stmt->execute();
    $stmt->bind_result($item_name, $balance);
    $stmt->fetch();
    $stmt->close();

    // Return item details including balance as JSON response
    echo json_encode([
        'item_name' => $item_name,
        'balance' => $balance
    ]);
    exit();
}
?>
