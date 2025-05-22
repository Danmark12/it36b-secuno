<?php
// config.php - Database connection and global settings
// This file contains the database connection setup.
// It should be included at the beginning of any script that needs database access.

// Enable error reporting (disable in production for security)
ini_set('display_errors', 1); // Set to 0 in production
ini_set('display_startup_errors', 1); // Set to 0 in production
error_reporting(E_ALL);

// --- Session Security Configuration ---
// Set session cookie to be HttpOnly to prevent client-side script access
ini_set('session.cookie_httponly', 1);

// Set session cookie to be secure (only send over HTTPS)
// IMPORTANT: Set to 1 in production. For local development on HTTP, you might need to set it to 0.
// Once deployed to a live server with HTTPS, change this to 1.
// ini_set('session.cookie_secure', 1); // Uncomment and set to 1 if you are using HTTPS in production

// Use strict mode for sessions to prevent session fixation attacks
ini_set('session.use_strict_mode', 1);

// Set session cookie lifetime (e.g., 2 hours). Adjust as needed.
// This is the lifetime of the session ID cookie. The session data itself can persist longer.
ini_set('session.cookie_lifetime', 7200); // 2 hours

// Set global session garbage collection max lifetime (e.g., 2 hours).
// This helps ensure session data is eventually cleaned up from the server.
ini_set('session.gc_maxlifetime', 7200); // 2 hours

// Database connection parameters
$host = 'localhost';        // Your database host, e.g., 'localhost' or '127.0.0.1'
$db   = 'secuno';          // Your actual database name
$user = 'your_username';   // Your MySQL username
$pass = 'your_password';   // Your MySQL password
$charset = 'utf8mb4';      // Character set for the database connection

// Data Source Name (DSN) for PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options for a robust connection
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays for fetches
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements (more secure)
];

try {
    // Establish the PDO database connection
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Handle database connection errors securely.
    // In a production environment, you would log this error and display a generic message to the user.
    error_log("Database connection failed: " . $e->getMessage()); // Log the error
    echo 'Database connection failed. Please try again later.'; // Display a user-friendly message
    exit; // Stop script execution
}

// Function to display styled messages (used in verify.php)
// This function outputs a complete HTML page with a centered message box.
function showMessage($message, $isError = false) {
    $borderColor = $isError ? '#cc0000' : '#4CAF50'; // Red for error, green for success
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Verification Status</title>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: #f4f4f4;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .message-box {
                padding: 25px 35px;
                background-color: #fff;
                border-radius: 10px;
                box-shadow: 0 6px 12px rgba(0,0,0,0.1);
                border-left: 6px solid {$borderColor};
                color: #333;
                max-width: 500px;
                text-align: center;
                word-wrap: break-word; /* Ensure long messages wrap */
            }
        </style>
    </head>
    <body>
        <div class='message-box'>{$message}</div>
    </body>
    </html>";
}
?>