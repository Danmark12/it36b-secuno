<?php
// verify_otp.php - OTP verification page
require_once 'db/config.php'; // Include the database configuration and session start

require_once 'vendor/autoload.php'; // For PHPMailer (though not used directly here, good to keep consistent)

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// Content-Security-Policy:
// default-src 'self' - Only allow resources from the same origin by default.
// style-src 'self' https://fonts.googleapis.com 'sha256-FADzL39s+Fvj2RHZFy2SJEEA/Cb/phGFGdAQpXu3j7w=';
//    'self' for inline styles, https://fonts.googleapis.com for Google Fonts CSS.
//    !! IMPORTANT: The SHA256 hash below is CRUCIAL for your INLINE <style> block.
//    If you modify the <style> block in this HTML, you MUST REGENERATE this hash.
//    You can usually find the correct hash in browser developer console warnings if it's incorrect.
// font-src 'self' https://fonts.gstatic.com - Google Fonts fonts.
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com 'sha256-FADzL39s+Fvj2RHZFy2SJEEA/Cb/phGFGdAQpXu3j7w='; font-src 'self' https://fonts.gstatic.com;");


$errors = [];
$success = '';

// Check if user is coming from login.php and has an OTP pending
// If not, redirect them back to login page and show an error (using the $errors array)
if (!isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_email'])) {
    // Clear any leftover OTP session data before redirecting
    unset($_SESSION['otp_user_id']);
    unset($_SESSION['otp_email']);
    unset($_SESSION['otp_csrf_token']);

    // Set an error message to be displayed on the login page
    $_SESSION['login_error_message'] = "Your OTP session has expired or is invalid. Please try logging in again.";
    header('Location: login.php');
    exit();
}

$user_id_for_otp = $_SESSION['otp_user_id'];
$user_email_for_otp = $_SESSION['otp_email'];

