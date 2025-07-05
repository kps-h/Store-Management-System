<?php
// Database credentials
$servername = "localhost";
$username = "root";  // Use your database username
$password = "";      // Use your database password
$dbname = "startup";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the Stock In Id from the GET request
$stockInId = $_GET['stockInId'];

// Fetch the items associated with the selected Stock In Id
$sql = "SELECT Item_Id, Item_Name FROM stock_in_items WHERE Stock_In_Id = '$stockInId'";
$result = $conn->query($sql);

// Prepare an array to store the response
$response = [];

if ($result->num_rows > 0) {
    // Fetch and return the items
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            'Item_Id' => $row['Item_Id'],
            'Item_Name' => $row['Item_Name']
        ];
    }
} else {
    $response['error'] = 'No items found for the selected Stock In Id';
}

// Return the response as JSON
echo json_encode($response);

// Close the database connection
$conn->close();
?>
