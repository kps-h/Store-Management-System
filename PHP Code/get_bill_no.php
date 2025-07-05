<?php
require_once 'db.php';
if (isset($_GET['Stock_In_Id'])) {
    $Stock_In_Id = $_GET['Stock_In_Id'];
    $stmt = $conn->prepare("SELECT Bill_No FROM stock_in WHERE Stock_In_Id = ?");
    $stmt->bind_param("s", $Stock_In_Id);
    $stmt->execute();
    $stmt->bind_result($Bill_No);
    $stmt->fetch();
    echo $Bill_No;
    $stmt->close();
}
?>
