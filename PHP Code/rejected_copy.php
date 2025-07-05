<?php
// Connect to the database (update your credentials)
$host = 'localhost'; // Your host
$dbname = 'startup'; // Your database name
$username = 'root'; // Your username
$password = ''; // Your password

try {
    // Create a PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query to get the necessary data from stock_in_items and rejected_items tables
    $sql = "
        SELECT 
            sii.Item_Id, 
            sii.Item_Name, 
            si.Date_In, 
            SUM(sii.Qty) AS Total_Qty,
            IFNULL(SUM(ri.Rejected_Qty), 0) AS Rejected_Qty
        FROM 
            stock_in_items sii
        JOIN 
            stock_in si ON sii.Stock_In_Id = si.Stock_In_Id
        LEFT JOIN
            rejected_items ri ON sii.Item_Id = ri.Item_Id
        GROUP BY 
            sii.Item_Id, sii.Item_Name, si.Date_In
        ORDER BY 
            sii.Item_Id, si.Date_In
    ";

    // Prepare and execute the SQL statement
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // Initialize an array to hold the results
    $items = [];

    // Fetch results and store in array
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $items[] = $row;
    }

    // Function to calculate the total quantity for each item
    $total_qty = [];
    foreach ($items as $item) {
        if (!isset($total_qty[$item['Item_Id']])) {
            $total_qty[$item['Item_Id']] = 0;
        }
        $total_qty[$item['Item_Id']] += $item['Total_Qty'];
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    $items = []; // Ensure $items is defined even in case of an error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>

    <h2>Stock In Report</h2>

    <table>
        <thead>
            <tr>
                <th>Item Id</th>
                <th>Item Name</th>
                <th>Date In</th>
                <th>Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($items)) {
                // Display data
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item['Item_Id']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['Item_Name']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['Date_In']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['Total_Qty']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <h3>Total Quantity</h3>
    <table>
        <thead>
            <tr>
                <th>Item Id</th>
                <th>Total Qty</th>
                <th>Rej Qty</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($total_qty)) {
                // Display total quantity per item
                foreach ($total_qty as $item_id => $total) {
                    // Get the rejected quantity for the item
                    $rej_qty = 0;
                    foreach ($items as $item) {
                        if ($item['Item_Id'] === $item_id) {
                            $rej_qty = $item['Rejected_Qty'];
                            break;
                        }
                    }
                    $balance = $total - $rej_qty; // Balance = Total Qty - Rejected Qty

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($item_id) . "</td>";
                    echo "<td>" . htmlspecialchars($total) . "</td>";
                    echo "<td>" . htmlspecialchars($rej_qty) . "</td>";
                    echo "<td>" . htmlspecialchars($balance) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No total data available</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>
