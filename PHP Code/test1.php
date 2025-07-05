<?php
include('db.php'); // Ensure database connection is established.

$sql = "SELECT Stock_Out_Id, Voucher_No, Date_Out, Total_Out, Remarks FROM stock_out";
$result = $conn->query($sql);

// Check if we have results.
if ($result->num_rows > 0) {
    echo "<div class='container'>";
    echo "<h1>Stock Out Records</h1>";
    echo "<table id='stock_out_table' class='display'>";
    echo "<thead><tr><th>Stock Out Id</th><th>Voucher No</th><th>Date Out</th><th>Total Out</th><th>Remarks</th></tr></thead>";
    echo "<tbody>";
    
    // Fetch and display each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><a href='stock_out_details.php?id=" . urlencode($row['Stock_Out_Id']) . "'>" . htmlspecialchars($row['Stock_Out_Id']) . "</a></td>";
        echo "<td>" . htmlspecialchars($row['Voucher_No']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Date_Out']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Total_Out']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Remarks']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    echo "</div>";
} else {
    echo "No stock out records found.";
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
    $('#stock_out_table').DataTable();
});
</script>
