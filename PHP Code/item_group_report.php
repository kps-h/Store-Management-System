<?php
include('db.php');

// Fetch Item Groups from database
$itemGroups = [];
$sql = "SELECT Item_Group_Id, Item_Group_Name FROM item_group";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $itemGroups[] = $row;
    }
} else {
    echo "Error fetching data: " . mysqli_error($conn);
}

// Handle form submission to add new item group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $newId = htmlspecialchars($_POST['new_id']);
    $newName = htmlspecialchars($_POST['new_name']);
    
    if (!empty($newId) && !empty($newName)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO item_group (Item_Group_Id, Item_Group_Name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $newId, $newName);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect to avoid form resubmission
            exit();
        } else {
            echo "Error inserting data: " . mysqli_error($conn);
        }
    }
}

// Handle Export to CSV
if (isset($_GET['export_csv'])) {
    $reportId = $_GET['export_csv'];
    
    // Fetch data for the selected report
    $sql = "SELECT Item_Group_Id, Item_Group_Name FROM item_group WHERE Item_Group_Id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $reportId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="item_group_report.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Item Group Id', 'Item Group Name']); // CSV header
        fputcsv($output, [$row['Item_Group_Id'], $row['Item_Group_Name']]); // Report data
        
        fclose($output);
    }
    exit();
}

// Handle Export to PDF
if (isset($_GET['export_pdf'])) {
    require('fpdf.php');  // Include FPDF library for PDF generation
    
    $reportId = $_GET['export_pdf'];
    
    // Fetch data for the selected report
    $sql = "SELECT Item_Group_Id, Item_Group_Name FROM item_group WHERE Item_Group_Id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $reportId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        $pdf->Cell(40, 10, 'Item Group Id: ' . $row['Item_Group_Id']);
        $pdf->Ln();
        $pdf->Cell(40, 10, 'Item Group Name: ' . $row['Item_Group_Name']);
        
        $pdf->Output('D', 'item_group_report.pdf'); // 'D' forces download
    }
    exit();
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Group Master</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2>Item Group Master</h2>
    
    <!-- Add Item Group Form -->
    <div class="d-flex justify-content-between mb-2">
        <input type="text" class="form-control" id="filterInput" placeholder="Search by Item Group Id" style="width: 250px;">
        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addItemModal">Add Item Group</button>
    </div>

    <table class="table table-bordered" id="itemGroupTable">
        <thead>
            <tr>
                <th>Item Group Id</th>
                <th>Item Group Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($itemGroups as $group): ?>
                <tr>
                    <td><?php echo htmlspecialchars($group['Item_Group_Id']); ?></td>
                    <td><?php echo htmlspecialchars($group['Item_Group_Name']); ?></td>
                    <td>
                        <a href="?export_csv=<?php echo $group['Item_Group_Id']; ?>" class="btn btn-success">Export to CSV</a>
                        <a href="?export_pdf=<?php echo $group['Item_Group_Id']; ?>" class="btn btn-danger">Export to PDF</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Adding Item Group -->
<div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addItemModalLabel">Add Item Group</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="new_id">Item Group Id</label>
                        <input type="text" class="form-control" id="new_id" name="new_id" required>
                    </div>
                    <div class="form-group">
                        <label for="new_name">Item Group Name</label>
                        <input type="text" class="form-control" id="new_name" name="new_name" required>
                    </div>
                    <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>

<script>
    // Initialize DataTable
    $(document).ready(function() {
        $('#itemGroupTable').DataTable();
        
        // Filter functionality
        $('#filterInput').on('keyup', function() {
            var table = $('#itemGroupTable').DataTable();
            table.search(this.value).draw();
        });
    });
</script>

</body>
</html>
