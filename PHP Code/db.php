<?php
// Database connection credentials
$host = 'localhost';  // Database host
$user = 'root';       // Database username (default is 'root' for XAMPP)
$pass = '';           // Database password (default is empty for XAMPP)
$dbname = 'startup'; // Your database name

// Create the connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check for errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
