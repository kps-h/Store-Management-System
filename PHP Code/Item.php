<?php
// Include database connection settings from db.php
include('db.php');

// Fetching item groups for dropdown
$itemGroups = [];
$sql = "SELECT Item_Group_Id, Item_Group_Name FROM item_group";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $itemGroups[] = $row;
    }
    mysqli_free_result($result); // Free the result set after fetching
} else {
    echo "Error fetching item groups: " . mysqli_error($conn);
}

// Fetching UOMs for dropdown
$uoms = [];
$sql = "SELECT UOM_Id, UOM_Name FROM uom";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $uoms[] = $row;
    }
    mysqli_free_result($result); // Free the result set after fetching
} else {
    echo "Error fetching UOMs: " . mysqli_error($conn);
}

// Fetching items for the table
$items = [];
$sql = "SELECT Item_Group_Id, Item_Id, Item_Name, UOM, Date FROM item";
$result = mysqli_query($conn, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    mysqli_free_result($result); // Free the result set after fetching
} else {
    echo "Error fetching items: " . mysqli_error($conn);
}

// Handle adding new UOM (AJAX)
$addedUOM = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_uom'])) {
    $newUOM = htmlspecialchars($_POST['new_uom']);
    
    if (!empty($newUOM)) {
        // Prepare and execute the UOM insertion query
        $stmt = mysqli_prepare($conn, "INSERT INTO uom (UOM_Name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $newUOM);
        
        if (mysqli_stmt_execute($stmt)) {
            $addedUOM = $newUOM; // Store the added UOM for pre-selection
            mysqli_stmt_close($stmt); // Close the prepared statement after execution
            
            // After successful UOM addition, refresh the UOM list
            // Fetch the updated UOMs
            $uoms = [];
            $sql = "SELECT UOM_Id, UOM_Name FROM uom";
            $result = mysqli_query($conn, $sql);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $uoms[] = $row;
                }
                mysqli_free_result($result);
            }

            // Return success response to AJAX
            echo json_encode(['status' => 'success', 'uoms' => $uoms]);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to add UOM']);
            exit();
        }
    }
}

