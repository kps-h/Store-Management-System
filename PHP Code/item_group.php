<?php
// Include database connection settings from db.php
include('db.php');

// Fetching data from the database using mysqli
$itemGroups = [];
$sql = "SELECT Item_Group_Id, Item_Group_Name FROM item_group";
$result = mysqli_query($conn, $sql);

if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $itemGroups[] = $row;
    }
} else {
    echo "Error fetching data: " . mysqli_error($conn);
}

// Handle adding new item group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $newId = htmlspecialchars($_POST['new_id']);
    $newName = htmlspecialchars($_POST['new_name']);
    
    if (!empty($newId) && !empty($newName)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO item_group (Item_Group_Id, Item_Group_Name) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $newId, $newName); // "ss" means both parameters are strings

        if (mysqli_stmt_execute($stmt)) {
            header("Location: " . $_SERVER['PHP_SELF']); // Redirect to avoid form resubmission
            exit();
        } else {
            echo "Error inserting data: " . mysqli_error($conn);
        }
    }
}

// Handle CSV export
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=item_groups.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Item Group Id', 'Item Group Name']); // Column headings
    
    foreach ($itemGroups as $group) {
        fputcsv($output, $group);
    }
    fclose($output);
    exit();
}

// Close connection after processing
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="style.css"> <!-- Link to the external CSS file -->
    <title>Item Group Master</title>
</head>
<body>
    <!-- Content Area -->
    <div class="container">
        <div class="header">
            <h2>Item Group Master</h2>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <input type="text" id="filterInput" class="form-control" placeholder="Filter by Item Group Id" style="width: 200px; display: inline-block;">
                </div>
                <div>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addItemModal">Add Item Group</button>
                    <!-- CSV Export Button -->
                    <a href="?export_csv=1" class="btn btn-primary">Export CSV</a>
                    <!-- Print Button -->
                    <button type="button" class="btn btn-warning" id="printBtn">Print</button>
                </div>
            </div>

            <table id="itemGroupTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item Group Id</th>
                        <th>Item Group Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itemGroups as $group): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['Item_Group_Id']); ?></td>
                            <td><?php echo htmlspecialchars($group['Item_Group_Name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal for adding item group -->
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
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                        </div>
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
    $(document).ready(function() {
        // Initialize DataTable
        var table = $('#itemGroupTable').DataTable();

        // Filter functionality
        $('#filterInput').on('keyup', function() {
            table.search(this.value).draw();
        });

        // Print Button functionality
        $('#printBtn').on('click', function() {
            // Get only the table and heading content to print
            var printContent = `
                <h2>Item Groups List</h2>
                <table class="table table-bordered" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="padding: 8px; border: 1px solid black; text-align: left;">Item Group Id</th>
                            <th style="padding: 8px; border: 1px solid black; text-align: left;">Item Group Name</th>
                        </tr>
                    </thead>
                    <tbody>`;

            // Loop through each table row and add it to the printable content
            $('#itemGroupTable tbody tr').each(function() {
                var row = $(this);
                var itemId = row.find('td:eq(0)').text();
                var itemName = row.find('td:eq(1)').text();
                printContent += `<tr>
                    <td style="padding: 8px; border: 1px solid black;">${itemId}</td>
                    <td style="padding: 8px; border: 1px solid black;">${itemName}</td>
                </tr>`;
            });

            printContent += `</tbody></table>`;

            // Open a new window for printing
            var printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Item Group Report</title>');
            // Add custom CSS for print styling
            printWindow.document.write(`
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { padding: 8px; border: 1px solid black; text-align: left; }
                </style>
            `);
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
		 });
    });
    </script>
</body>
</html>
