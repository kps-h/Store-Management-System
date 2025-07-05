<?php
include('db.php'); // Ensure database connection is established.

$sql = "SELECT Stock_In_Id, Bill_No, Date_In, Total_In, Remarks FROM stock_in";
$result = $conn->query($sql);

// Check if we have results.
if ($result->num_rows > 0) {
    echo "<div class='container'>";
    echo "<h1>Stock In Records</h1>";
    echo "<table id='stock_in_table' class='display'>";
    echo "<thead><tr><th>Stock In Id</th><th>Bill No</th><th>Date In</th><th>Total In</th><th>Remarks</th></tr></thead>";
    echo "<tbody>";
    
    // Fetch and display each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><a href='stock_in_details.php?id=" . urlencode($row['Stock_In_Id']) . "'>" . htmlspecialchars($row['Stock_In_Id']) . "</a></td>";
        echo "<td>" . htmlspecialchars($row['Bill_No']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Date_In']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Total_In']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Remarks']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "No stock in records found.";
}

$conn->close();
?>
<!-- Link to DataTable CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">

<!-- Link to external CSS (your custom styles) -->
<link rel="stylesheet" type="text/css" href="new_style.css">

<!-- Include jQuery and DataTable JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#stock_in_table').DataTable();

});
</script>
