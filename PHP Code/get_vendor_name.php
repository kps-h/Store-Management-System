<?php
include('db.php');
if (isset($_GET['vendor_id'])) {
    $vendor_id = $_GET['vendor_id'];
    $query = "SELECT Vendor_Name FROM vendor WHERE Vendor_Id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $vendor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo $row['Vendor_Name'];
    }
}
?>