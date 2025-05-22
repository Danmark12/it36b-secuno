
<?php
// verify.php - Email verification endpoint
require_once 'db/config.php'; // Include the database configuration, which also contains showMessage()

// --- Functions for Verification Logic ---

/**
 * Finds a pending user record by the provided token.
 * Checks if the token is valid and not expired.
 * @param PDO $conn The PDO database connection object.
 * @param string $token The verification token.
 * @return array|false The pending user record if found and valid, otherwise false.
 */
function findPendingUser($conn, $token) {
    $stmt = $conn->prepare("SELECT * FROM pending_users WHERE token = :token AND token_expires_at > NOW()");
    $stmt->execute(['token' => $token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Activates a user by moving their record from `pending_users` to `users` table
 * and then deleting it from `pending_users`. Uses a transaction for atomicity.
 * @param PDO $conn The PDO database connection object.
 * @param array $user The user record from `pending_users`.
 * @return bool True on successful activation, false otherwise.
 */
function activateUser($conn, $user) {
    try {
        $conn->beginTransaction(); // Start a database transaction

        // Insert into `users` table
        $stmt = $conn->prepare("INSERT INTO users (email, user_type, password_hash, created_at) VALUES (:email, :user_type, :password_hash, NOW())");
        $stmt->execute([
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'password_hash' => $user['password_hash']
        ]);

        // Delete from `pending_users` table
        $stmt = $conn->prepare("DELETE FROM pending_users WHERE id = :id");
        $stmt->execute(['id' => $user['id']]);

        $conn->commit(); // Commit the transaction if all operations are successful
        return true;
    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback the transaction if any error occurs
        error_log("Failed to activate user: " . $e->getMessage()); // Log the error
        return false;
    }
}

// --- Main Verification Process ---

// Check if a token is provided in the URL
if (!isset($_GET['token']) || empty($_GET['token'])) {
    showMessage("❌ Invalid request. No verification token provided.", true);
    exit;
}

$token = $_GET['token'];

// Find the pending user using the token
$user = findPendingUser($conn, $token);

if (!$user) {
    // If no user found or token expired/invalid
    showMessage("❌ Verification token is invalid or has expired. Please register again.", true);
    exit;
}

// Activate the user
if (activateUser($conn, $user)) {
    showMessage("✅ Your email has been verified successfully! You can now log in.");
} else {
    showMessage("❌ An error occurred during verification. Please try again later or contact support.", true);
}
?>