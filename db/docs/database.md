CREATE TABLE pending_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    user_type VARCHAR(50),
    password_hash VARCHAR(255),
    token VARCHAR(64) NOT NULL,
    token_expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    user_type VARCHAR(50),
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Assuming your 'users' table already exists
ALTER TABLE users
ADD COLUMN failed_login_attempts INT DEFAULT 0;

CREATE TABLE IF NOT EXISTS account_lockouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unlock_at DATETIME NOT NULL,
    reason VARCHAR(255) DEFAULT 'Too many failed login attempts',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (user_id) -- Ensures only one active lockout record per user
);

......................................................

CREATE TABLE incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    incident_type VARCHAR(255) NOT NULL,
    incident_date_time DATETIME NOT NULL,
    system_affected VARCHAR(255) NOT NULL,
    severity VARCHAR(50) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    admin_remarks TEXT NULL,
    resolution_status VARCHAR(50) DEFAULT 'Unresolved',
    resolution_details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE incident_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL, -- size in bytes
    file_path VARCHAR(512) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
);

................................................................

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
    <!-- echo "<!DOCTYPE html>
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
?> -->
........................

CREATE TABLE user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(100) NOT NULL, -- e.g., 'Login Success', 'Login Failed', 'Report Submitted', 'Password Change'
    description TEXT, -- Detailed description of the activity
    ip_address VARCHAR(45) NULL, -- IPv4 or IPv6 address
    user_agent TEXT NULL, -- Browser and OS information
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add an index for faster lookups by user and timestamp
CREATE INDEX idx_user_activity ON user_activity_logs (user_id, timestamp DESC);

.........................................
ALTER TABLE users
ADD COLUMN profile_photo_path VARCHAR(255) NULL DEFAULT 'uploads/profile_photos/default.png' AFTER email;

CREATE TABLE profile_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE, -- UNIQUE ensures one photo per user
    file_path VARCHAR(255) NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE users
ADD COLUMN name VARCHAR(255) NULL AFTER email;

...........................................

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('open', 'closed', 'pending') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
ALTER TABLE reports MODIFY COLUMN status ENUM('open', 'closed', 'pending', 'resolved') DEFAULT 'open';