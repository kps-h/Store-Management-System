<?php
include('db.php');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Get filter parameters
$item_id = isset($_POST['item_id']) ? $_POST['item_id'] : '';
$item_name = isset($_POST['item_name']) ? $_POST['item_name'] : '';
$date = isset($_POST['date']) ? $_POST['date'] : '';

// Build the query with filters
$query = "SELECT * FROM ledger WHERE 1=1";
if ($item_id) $query .= " AND Item_Id = '$item_id'";
if ($item_name) $query .= " AND Item_Name LIKE '%$item_name%'";
if ($date) $query .= " AND (Date_In >= '$date' OR Date_Out <= '$date')";

$result = $conn->query($query);

// Fetch unique Item IDs and Names for dropdowns
$item_ids = $conn->query("SELECT DISTINCT Item_Id FROM ledger");
$item_names = $conn->query("SELECT DISTINCT Item_Name FROM ledger");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ledger Report</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS CDN -->
    <link href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.3.4/css/buttons.dataTables.min.css" rel="stylesheet">

    <style>
        /* Custom styles for buttons */
        .dataTables_length, .dataTables_filter, .dataTables_info {
            padding: 5px 15px;
        }
        .dt-button {
            margin-left: 5px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Ledger Report</h2>

    <!-- Filters Form -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <select name="item_id" class="form-control">
                    <option value="">Select Item ID</option>
                    <?php while ($row = $item_ids->fetch_assoc()) { ?>
                        <option value="<?php echo $row['Item_Id']; ?>" <?php echo ($item_id == $row['Item_Id']) ? 'selected' : ''; ?>>
                            <?php echo $row['Item_Id']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="item_name" class="form-control">
                    <option value="">Select Item Name</option>
                    <?php while ($row = $item_names->fetch_assoc()) { ?>
                        <option value="<?php echo $row['Item_Name']; ?>" <?php echo ($item_name == $row['Item_Name']) ? 'selected' : ''; ?>>
                            <?php echo $row['Item_Name']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </div>
    </form>

    <!-- Table -->
    <table id="ledgerTable" class="table table-bordered display nowrap">
        <thead>
            <tr>
                <th>Item ID</th>
                <th>Item Name</th>
                <th>Date</th>
                <th>Stock In ID</th>
                <th>Stock Out ID</th>
                <th>Bill No</th>
                <th>Voucher No</th>
                <th>In Qty</th>
                <th>Out Qty</th>
                <th>Qty</th>
                <th>Rejected ID</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_balance = 0;
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $balance = $row['In_qty'] - $row['Out_qty'] - $row['qty'];
                    $total_balance += $balance;
                    echo "<tr>
                        <td>{$row['Item_Id']}</td>
                        <td>{$row['Item_Name']}</td>
                        <td>{$row['Date_In']} {$row['Date_Out']}</td>
                        <td>{$row['Stock_In_Id']}</td>
                        <td>{$row['Stock_Out_Id']}</td>
                        <td>{$row['Bill_No']}</td>
                        <td>{$row['Voucher_No']}</td>
                        <td>{$row['In_qty']}</td>
                        <td>{$row['Out_qty']}</td>
                        <td>{$row['qty']}</td>
                        <td>{$row['rejected_id']}</td>
						<td>{$balance}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='12' class='text-center'>No records found</td></tr>";
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="10" class="text-end"><strong>Total Balance</strong></td>
                <td colspan="2"><?php echo $total_balance; ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Bootstrap, jQuery, DataTables, and Buttons JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.4/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfmake@0.1.53/build/pdfmake.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfmake@0.1.53/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.4/js/buttons.html5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#ledgerTable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copy', 'csv', 'excel', 'pdf'
            ]
        });
    });
</script>
</body>
</html>
