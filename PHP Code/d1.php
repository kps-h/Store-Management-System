<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            height: 100vh;
            width: 250px;
            background-color: #343a40;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 20px;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
            margin: 5px 0;
            font-size: 16px;
        }

        .sidebar a:hover {
            background-color: #007bff;
        }

        .content {
            margin-left: 250px;
            padding: 30px;
        }

        .card {
            border-radius: 10px;
        }

        .chart-container {
            position: relative;
            height: 350px;
        }

        .navbar {
            background-color: #007bff;
            color: white;
        }

        .navbar a {
            color: white;
        }

        .table th, .table td {
            vertical-align: middle;
        }

        .dashboard-card {
            border-radius: 15px;
        }

        .footer {
            text-align: center;
            padding: 15px;
            background-color: #f8f9fa;
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 767px) {
            .sidebar {
                width: 100%;
                height: auto;
            }

            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h3 class="text-white text-center">Store Dashboard</h3>
    <a href="#">Dashboard</a>
    <a href="#">Sales</a>
    <a href="#">Inventory</a>
    <a href="#">Orders</a>
    <a href="#">Reports</a>
    <a href="#">Settings</a>
</div>

<!-- Main Content Area -->
<div class="content">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">Dashboard</span>
            <div class="d-flex">
                <a href="#" class="btn btn-light me-2">Notifications</a>
                <a href="#" class="btn btn-light">Profile</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Total Sales</h5>
                    <p class="card-text">$45,300</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Inventory Level</h5>
                    <p class="card-text">2,000 Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dashboard-card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">New Orders</h5>
                    <p class="card-text">35 Orders</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales and Inventory Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Sales Over Time</h5>
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Inventory Levels</h5>
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Recent Orders</h5>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>#1001</td>
                        <td>John Doe</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>$120.00</td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>#1002</td>
                        <td>Jane Smith</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                        <td>$250.00</td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>#1003</td>
                        <td>Mary Johnson</td>
                        <td><span class="badge bg-danger">Cancelled</span></td>
                        <td>$180.00</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2024 Store Management. All Rights Reserved.</p>
</div>

<!-- JavaScript for Charts -->
<script>
    // Sales Chart Data
    const salesData = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Sales ($)',
            data: [4500, 5300, 4600, 6000, 6700, 7200, 8000, 7500, 6500, 7000, 8000, 8500],
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 2,
            fill: false
        }]
    };

    const salesConfig = {
        type: 'line',
        data: salesData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true },
                y: { beginAtZero: true }
            }
        }
    };

    // Inventory Chart Data
    const inventoryData = {
        labels: ['Product A', 'Product B', 'Product C', 'Product D', 'Product E'],
        datasets: [{
            label: 'Inventory Levels',
            data: [150, 120, 200, 180, 250],
            backgroundColor: ['#FF5733', '#33FF57', '#3357FF', '#F3FF33', '#FF33A8'],
            borderWidth: 1
        }]
    };

    const inventoryConfig = {
        type: 'bar',
        data: inventoryData,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    };

    // Create Charts
    window.onload = function() {
        new Chart(document.getElementById('salesChart'), salesConfig);
        new Chart(document.getElementById('inventoryChart'), inventoryConfig);
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
