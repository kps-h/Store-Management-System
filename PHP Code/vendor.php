<?php
// Database connection settings from db.php
include('db.php'); // Includes the database connection from db.php

// Fetching vendors for the table
$vendors = [];
$query = "SELECT Vendor_Id, Vendor_Name, Phone, Email, Address FROM vendor";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($vendor = mysqli_fetch_assoc($result)) {
        $vendors[] = $vendor;
    }
} else {
    echo "Error fetching vendors: " . mysqli_error($conn);
}

// Handle adding new vendor
$vendorAdded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $newId = htmlspecialchars($_POST['new_id']);
    $newName = htmlspecialchars($_POST['new_name']);
    $newPhone = htmlspecialchars($_POST['new_phone']);
    $newEmail = htmlspecialchars($_POST['new_email']);
    $newAddress = htmlspecialchars($_POST['new_address']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM vendor WHERE Vendor_Id = ?");
    $stmt->bind_param("s", $newId); // Assuming Vendor_Id is a string
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count > 0) {
        echo "<script>alert('Duplicated Vendor Id is not allowed');</script>";
    } elseif (!empty($newId) && !empty($newName) && !empty($newPhone) && !empty($newEmail) && !empty($newAddress)) {
        $stmt = $conn->prepare("INSERT INTO vendor (Vendor_Id, Vendor_Name, Phone, Email, Address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $newId, $newName, $newPhone, $newEmail, $newAddress);
        if ($stmt->execute()) {
            $vendorAdded = true; // Set vendor added flag
        } else {
            echo "Error inserting data: " . mysqli_error($conn);
        }
    }
}

// Handle editing vendor
if (isset($_POST['edit'])) {
    $editId = htmlspecialchars($_POST['edit_id']);
    $editName = htmlspecialchars($_POST['edit_name']);
    $editPhone = htmlspecialchars($_POST['edit_phone']);
    $editEmail = htmlspecialchars($_POST['edit_email']);
    $editAddress = htmlspecialchars($_POST['edit_address']);

    $stmt = $conn->prepare("UPDATE vendor SET Vendor_Name = ?, Phone = ?, Email = ?, Address = ? WHERE Vendor_Id = ?");
    $stmt->bind_param("sssss", $editName, $editPhone, $editEmail, $editAddress, $editId);
    if ($stmt->execute()) {
        echo "<script>alert('Vendor updated successfully');</script>";
    } else {
        echo "Error updating data: " . mysqli_error($conn);
    }
}

