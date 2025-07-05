<?php
// Include database connection settings and navigation bar
include('db.php');

// Fetching recipients for the table
$recipients = [];
try {
    $stmt = $conn->prepare("SELECT recipient_id, recipient_name, phone, email, address FROM recipient");
    $stmt->execute();

    // Use get_result() to fetch data and ensure proper result handling
    $result = $stmt->get_result();  // This is where the result set is obtained
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }

    // Free the result set after using it to prevent sync issues
    $result->free();
} catch (Exception $e) {
    echo "Error fetching recipients: " . $e->getMessage();
}

// Handle adding new recipient
$recipientAdded = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $newId = htmlspecialchars($_POST['new_id']);
    $newName = htmlspecialchars($_POST['new_name']);
    $newPhone = htmlspecialchars($_POST['new_phone']);
    $newEmail = htmlspecialchars($_POST['new_email']);
    $newAddress = htmlspecialchars($_POST['new_address']);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM recipient WHERE recipient_id = ?");
    $stmt->bind_param("s", $newId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->free_result(); // Free the result after using it to prevent sync issues

    if ($count > 0) {
        echo "<script>alert('Duplicated Recipient Id is not allowed');</script>";
    } elseif (!empty($newId) && !empty($newName) && !empty($newPhone) && !empty($newEmail) && !empty($newAddress)) {
        try {
            $stmt = $conn->prepare("INSERT INTO recipient (recipient_id, recipient_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $newId, $newName, $newPhone, $newEmail, $newAddress);
            $stmt->execute();
            $recipientAdded = true; // Set recipient added flag
        } catch (Exception $e) {
            echo "Error inserting data: " . $e->getMessage();
        }
    }
}

// Handle editing recipient
if (isset($_POST['edit'])) {
    $editId = htmlspecialchars($_POST['edit_id']);
    $editName = htmlspecialchars($_POST['edit_name']);
    $editPhone = htmlspecialchars($_POST['edit_phone']);
    $editEmail = htmlspecialchars($_POST['edit_email']);
    $editAddress = htmlspecialchars($_POST['edit_address']);

    try {
        $stmt = $conn->prepare("UPDATE recipient SET recipient_name = ?, phone = ?, email = ?, address = ? WHERE recipient_id = ?");
        $stmt->bind_param("sssss", $editName, $editPhone, $editEmail, $editAddress, $editId);
        $stmt->execute();
        echo "<script>alert('Recipient updated successfully');</script>";
    } catch (Exception $e) {
        echo "Error updating data: " . $e->getMessage();
    }
}

// Handle deleting recipient
if (isset($_POST['delete'])) {
    $deleteId = htmlspecialchars($_POST['delete_id']);
    try {
        $stmt = $conn->prepare("DELETE FROM recipient WHERE recipient_id = ?");
        $stmt->bind_param("s", $deleteId);
        $stmt->execute();
        echo "<script>alert('Recipient deleted successfully');</script>";
    } catch (Exception $e) {
        echo "Error deleting data: " . $e->getMessage();
    }
}
// Handle exporting CSV
if (isset($_GET['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=recipients.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Recipient Id', 'Recipient Name', 'Phone', 'Email', 'Address']); // Column headings

    foreach ($recipients as $recipient) {
        fputcsv($output, $recipient);
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
    <link rel="stylesheet" href="style.css"> <!-- External CSS file -->
    <title>Recipient Management</title>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Recipient Management</h2>
        </div>

        <div class="table-container">
            <div class="d-flex justify-content-between mb-2">
                <div>
                    <input type="text" id="filterInput" class="form-control" placeholder="Filter by Recipient Id" style="width: 200px; display: inline-block;">
                </div>
                <div>
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addRecipientModal">Add Recipient</button>
					<!-- CSV Export Button -->
                    <a href="?export_csv=1" class="btn btn-primary">Export CSV</a>
                    <!-- Print Button -->
                    <button type="button" class="btn btn-warning" id="printBtn">Print</button>
                </div>
            </div>

            <table id="recipientTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Recipient Id</th>
                        <th>Recipient Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipients as $recipient): ?>
                        <tr>
                            <td>
                                <a href="#" class="recipient-link" data-id="<?php echo htmlspecialchars($recipient['recipient_id']); ?>" data-name="<?php echo htmlspecialchars($recipient['recipient_name']); ?>" data-phone="<?php echo htmlspecialchars($recipient['phone']); ?>" data-email="<?php echo htmlspecialchars($recipient['email']); ?>" data-address="<?php echo htmlspecialchars($recipient['address']); ?>">
                                    <?php echo htmlspecialchars($recipient['recipient_id']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($recipient['recipient_name']); ?></td>
                            <td><?php echo htmlspecialchars($recipient['phone']); ?></td>
                            <td><?php echo htmlspecialchars($recipient['email']); ?></td>
                            <td><?php echo htmlspecialchars($recipient['address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for adding recipient -->
    <div class="modal fade" id="addRecipientModal" tabindex="-1" role="dialog" aria-labelledby="addRecipientModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRecipientModalLabel">Add Recipient</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="new_id">Recipient Id</label>
                            <input type="text" class="form-control" id="new_id" name="new_id" required>
                        </div>
                        <div class="form-group">
                            <label for="new_name">Recipient Name</label>
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
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for edit/delete actions -->
<div class="modal fade" id="actionRecipientModal" tabindex="-1" role="dialog" aria-labelledby="actionRecipientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="actionRecipientModalLabel">Recipient Actions</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="recipientActionMessage"></p>
                <button type="button" class="btn btn-primary" id="editRecipientButton">Edit</button>
                <button type="button" class="btn btn-danger" id="deleteRecipientButton">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for editing recipient -->
<div class="modal fade" id="editRecipientModal" tabindex="-1" role="dialog" aria-labelledby="editRecipientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRecipientModalLabel">Edit Recipient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editRecipientForm">
                    <input type="hidden" id="edit_id" name="edit_id">
                    <div class="form-group">
                        <label for="edit_name">Recipient Name</label>
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

<!-- Modal for deleting recipient -->
<div class="modal fade" id="deleteRecipientModal" tabindex="-1" role="dialog" aria-labelledby="deleteRecipientModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRecipientModalLabel">Delete Recipient</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this recipient?</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteRecipientForm">
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
            $('#recipientTable').DataTable();

            // Filter functionality
            $('#filterInput').on('keyup', function() {
                $('#recipientTable').DataTable().search(this.value).draw();
            });

            // Handle recipient link click
            $('.recipient-link').on('click', function(e) {
                e.preventDefault(); // Prevent default link behavior
                var id = $(this).data('id');
                var name = $(this).data('name');
                var phone = $(this).data('phone');
                var email = $(this).data('email');
                var address = $(this).data('address');

                // Set message and show modal
                $('#recipientActionMessage').text(`Actions for Recipient ID: ${id}`);
                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_phone').val(phone);
                $('#edit_email').val(email);
                $('#edit_address').val(address);
                $('#delete_id').val(id);
                
                $('#actionRecipientModal').modal('show');
            });

            // Edit recipient action
            $('#editRecipientButton').on('click', function() {
                $('#actionRecipientModal').modal('hide');
                $('#editRecipientModal').modal('show');
            });

            // Delete recipient action
            $('#deleteRecipientButton').on('click', function() {
                $('#actionRecipientModal').modal('hide');
                $('#deleteRecipientModal').modal('show');
            });

            // Alert for recipient added
            <?php if ($recipientAdded): ?>
                alert('Recipient Added');
            <?php endif; ?>
			
			// Print Button functionality
            $('#printBtn').on('click', function() {
                var printContent = `
                    <h2>Recipient List</h2>
                    <table class="table table-bordered" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th style="padding: 8px; border: 1px solid black; text-align: left;">Recipient Id</th>
                                <th style="padding: 8px; border: 1px solid black; text-align: left;">Recipient Name</th>
                                <th style="padding: 8px; border: 1px solid black; text-align: left;">Phone</th>
                                <th style="padding: 8px; border: 1px solid black; text-align: left;">Email</th>
                                <th style="padding: 8px; border: 1px solid black; text-align: left;">Address</th>
                            </tr>
                        </thead>
                        <tbody>`;

                // Loop through each table row and add it to the printable content
                $('#recipientTable tbody tr').each(function() {
                    var row = $(this);
                    var recipientId = row.find('td:eq(0)').text();
                    var recipientName = row.find('td:eq(1)').text();
                    var phone = row.find('td:eq(2)').text();
                    var email = row.find('td:eq(3)').text();
                    var address = row.find('td:eq(4)').text();
                    printContent += `<tr>
                        <td style="padding: 8px; border: 1px solid black;">${recipientId}</td>
                        <td style="padding: 8px; border: 1px solid black;">${recipientName}</td>
                        <td style="padding: 8px; border: 1px solid black;">${phone}</td>
                        <td style="padding: 8px; border: 1px solid black;">${email}</td>
                        <td style="padding: 8px; border: 1px solid black;">${address}</td>
                    </tr>`;
                });

                printContent += `</tbody></table>`;
                var printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write(printContent);
                printWindow.document.close();
                printWindow.print();
            });
			
        });
    </script>
</body>
</html>
