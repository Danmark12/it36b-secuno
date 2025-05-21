<?php
require 'db.php'; // Your PDO connection

if (!isset($_GET['token'])) {
    showMessage("❌ Invalid request. No token provided.");
    exit;
}

$token = $_GET['token'];

// Find matching pending user
$stmt = $conn->prepare("SELECT * FROM pending_users WHERE token = :token AND token_expires_at > NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    showMessage("❌ Token is invalid or has expired. Please register again.");
    exit;
}

// Move to `users` table
$stmt = $conn->prepare("INSERT INTO users (email, user_type, password_hash, created_at) VALUES (:email, :user_type, :password_hash, NOW())");
$stmt->execute([
    'email' => $user['email'],
    'user_type' => $user['user_type'],
    'password_hash' => $user['password_hash']
]);

// Remove from `pending_users`
$stmt = $conn->prepare("DELETE FROM pending_users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);

showMessage("✅ Your email has been verified successfully! You can now log in.");

// Show styled message
function showMessage($message) {
    echo "<!DOCTYPE html>
    <html><head><title>Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .message-box {
            padding: 25px 35px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            border-left: 6px solid #4CAF50;
            color: #333;
            max-width: 500px;
            text-align: center;
        }
    </style>
    </head><body>
    <div class='message-box'>$message</div>
    </body></html>";
}
?>
