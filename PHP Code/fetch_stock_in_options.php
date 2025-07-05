<?php
include('db.php'); // Your DB connection here

$query = "SELECT DISTINCT Stock_In_Id FROM ledger WHERE Stock_In_Id != ''";
$result = mysqli_query($conn, $query);

$options = '';
while ($row = mysqli_fetch_assoc($result)) {
    $options .= '<option value="' . $row['Stock_In_Id'] . '">' . $row['Stock_In_Id'] . '</option>';
}

echo $options;
?>
