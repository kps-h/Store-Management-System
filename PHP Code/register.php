<?php
if (isLoggedIn()) {
    redirectToHome(); // If already logged in, redirect to home
}

$successMessage = ''; // Default: no success message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    if ($password == $confirm_password) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Check if username already exists
        $sql = "SELECT * FROM account WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) == 0) {
            // Insert new user into the account table
            $sql = "INSERT INTO account (username, password) VALUES ('$username', '$hashed_password')";
            if (mysqli_query($conn, $sql)) {
                // Set success message for the registration
                $successMessage = "Registered Successfully!";
                // Delay redirection handled by JS
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            echo "Username already exists!";
        }
    } else {
        echo "Passwords do not match!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Store Management</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
        }
        .jumbotron {
            background: #488AC7; /* Customize background color */
            color: white;
        }
        .form-container {
            max-width: 400px;
            width: 100%;
            margin-top: 50px;
            padding: 30px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-left: auto;
            margin-right: auto;
        }
        .btn-success {
            background-color: #488AC7;
            border-color: #488AC7;
        }
        footer {
            background-color: #f8f9fa;
            padding: 10px 0;
        }
        .alert-success {
            display: none;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white">
        <div class="container">
            <a class="navbar-brand" href="#">Store Management</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container text-center">
        <div class="form-container">
            <h2 class="text-center mb-4">Register</h2>

            <!-- Display Success Message -->
            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-success btn-block">Register</button>
            </form>
            <p class="mt-3">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <footer class="text-center mt-5 mb-3">
        <p>&copy; 2024 Store Management. All Rights Reserved.</p>
    </footer>

    <!-- JS and Redirect -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // If the success message is set, show it for 2 seconds and then redirect
        <?php if ($successMessage): ?>
            $(document).ready(function() {
                $('.alert-success').fadeIn().delay(2000).fadeOut(function() {
                    window.location.href = 'login.php'; // Redirect to login page after 2 seconds
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>
