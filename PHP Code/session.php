<?php
// Start the session
session_start();

// Set session timeout limit in seconds (2 minutes = 120 seconds)
$session_timeout = 120; 

// Check if the session variable 'last_activity' exists (indicating the session was started)
if (isset($_SESSION['last_activity'])) {
    // Calculate the session's lifetime
    $session_lifetime = time() - $_SESSION['last_activity'];

    // If session exceeds 2 minutes, redirect to login page
    if ($session_lifetime > $session_timeout) {
        // Destroy session and redirect to login
        session_unset();
        session_destroy();
        header("Location: login.php");  // Redirect to login page
        exit();
    }
}

// Update the last activity time to the current time
$_SESSION['last_activity'] = time();
?>
