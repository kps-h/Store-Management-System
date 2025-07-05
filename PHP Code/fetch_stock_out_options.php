<?php
include('db.php');

$query = "SELECT DISTINCT Stock_Out_Id FROM ledger WHERE Stock_Out_Id != ''";
$result = mysqli_query($conn, $query);

$options = '';
while ($row = mysqli_fetch_assoc($result)) {
    $options .= '<option value="' . $row['Stock_Out_Id'] . '">' . $row['Stock_Out_Id'] . '</option>';
}

echo $options;
?>
