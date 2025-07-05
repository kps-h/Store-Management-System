<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "startup";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data based on filter if set
$itemFilter = isset($_GET['item']) ? $_GET['item'] : '';

// Build query with optional filter
$query = "SELECT * FROM ledger";
if ($itemFilter) {
    $query .= " WHERE Item_Id = '$itemFilter'";
}

$query .= " ORDER BY Item_Id, Date_In, Date_Out";  // Order by Item_Id and Date to group and calculate cumulative balance

$result = $conn->query($query);
$ledgerData = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $ledgerData[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ledger Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
</head>
<body>
  <div class="container my-5">
    <h1 class="text-center mb-4">Ledger Report</h1>

    <!-- Filters -->
    <div class="mb-4">
      <label for="itemFilter" class="form-label">Filter by Item:</label>
      <form method="GET" action="">
        <select id="itemFilter" name="item" class="form-select" onchange="this.form.submit()">
          <option value="">All Items</option>
          <option value="CCOil001" <?= isset($_GET['item']) && $_GET['item'] == 'CCOil001' ? 'selected' : '' ?>>Coconut Oil</option>
          <option value="COil001" <?= isset($_GET['item']) && $_GET['item'] == 'COil001' ? 'selected' : '' ?>>Corn Oil</option>
          <!-- Add more items here -->
        </select>
      </form>
    </div>

    <!-- Table -->
    <table id="ledgerTable" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Item Id</th>
          <th>Item Name</th>
          <th>Date In</th>
          <th>Date Out</th>
          <th>Stock In Id</th>
          <th>Stock Out Id</th>
          <th>Bill No</th>
          <th>Voucher No</th>
          <th>In Qty</th>
          <th>Out Qty</th>
          <th>Cumulative Balance</th>
          <th>Date</th>
          <th>Rejected Id</th>
          <th>Qty</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $cumulativeBalance = 0; // Initialize the cumulative balance
        $currentItemId = ''; // Track the current Item Id to reset balance on change

        foreach ($ledgerData as $item):
          // If we encounter a new Item_Id, reset the cumulative balance
          if ($currentItemId != $item['Item_Id']) {
            $cumulativeBalance = 0;
            $currentItemId = $item['Item_Id'];  // Update current Item Id
          }

          // Update the cumulative balance for each row
          $cumulativeBalance += ($item['In_qty'] ?? 0) - ($item['Out_qty'] ?? 0);
        ?>
          <tr>
            <td><?= htmlspecialchars($item['Item_Id']) ?></td>
            <td><?= htmlspecialchars($item['Item_Name']) ?></td>
            <td><?= isset($item['Date_In']) && !empty($item['Date_In']) ? htmlspecialchars($item['Date_In']) : 'N/A' ?></td>
            <td><?= isset($item['Date_Out']) && !empty($item['Date_Out']) ? htmlspecialchars($item['Date_Out']) : 'N/A' ?></td>
            <td><?= htmlspecialchars($item['Stock_In_Id']) ?></td>
            <td><?= htmlspecialchars($item['Stock_Out_Id']) ?></td>
            <td><?= isset($item['Bill_No']) && !empty($item['Bill_No']) ? htmlspecialchars($item['Bill_No']) : 'N/A' ?></td>
            <td><?= isset($item['Voucher_No']) && !empty($item['Voucher_No']) ? htmlspecialchars($item['Voucher_No']) : 'N/A' ?></td>
            <td><?= isset($item['In_qty']) && $item['In_qty'] !== null ? htmlspecialchars($item['In_qty']) : '0' ?></td>
            <td><?= isset($item['Out_qty']) && $item['Out_qty'] !== null ? htmlspecialchars($item['Out_qty']) : '0' ?></td>
            <td><?= $cumulativeBalance ?></td> <!-- Display the cumulative balance -->
            <td><?= isset($item['Date']) && !empty($item['Date']) ? htmlspecialchars($item['Date']) : 'N/A' ?></td>
            <td><?= isset($item['Rejected_Id']) && !empty($item['Rejected_Id']) ? htmlspecialchars($item['Rejected_Id']) : 'N/A' ?></td>
            <td><?= isset($item['Qty']) && $item['Qty'] !== null ? htmlspecialchars($item['Qty']) : '0' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>

  <script>
    $(document).ready(function() {
      $('#ledgerTable').DataTable();
    });
  </script>
</body>
</html>
