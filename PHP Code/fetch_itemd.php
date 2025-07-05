<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "startup");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the Stock In Id and Item Id from the query string
$stockInId = $_GET['stockInId'];
$itemId = $_GET['itemId'];

// Fetch item details based on Stock In Id and Item Id
$sql = "SELECT item_name, uom FROM items WHERE stock_in_id = '$stockInId' AND item_id = '$itemId'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'itemName' => $row['item_name'],
        'uom' => $row['uom']
    ]);
} else {
    echo json_encode([]);
}

$conn->close();
?>
