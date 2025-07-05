<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "startup";  // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get item-wise details
$sql = "SELECT 
            Item_Name, 
            SUM(In_qty) AS Total_In_Qty, 
            SUM(Out_qty) AS Total_Out_Qty, 
            SUM(qty) AS Total_Qty, 
            (SUM(In_qty) - SUM(Out_qty) - SUM(qty)) AS Balance, 
            Item_Id
        FROM ledger
        GROUP BY Item_Name, Item_Id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data of each row
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td><a href='item_details.php?id=" . $row['Item_Id'] . "'>" . $row['Item_Name'] . "</a></td>
                <td>" . $row['Total_In_Qty'] . "</td>
                <td>" . $row['Total_Out_Qty'] . "</td>
                <td>" . $row['Total_Qty'] . "</td>
                <td>" . $row['Balance'] . "</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5' class='text-center'>No data found</td></tr>";
}

$conn->close();
?>
