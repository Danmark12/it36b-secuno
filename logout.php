<?php
session_start();        // Start the session
session_unset();        // Unset all session variables
session_destroy();      // Destroy the session

// Optional: Clear any cookies (e.g., if you're using 'Remember Me')
setcookie(session_name(), '', time() - 3600, '/');

// Redirect to login page
header("Location: index.php"); 
exit();
?>