// Handle adding new item
$itemAdded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $newGroupId = htmlspecialchars($_POST['new_group_id']);
    $newId = htmlspecialchars($_POST['new_id']);
    $newName = htmlspecialchars($_POST['new_name']);
    $newUOM = htmlspecialchars($_POST['new_uom']);
    $newDate = htmlspecialchars($_POST['new_date']);  // Get the Date from form input

    // Check if the Item Id already exists
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM item WHERE Item_Id = ?");
    mysqli_stmt_bind_param($stmt, "s", $newId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt); // Close the prepared statement after execution
    
    if ($count > 0) {
        echo "<script>alert('Duplicated Item Id is not allowed');</script>";
    } elseif (!empty($newGroupId) && !empty($newId) && !empty($newName) && !empty($newUOM) && !empty($newDate)) {
        // Prepare and execute the item insertion query
        $stmt = mysqli_prepare($conn, "INSERT INTO item (Item_Group_Id, Item_Id, Item_Name, UOM, Date) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $newGroupId, $newId, $newName, $newUOM, $newDate);
        
        if (mysqli_stmt_execute($stmt)) {
            $itemAdded = true; // Set item added flag
            mysqli_stmt_close($stmt); // Close the prepared statement after execution
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error inserting data: " . mysqli_error($conn);
        }
    }
}
if (isset($_GET['export_csv'])) {
    // Generate CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=items.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Item Group Id', 'Item Id', 'Item Name', 'UOM', 'Date']); // Column headings
    foreach ($items as $item) {
        fputcsv($output, $item);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css"> <!-- External stylesheet -->
    <title>Item Management</title>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Item Management</h2>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between mb-2">
                <div class="w-50">
                    <input type="text" id="filterInput" class="form-control" placeholder="Filter by Item Id" style="width: 50%; display: inline-block;">
                </div>
                <div class="w-50 text-right">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addItemModal">Add Item</button>
                    <!-- CSV Export Button -->
                    <a href="?export_csv=1" class="btn btn-primary">Export CSV</a>
                    <!-- Print Button -->
                    <button type="button" class="btn btn-warning" id="printBtn">Print</button>
                </div>
            </div>

            <div class="table-responsive">
                <table id="itemTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item Group Id</th>
                            <th>Item Id</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th>Date</th> <!-- Added Date Column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['Item_Group_Id']); ?></td>
                                <td><?php echo htmlspecialchars($item['Item_Id']); ?></td>
                                <td><?php echo htmlspecialchars($item['Item_Name']); ?></td>
                                <td><?php echo htmlspecialchars($item['UOM']); ?></td>
                                <td><?php echo htmlspecialchars($item['Date']); ?></td> <!-- Display Date -->
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for adding item -->
    <div class="modal fade" id="addItemModal" tabindex="-1" role="dialog" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Add Item</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" onsubmit="return validateItemId();">
                        <div class="form-group">
                            <label for="new_group_id">Item Group Id</label>
                            <select class="form-control" id="new_group_id" name="new_group_id" required>
                                <option value="">Select Item Group</option>
                                <?php foreach ($itemGroups as $group): ?>
                                    <option value="<?php echo $group['Item_Group_Id']; ?>"><?php echo $group['Item_Group_Name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
						<div class="form-group">
                            <label for="new_date">Date</label>
                            <input type="date" class="form-control" id="new_date" name="new_date" required>
                        </div>
                        <div class="form-group">
                            <label for="new_id">Item Id</label>
                            <input type="text" class="form-control" id="new_id" name="new_id" required>
                        </div>
                        <div class="form-group">
                            <label for="new_name">Item Name</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                        </div>
                        <div class="form-group">
                            <label for="new_uom">UOM</label>
                            <select class="form-control" id="new_uom" name="new_uom" required>
                                <option value="">Select UOM</option>
                                <?php foreach ($uoms as $uom): ?>
                                    <option value="<?php echo htmlspecialchars($uom['UOM_Name']); ?>" <?php echo ($uom['UOM_Name'] === $addedUOM) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($uom['UOM_Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-primary mt-2" data-toggle="modal" data-target="#addUOMModal">Add +</button>
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

    <!-- Modal for adding UOM -->
    <div class="modal fade" id="addUOMModal" tabindex="-1" role="dialog" aria-labelledby="addUOMModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUOMModalLabel">Add New UOM</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addUOMForm">
                        <div class="form-group">
                            <label for="new_uom">New UOM</label>
                            <input type="text" class="form-control" id="new_uom_input" name="new_uom" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#itemTable').DataTable();

            // Filter functionality
            $('#filterInput').on('keyup', function() {
                $('#itemTable').DataTable().search(this.value).draw();
            });

            // Initialize Select2 for Item Group and UOM dropdowns
            $('#new_group_id').select2({
                placeholder: "Select Item Group",
                allowClear: true
            });

            $('#new_uom').select2({
                placeholder: "Select UOM",
                allowClear: true
            });

            // Handle UOM form submission via AJAX
            $('#addUOMForm').submit(function(event) {
                event.preventDefault(); // Prevent default form submission
                var newUOM = $('#new_uom_input').val();

                if (newUOM) {
                    $.ajax({
                        url: '', // Submit to the same page
                        method: 'POST',
                        data: { 
                            add_uom: true,
                            new_uom: newUOM 
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                // Update UOM dropdown with the new UOM
                                var uomSelect = $('#new_uom');
                                uomSelect.empty(); // Clear existing options

                                // Add new UOMs to dropdown
                                response.uoms.forEach(function(uom) {
                                    uomSelect.append('<option value="' + uom.UOM_Name + '">' + uom.UOM_Name + '</option>');
                                });

                                // Select the newly added UOM
                                uomSelect.val(newUOM).trigger('change');

                                // Close the modal
                                $('#addUOMModal').modal('hide');
                            } else {
                                alert('Failed to add UOM: ' + response.message);
                            }
                        }
                    });
                }
            });
        });
	$('#printBtn').click(function() {
    var table = document.getElementById('itemTable');
    var newWindow = window.open('', '', 'height=500,width=800');
    newWindow.document.write('<html><head><title>Item List</title></head><body>');
    newWindow.document.write('<h2>Item Management</h2>');
    newWindow.document.write(table.outerHTML);  // Add the table HTML to the new window
    newWindow.document.write('</body></html>');
    newWindow.document.close();
    newWindow.print();
    });		
    </script>
</body>
</html>