// --- CSRF Token Generation (for OTP form) ---
// Use a different CSRF token for this form to avoid conflicts with login.php's token.
function getOtpCsrfToken() {
    if (empty($_SESSION['otp_csrf_token'])) {
        $_SESSION['otp_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['otp_csrf_token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['otp_csrf_token']) || $_POST['otp_csrf_token'] !== getOtpCsrfToken()) {
        $errors[] = "Invalid CSRF token. Please try again.";
        // Regenerate token on failure
        unset($_SESSION['otp_csrf_token']);
    } else {
        $user_otp = trim($_POST['otp_code'] ?? '');

        if (empty($user_otp)) {
            $errors[] = "Please enter the OTP.";
        } elseif (!preg_match('/^\d{6}$/', $user_otp)) { // Ensure it's exactly 6 digits
            $errors[] = "Invalid OTP format. It should be 6 digits.";
        }

        if (empty($errors)) {
            try {
                // Fetch the OTP from the database
                // ORDER BY created_at DESC ensures we get the latest OTP if multiple exist for a user.
                $stmt = $conn->prepare("SELECT otp_code, expires_at FROM otp_codes WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
                $stmt->execute(['user_id' => $user_id_for_otp]);
                $stored_otp = $stmt->fetch(PDO::FETCH_ASSOC);

                // Add a small delay to deter brute-force attempts on OTPs
                usleep(rand(200000, 500000)); // Delay between 0.2 to 0.5 seconds

                if ($stored_otp) {
                    // Check if OTP matches and is not expired
                    $current_time = new DateTime();
                    $expiry_time = new DateTime($stored_otp['expires_at']);

                    if ($user_otp === $stored_otp['otp_code'] && $current_time < $expiry_time) {
                        // OTP is valid! Finalize login.

                        // Fetch full user details for session (important: don't rely solely on $_SESSION['otp_user_id'])
                        // We need the user_type from the 'users' table
                        $stmt = $conn->prepare("SELECT id, email, user_type FROM users WHERE id = :user_id LIMIT 1");
                        $stmt->execute(['user_id' => $user_id_for_otp]);
                        $user_details = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($user_details) {
                            // --- Session Security ---
                            session_regenerate_id(true); // Regenerate session ID to prevent session fixation and hijacking

                            $_SESSION['user_id'] = $user_details['id'];
                            $_SESSION['email'] = $user_details['email'];
                            $_SESSION['user_type'] = $user_details['user_type']; // Store user type in session
                            $_SESSION['loggedin'] = true; // Mark user as logged in

                            // Clear OTP-related session variables from the session for security
                            unset($_SESSION['otp_user_id']);
                            unset($_SESSION['otp_email']);
                            unset($_SESSION['otp_csrf_token']);

                            // Invalidate/delete the used OTP from the database to prevent replay attacks
                            // Deleting all OTPs for this user ID on successful verification is a robust approach.
                            $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id")->execute(['user_id' => $user_id_for_otp]);

                            // --- Redirect based on user type ---
                            if ($user_details['user_type'] === 'admin') {
                                header("Location: admin_dashboard.php");
                                exit();
                            } else {
                                header("Location: users.php");
                                exit();
                            }

                        } else {
                            $errors[] = "User data could not be retrieved after OTP verification. Please try logging in again.";
                            // Log this critical error for debugging
                            error_log("SECURITY ALERT: User ID {$user_id_for_otp} found in OTP but not in users table. Possible data inconsistency or attack.");
                            // Invalidate OTP as user lookup failed
                            $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id")->execute(['user_id' => $user_id_for_otp]);
                        }

                    } else {
                        $errors[] = "Invalid or expired OTP. Please try logging in again to get a new code.";
                        // Invalidate the OTP to prevent reuse after failure or expiration
                        // If an invalid OTP is entered, or it expires, delete it to prevent further attempts on that specific OTP.
                        $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id")->execute(['user_id' => $user_id_for_otp]);
                    }
                } else {
                    $errors[] = "No active OTP found for this account. Please try logging in again.";
                }

            } catch (PDOException $e) {
                // Log the database error for administrator
                error_log("OTP verification database error: " . $e->getMessage());
                $errors[] = "An unexpected error occurred during OTP verification. Please try again.";
            }
        }
    }
}

// Get the current CSRF token for the form (even on initial page load)
$otp_csrf_token_value = getOtpCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verify OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Your CSS styles go here. If you modify these, you MUST regenerate the CSP SHA256 hash. */
        body {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .otp-section {
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background: white;
            box-sizing: border-box;
        }
        .otp-section h2 {
            margin-bottom: 25px;
            font-size: 32px;
            color: #2c3e50;
            text-align: center;
            font-weight: 600;
        }
        .otp-section p {
            text-align: center;
            margin-bottom: 20px;
            color: #555;
        }
        .otp-section input[type="text"] {
            width: calc(100% - 24px);
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 16px;
            text-align: center; /* Center the OTP for better UX */
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .otp-section input[type="text"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .otp-section button {
            width: 100%;
            padding: 14px;
            background-color: #00004d;
            color: white;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .otp-section button:hover {
            background-color: #001a66;
            transform: translateY(-2px);
        }
        .otp-section button:active {
            transform: translateY(0);
        }
        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            text-align: center;
            word-wrap: break-word;
        }
        .error {
            background-color: #ffe6e6;
            color: #cc0000;
            border: 1px solid #cc0000;
        }
        .success {
            background-color: #eaf7ea;
            color: #2e7d32;
            border: 1px solid #4CAF50;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="otp-section">
        <h2>Verify One-Time Password</h2>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p>An OTP has been sent to your email: <strong><?= htmlspecialchars($user_email_for_otp) ?></strong>. Please enter it below.</p>

        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="otp_csrf_token" value="<?= htmlspecialchars($otp_csrf_token_value) ?>">
            <input type="text" name="otp_code" placeholder="Enter OTP (6 digits)" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}">
            <button type="submit">Verify OTP</button>
        </form>
    </div>
</div>

</body>
</html>