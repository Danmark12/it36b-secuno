<?php
// db/config.php - Database connection and global settings

// --- Session Handling ---
// Start session only if not already started.
// This block should be included once at the very top of any script that uses sessions.
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 1800); // 30 minutes
    session_start();
}

// --- ERROR REPORTING (FOR DEVELOPMENT ONLY) ---
// IMPORTANT: Disable these in a production environment for security.
// In production, use error_log() to write errors to a file, not display them.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END ERROR REPORTING CONFIG ---

// Database connection parameters
$host = 'localhost';
$db   = 'secuno';
$user = 'your_database_username';   // <--- CHANGE THIS to your actual DB username
$pass = 'your_database_password';   // <--- CHANGE THIS to your actual DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                // Use real prepared statements
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the actual error to a secure server log file (e.g., Apache/Nginx error log)
    error_log("Database connection failed: " . $e->getMessage(), 0);
    // Display a generic message to the user for security
    die('An essential service is currently unavailable. Please try again later.');
}

// --- Centralized function for logging activities ---
// This function needs to be defined once and made available where needed.
// Placing it in config.php ensures it's loaded whenever the database connection is.
if (!function_exists('logUserActivity')) { // Check if function already exists to prevent redeclaration errors
    function logUserActivity($conn, $userId, $activityType, $description = null) {
        // Get IP address
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // For proxy environments, take the first IP in the list
            $ipAddresses = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ipAddress = trim($ipAddresses[0]);
        }

        // Get User Agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            // Use NULL for user_id if it's not provided (e.g., for system-level errors or unknown users)
            $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $activityType, $description, $ipAddress, $userAgent]);
        } catch (PDOException $e) {
            // Log the error internally, but don't show to user
            error_log("Error logging user activity for user ID " . ($userId ?? 'N/A') . ": " . $e->getMessage());
            // In a real application, you might want to alert an admin here.
        }
    }
}


// --- Utility function to show styled messages ---
// Check if the function showMessage() already exists to prevent "Cannot redeclare" error.
// This allows you to include config.php multiple times without issues if needed,
// though generally, it's best to include it only once per request.
if (!function_exists('showMessage')) {
    function showMessage($message, $isError = false) {
        $borderColor = $isError ? '#cc0000' : '#4CAF50';
        $backgroundColor = $isError ? '#f8d7da' : '#d4edda';
        $textColor = $isError ? '#721c24' : '#155724';

        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Status</title>
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
                    background-color: {$backgroundColor};
                    border-radius: 10px;
                    box-shadow: 0 6px 12px rgba(0,0,0,0.1);
                    border-left: 6px solid {$borderColor};
                    color: {$textColor};
                    max-width: 500px;
                    text-align: center;
                    word-wrap: break-word;
                    font-size: 1.1em;
                }
            </style>
        </head>
        <body>
            <div class='message-box'>{$message}</div>
        </body>
        </html>";
        exit; // Terminate script after displaying message
    }
}
?>