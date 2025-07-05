<?php
// Include the database connection file
include('db.php'); // Assuming your database connection code is saved in db_connection.php

// Start a session to keep track of user login status
session_start();

// Check if the login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect the submitted username and password
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Query to check if the user exists with the given username and password
    $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1";
    $result = $conn->query($sql);

    // If the user exists, start a session and redirect to a dashboard or home page
    if ($result->num_rows > 0) {
        // Fetch the user data
        $user = $result->fetch_assoc();

        // Set session variables (you can store more information if needed)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Redirect to a dashboard or home page
        header('Location: index.php');
        exit();
    } else {
        // If the user doesn't exist or password doesn't match
        $error_message = "Invalid username or password!";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full height of the viewport */
        }

        /* Navigation Bar */
        nav {
            width: 100%;
            background-color: #333;
            padding: 10px 0;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
			white-space: nowrap;
        }

        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
        }

        nav ul li {
            margin: 0 350px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-size: 20px;
            padding: 10x 16px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }

        nav ul li a:hover {
            background-color: #575757;
        }

        /* Centering the login box */
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            text-align: center;
            margin-top: 20px; /* Adjust for nav bar height */
			white-space: nowrap;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }

        /* Form Styles */
        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-size: 16px;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            width: 100%;
        }

        button {
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Error message styling */
        p.error {
            color: red;
            margin-top: 10px;
            font-size: 14px;
        }

        /* Responsive design */
        @media (max-width: 600px) {
            .login-container {
                padding: 20px;
                width: 90%;
            }

            h2 {
                font-size: 20px;
            }

            input[type="text"],
            input[type="password"] {
                font-size: 14px;
            }

            button {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <ul>
            <li><a href="index.php">Store Management System</a></li>
            <li><a href="login.php">Log In</a></li>
        
        </ul>
    </nav>

    <!-- Login Form -->
    <div class="login-container">
        <h2>Login</h2>
        
        <?php
        // Display error message if there is one
        if (isset($error_message)) {
            echo "<p class='error'>$error_message</p>";
        }
        ?>

        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
