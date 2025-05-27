<?php
// C:\xampp\htdocs\it36b-ias\logout.php

// Ensure session is started before accessing $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Include database configuration and the logUserActivity function ---
// You MUST ensure 'db/config.php' correctly sets up the $conn PDO object.
// If logUserActivity is not in config.php, you'll need to define it here
// or in another file that gets included.
require_once 'db/config.php'; 

// IMPORTANT: Define logUserActivity here if it's NOT already in config.php
// (copied from previous responses for completeness)
if (!function_exists('logUserActivity')) {
    function logUserActivity(PDO $conn, ?int $user_id, string $activity_type, string $description) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        try {
            $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent) VALUES (:user_id, :activity_type, :description, :ip_address, :user_agent)");
            $stmt->execute([
                'user_id' => $user_id,
                'activity_type' => $activity_type,
                'description' => $description,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log user activity during logout: " . $e->getMessage());
        }
    }
}

// Before session destruction:
// Log the logout activity if a user was logged in
if (isset($_SESSION['user_id'])) {
    // We pass $conn, $_SESSION['user_id'], activity type, and description
    logUserActivity($conn, $_SESSION['user_id'], 'Logout', 'User logged out successfully.');
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"] // Corrected 'httppnly' to 'httponly'
    );
}

// Destroy the session data on the server
session_destroy();

// Redirect to the login page
header("Location: index.php"); // Adjust this path if your login page is different or in a subfolder
exit();
?>