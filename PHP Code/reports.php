<?php
// Sample data (you can replace this with actual data fetched from a database)
$reports = [
    ['id' => 1, 'title' => 'Item Group Report', 'type' => 'Item Group', 'date' => '2024-10-01', 'file' => 'item_group_report.pdf'],
    ['id' => 2, 'title' => 'Item Report', 'type' => 'Item', 'date' => '2024-10-05', 'file' => 'item_report.pdf'],
    ['id' => 3, 'title' => 'Vendor Report', 'type' => 'Vendor', 'date' => '2024-09-28', 'file' => 'vendor_report.pdf'],
    ['id' => 4, 'title' => 'Recipient Report', 'type' => 'Recipient', 'date' => '2024-09-25', 'file' => 'recipient_report.pdf'],
    ['id' => 5, 'title' => 'Stock In Report', 'type' => 'Stock In', 'date' => '2024-10-03', 'file' => 'stock_in_report.pdf'],
    ['id' => 6, 'title' => 'Stock Out Report', 'type' => 'Stock Out', 'date' => '2024-10-07', 'file' => 'stock_out_report.pdf'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Global Styles */
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            padding-top: 40px;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 90%;
            max-width: 1000px;
            margin-bottom: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
            font-size: 28px;
            margin-bottom: 40px;
        }

        /* Search and Filter */
        #search {
            padding: 10px;
            font-size: 14px;
            width: 40%;
            margin-bottom: 20px;
            margin-right: 10px;
            display: inline-block;
        }

        .filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 20px;
        }

        .filters select, .filters input[type="date"] {
            padding: 10px;
            font-size: 14px;
            width: 30%;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
            font-weight: 500;
            cursor: pointer;
            text-align: center;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        td {
            text-align: center;
        }

        .action-btn {
            background-color: #007BFF;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 5px;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 16px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }

        .pagination button:hover {
            background-color: #0056b3;
        }

        /* Modal Preview */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            max-width: 600px;
            border-radius: 8px;
            width: 80%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close-btn {
            background-color: red;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            float: right;
            border: none;
        }

        /* Recent Activity Log */
        .activity-log {
            margin-top: 30px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .activity-log h3 {
            margin-top: 0;
            font-size: 18px;
            color: #333;
        }

        .activity-log p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 24px;
            }

            #search {
                width: 100%;
                margin-bottom: 15px;
            }

            .filters {
                flex-direction: column;
                gap: 15px;
            }

            .filters select, .filters input[type="date"] {
                width: 100%;
            }

            .pagination button {
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>User Reports</h1>

    <!-- Search Box -->
    <input type="text" id="search" placeholder="Search by report title..." onkeyup="searchReports()">

    <!-- Filters Section -->
    <div class="filters">
        <select id="typeFilter">
            <option value="">Filter by Type</option>
            <option value="Item Group">Item Group</option>
            <option value="Item">Item</option>
            <option value="Vendor">Vendor</option>
            <option value="Recipient">Recipient</option>
            <option value="Stock In">Stock In</option>
            <option value="Stock Out">Stock Out</option>
        </select>

        <input type="date" id="startDate" onchange="filterByDate()">
        <input type="date" id="endDate" onchange="filterByDate()">
    </div>

    <!-- Reports Table -->
    <table id="reportsTable">
        <thead>
            <tr>
                <th onclick="sortTable(0)">Report Title &#x2195;</th>
                <th onclick="sortTable(1)">Report Type &#x2195;</th>
                <th onclick="sortTable(2)">Date &#x2195;</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?php echo htmlspecialchars($report['title']); ?></td>
                    <td><?php echo htmlspecialchars($report['type']); ?></td>
                    <td><?php echo htmlspecialchars($report['date']); ?></td>
                    <td>
                        <a href="export_csv.php?id=<?php echo $report['id']; ?>" class="action-btn">Export to CSV</a>
                        <a href="export_pdf.php?id=<?php echo $report['id']; ?>" class="action-btn">Export to PDF</a>
                        <button class="action-btn" onclick="printReport(<?php echo $report['id']; ?>)">Print</button>
                        <button class="action-btn" onclick="previewReport(<?php echo $report['id']; ?>)">Preview</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination Controls -->
    <div class="pagination">
        <button>Previous</button>
        <button>1</button>
        <button>2</button>
        <button>Next</button>
    </div>

    <!-- Recent Activity Log -->
    <div class="activity-log">
        <h3>Recent Activity:</h3>
        <p>Vendor Report - Downloaded on 2024-10-05</p>
        <p>Stock In Report - Downloaded on 2024-09-30</p>
    </div>

    <!-- Modal for Preview -->
    <div id="previewModal" class="modal">
        <div class="modal-content">
            <button class="close-btn" onclick="closePreview()">Close</button>
            <h2>Report Preview</h2>
            <p>This is a preview of the report. The full content can be downloaded or printed.</p>
        </div>
    </div>
</div>

<script>
    // Search function
    function searchReports() {
        const searchValue = document.getElementById('search').value.toLowerCase();
        const rows = document.getElementById('reportsTable').getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const title = rows[i].getElementsByTagName('td')[0].textContent.toLowerCase();
            if (title.indexOf(searchValue) === -1) {
                rows[i].style.display = 'none';
            } else {
                rows[i].style.display = '';
            }
        }
    }

    // Sorting function
    function sortTable(columnIndex) {
        const table = document.getElementById('reportsTable');
        const rows = Array.from(table.rows).slice(1); // Skip the header row
        const sortedRows = rows.sort((rowA, rowB) => {
            const cellA = rowA.cells[columnIndex].textContent.trim();
            const cellB = rowB.cells[columnIndex].textContent.trim();
            return cellA.localeCompare(cellB);
        });
        sortedRows.forEach(row => table.appendChild(row));
    }

    // Filter by date range
    function filterByDate() {
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const rows = document.getElementById('reportsTable').getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const date = rows[i].getElementsByTagName('td')[2].textContent.trim();
            if ((startDate && date < startDate) || (endDate && date > endDate)) {
                rows[i].style.display = 'none';
            } else {
                rows[i].style.display = '';
            }
        }
    }

    // Print Report
    function printReport(reportId) {
        alert('Printing report with ID: ' + reportId);
    }

    // Preview Report
    function previewReport(reportId) {
        document.getElementById('previewModal').style.display = 'flex';
    }

    function closePreview() {
        document.getElementById('previewModal').style.display = 'none';
    }
</script>

</body>
</html>
