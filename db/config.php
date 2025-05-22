<?php
// db/config.php - Database connection and global settings

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    ini_set('session.cookie_lifetime', 1800); // 30 minutes
    session_start();
}

// Enable error reporting (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$db   = 'secuno';
$user = 'your_username';   // <-- change to your actual DB username
$pass = 'your_password';   // <-- change to your actual DB password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo 'Database connection failed. Please try again later.';
    exit;
}

// Utility function to show styled messages
function showMessage($message, $isError = false) {
    $borderColor = $isError ? '#cc0000' : '#4CAF50';
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
                word-wrap: break-word;
            }
        </style>
    </head>
    <body>
        <div class='message-box'>{$message}</div>
    </body>
    </html>";
}
?>