// Handle deleting vendor
if (isset($_POST['delete'])) {
    $deleteId = htmlspecialchars($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM vendor WHERE Vendor_Id = ?");
    $stmt->bind_param("s", $deleteId);
    if ($stmt->execute()) {
        echo "<script>alert('Vendor deleted successfully');</script>";
    } else {
        echo "Error deleting data: " . mysqli_error($conn);
    }
}

// Handle CSV export
if (isset($_GET['export_csv'])) {
    // Set headers to indicate this is a CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=vendors.csv');
    
    // Open PHP output stream for writing the CSV data
    $output = fopen('php://output', 'w');
    
    // Write the header row of the CSV file
    fputcsv($output, ['Vendor Id', 'Vendor Name', 'Phone', 'Email', 'Address']);
    
    // Write each vendor data as a new row in the CSV file
    foreach ($vendors as $vendor) {
        fputcsv($output, $vendor);
    }
    
    // Close the output stream
    fclose($output);
    exit(); // Prevent further rendering of the page
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
    <!-- Link to external CSS -->
    <link rel="stylesheet" href="style.css">
    <title>Vendor Management</title>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Vendor Management</h2>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <input type="text" id="filterInput" class="form-control" placeholder="Filter by Vendor Id" style="width: 200px; display: inline-block;">
                </div>
                <div>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addVendorModal">Add Vendor</button>
					<a href="?export_csv=1" class="btn btn-primary">Export CSV</a>
                    <button type="button" class="btn btn-warning" id="printBtn">Print</button>
                </div>
            </div>

            <table id="vendorTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Vendor Id</th>
                        <th>Vendor Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td>
                                <a href="#" class="vendor-link" data-id="<?php echo htmlspecialchars($vendor['Vendor_Id']); ?>" data-name="<?php echo htmlspecialchars($vendor['Vendor_Name']); ?>" data-phone="<?php echo htmlspecialchars($vendor['Phone']); ?>" data-email="<?php echo htmlspecialchars($vendor['Email']); ?>" data-address="<?php echo htmlspecialchars($vendor['Address']); ?>">
                                    <?php echo htmlspecialchars($vendor['Vendor_Id']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($vendor['Vendor_Name']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['Phone']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['Email']); ?></td>
                            <td><?php echo htmlspecialchars($vendor['Address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for adding vendor -->
    <div class="modal fade" id="addVendorModal" tabindex="-1" role="dialog" aria-labelledby="addVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addVendorModalLabel">Add Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="new_id">Vendor Id</label>
                            <input type="text" class="form-control" id="new_id" name="new_id" required>
                        </div>
                        <div class="form-group">
                            <label for="new_name">Vendor Name</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                        </div>
                        <div class="form-group">
                            <label for="new_phone">Phone</label>
                            <input type="text" class="form-control" id="new_phone" name="new_phone" required>
                        </div>
                        <div class="form-group">
                            <label for="new_email">Email</label>
                            <input type="email" class="form-control" id="new_email" name="new_email" required>
                        </div>
                        <div class="form-group">
                            <label for="new_address">Address</label>
                            <textarea class="form-control" id="new_address" name="new_address" required></textarea>
                        </div>
                        <button type="submit" name="submit" class="btn btn-primary">Add Vendor</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for edit/delete actions -->
    <div class="modal fade" id="actionVendorModal" tabindex="-1" role="dialog" aria-labelledby="actionVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actionVendorModalLabel">Vendor Actions</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="vendorActionMessage"></p>
                    <button type="button" class="btn btn-primary" id="editVendorButton">Edit</button>
                    <button type="button" class="btn btn-danger" id="deleteVendorButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for editing vendor -->
    <div class="modal fade" id="editVendorModal" tabindex="-1" role="dialog" aria-labelledby="editVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editVendorModalLabel">Edit Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="editVendorForm">
                        <input type="hidden" id="edit_id" name="edit_id">
                        <div class="form-group">
                            <label for="edit_name">Vendor Name</label>
                            <input type="text" class="form-control" id="edit_name" name="edit_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_phone">Phone</label>
                            <input type="text" class="form-control" id="edit_phone" name="edit_phone" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="edit_email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_address">Address</label>
                            <textarea class="form-control" id="edit_address" name="edit_address" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="edit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for deleting vendor -->
    <div class="modal fade" id="deleteVendorModal" tabindex="-1" role="dialog" aria-labelledby="deleteVendorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteVendorModalLabel">Delete Vendor</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this vendor?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteVendorForm">
                        <input type="hidden" id="delete_id" name="delete_id">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete" class="btn btn-danger">Delete</button>
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
            $('#vendorTable').DataTable();

            // Filter functionality
            $('#filterInput').on('keyup', function() {
                $('#vendorTable').DataTable().search(this.value).draw();
            });

            // Handle vendor link click
            $('.vendor-link').on('click', function(e) {
                e.preventDefault(); // Prevent default link behavior
                var id = $(this).data('id');
                var name = $(this).data('name');
                var phone = $(this).data('phone');
                var email = $(this).data('email');
                var address = $(this).data('address');

                // Set message and show modal
                $('#vendorActionMessage').text(`Actions for Vendor ID: ${id}`);
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_phone').val(phone);
                $('#edit_email').val(email);
                $('#edit_address').val(address);
                $('#delete_id').val(id);
                
                $('#actionVendorModal').modal('show');
            });

            // Edit vendor action
            $('#editVendorButton').on('click', function() {
                $('#actionVendorModal').modal('hide');
                $('#editVendorModal').modal('show');
            });

            // Delete vendor action
            $('#deleteVendorButton').on('click', function() {
                $('#actionVendorModal').modal('hide');
                $('#deleteVendorModal').modal('show');
            });

            // Alert for vendor added
            <?php if ($vendorAdded): ?>
                alert('Vendor Added');
            <?php endif; ?>	
        });
	$('#printBtn').on('click', function() {
    // Get the table content
    var printContent = `
        <h2>Vendor List</h2>
        <table class="table table-bordered" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 8px; border: 1px solid black; text-align: left;">Vendor Id</th>
                    <th style="padding: 8px; border: 1px solid black; text-align: left;">Vendor Name</th>
                    <th style="padding: 8px; border: 1px solid black; text-align: left;">Phone</th>
                    <th style="padding: 8px; border: 1px solid black; text-align: left;">Email</th>
                    <th style="padding: 8px; border: 1px solid black; text-align: left;">Address</th>
                </tr>
            </thead>
            <tbody>`;
    
    // Loop through each table row and add it to the printable content
    $('#vendorTable tbody tr').each(function() {
        var row = $(this);
        var vendorId = row.find('td:eq(0)').text();
        var vendorName = row.find('td:eq(1)').text();
        var phone = row.find('td:eq(2)').text();
        var email = row.find('td:eq(3)').text();
        var address = row.find('td:eq(4)').text();
        
        printContent += `
            <tr>
                <td style="padding: 8px; border: 1px solid black;">${vendorId}</td>
                <td style="padding: 8px; border: 1px solid black;">${vendorName}</td>
                <td style="padding: 8px; border: 1px solid black;">${phone}</td>
                <td style="padding: 8px; border: 1px solid black;">${email}</td>
                <td style="padding: 8px; border: 1px solid black;">${address}</td>
            </tr>`;
    });
    
    printContent += `</tbody></table>`;

    // Open a new window for printing
    var printWindow = window.open('', '', 'height=600,width=800');
    printWindow.document.write('<html><head><title>Vendor List</title>');
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
    </script>
</body>
</html>
