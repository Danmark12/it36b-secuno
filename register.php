
<?php
// register.php - User registration page with email verification
session_start(); // Start the session for CSRF token management

require_once 'db/config.php'; // Include the database configuration
require_once 'vendor/autoload.php'; // Include PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = []; // Array to store validation and processing errors
$success = ''; // String to store success messages

// --- Functions for Registration Logic ---

/**
 * Generates and retrieves the CSRF token.
 * If no token exists in the session, a new one is generated.
 * @return string The CSRF token.
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the user's email, password, and password confirmation.
 * Enforces email domain restriction and password strength rules.
 * @param string $email The user's email address.
 * @param string $password The user's chosen password.
 * @param string $password_confirm The password confirmation.
 * @param array $currentErrors An array of existing errors to append to.
 * @return array An updated array of errors.
 */
function validateRegistrationInput($email, $password, $password_confirm, $currentErrors) {
    // Email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $currentErrors[] = "Invalid email format.";
    }
    // Specific email domain restriction (as per your requirement: @nbsc.edu.ph)
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@nbsc\.edu\.ph$/', $email)) {
        $currentErrors[] = "Only valid @nbsc.edu.ph email addresses are allowed.";
    }

    // Password presence check
    if (empty($password)) {
        $currentErrors[] = "Password is required.";
    } elseif (strlen($password) < 8) { // Minimum password length
        $currentErrors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || // At least one uppercase letter
              !preg_match('/[a-z]/', $password) || // At least one lowercase letter
              !preg_match('/[0-9]/', $password) || // At least one number
              !preg_match('/[^A-Za-z0-9]/', $password)) { // At least one special character
        $currentErrors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Password confirmation check
    if ($password !== $password_confirm) {
        $currentErrors[] = "Passwords do not match.";
    }

    return $currentErrors;
}

/**
 * Checks if an email address already exists in either the `users` or `pending_users` tables.
 * @param PDO $conn The PDO database connection object.
 * @param string $email The email address to check.
 * @param array $currentErrors An array of existing errors to append to.
 * @return array An updated array of errors.
 */
function checkDuplicateEmail($conn, $email, $currentErrors) {
    // Check in the `users` table
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        $currentErrors[] = "Email is already registered.";
    } else {
        // Check in the `pending_users` table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM pending_users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $currentErrors[] = "A verification email has already been sent to this address. Please check your inbox or wait for the token to expire.";
        }
    }
    return $currentErrors;
}

/**
 * Inserts a new user record into the `pending_users` table.
 * @param PDO $conn The PDO database connection object.
 * @param string $email The user's email.
 * @param string $user_type The user's type (e.g., 'student').
 * @param string $password_hash The hashed password.
 * @param string $token The verification token.
 * @param string $token_expiry The token expiry datetime string.
 * @return bool True on successful insertion, false otherwise.
 */
function insertPendingUser($conn, $email, $user_type, $password_hash, $token, $token_expiry) {
    $stmt = $conn->prepare("INSERT INTO pending_users (email, user_type, password_hash, token, token_expires_at)
                            VALUES (:email, :user_type, :password_hash, :token, :token_expires_at)");
    return $stmt->execute([
        'email' => $email,
        'user_type' => $user_type,
        'password_hash' => $password_hash,
        'token' => $token,
        'token_expires_at' => $token_expiry
    ]);
}

/**
 * Sends a verification email to the user.
 * @param string $email The recipient's email address.
 * @param string $token The verification token to include in the link.
 * @return bool True if the email was sent successfully, false otherwise.
 */
function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true); // Create a new PHPMailer instance
    try {
        // Server settings for Gmail SMTP
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                       // Gmail's SMTP server
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication

        // Your Gmail account details for sending
        // IMPORTANT: If 2-Factor Authentication (2FA) is enabled on danmarkpetalcurin@gmail.com,
        // you MUST use an "App Password" here, not your regular Gmail password.
        // Go to your Google Account -> Security -> App passwords (under "How you sign in to Google").
        // Select "Mail" for app and "Other (Custom name)" for device, then generate.
        $mail->Username   = 'danmarkpetalcurin@gmail.com';          // Your full Gmail address
        $mail->Password   = 'qdal zfxu fsej bqqf';                  // Your Gmail App Password (or regular password if 2FA is off)

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
        $mail->Port       = 587;                                    // TCP port for STARTTLS

        // Recipients
        // The 'From' address should match your SMTP Username for best deliverability.
        $mail->setFrom('danmarkpetalcurin@gmail.com', 'Secuno System'); // Sender email and name
        $mail->addAddress($email);                                  // Add the recipient (the user's @nbsc.edu.ph email)

        // Content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = "Verify Your Email for Secuno System";
        // IMPORTANT: The domain below has been updated for your local XAMPP setup.
        // If you move this to a live server, you MUST change 'http://localhost/it36b-ias'
        // to your actual live domain (e.g., '[https://yourlivesystem.com](https://yourlivesystem.com)').
        $verify_link = "http://localhost/it36b-ias/verify.php?token=$token";
        $mail->Body    = "Hello,<br><br>Thank you for registering with Secuno System. Please click the link below to verify your email address:<br><br><a href='{$verify_link}'>{$verify_link}</a><br><br>This link will expire in 24 hours.<br><br>If you did not register for an account, please ignore this email.<br><br>Best regards,<br>The Secuno System Team";
        $mail->AltBody = "Hello,\n\nThank you for registering with Secuno System. Please copy and paste the link below into your browser to verify your email address:\n\n{$verify_link}\n\nThis link will expire in 24 hours.\n\nIf you did not register for an account, please ignore this email.\n\nBest regards,\nThe Secuno System Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the PHPMailer error for debugging.
        // This error message is crucial for diagnosing email sending issues.
        error_log("Email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// --- Main Registration Process ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Honeypot Check (FIRST LINE OF DEFENSE AGAINST BOTS) ---
    // Check if the honeypot field 'fax_number' is filled.
    // If it's filled, it's likely a bot, so we just exit silently or provide a generic error.
    if (!empty($_POST['fax_number'])) {
        // Log this attempt if you want, but for the user, just act as if it succeeded
        // to avoid giving bots clues about how to bypass your defense.
        // For example, error_log("Honeypot triggered by IP: " . $_SERVER['REMOTE_ADDR']);
        $success = "Registration successful! Please check your @nbsc.edu.ph email to verify your account. Also check your spam/junk folder."; // Provide a success message to mislead bots
        // Or, simply exit silently: exit();
        // For demonstration, we'll just set success and let the page render.
    } else {
        // Proceed with actual registration logic only if honeypot is NOT filled
        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== getCsrfToken()) {
            $errors[] = "Invalid CSRF token. Please try again.";
        } else {
            // Sanitize and retrieve input
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? ''; // Added password confirmation field
            $user_type = 'user'; // Fixed user_type as 'user' as per your original code

            // Validate input fields
            $errors = validateRegistrationInput($email, $password, $password_confirm, $errors);

            // If no input validation errors, proceed to check for duplicates and database operations
            if (empty($errors)) {
                $errors = checkDuplicateEmail($conn, $email, $errors);

                if (empty($errors)) {
                    // Hash password
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);

                    // Generate token and expiry
                    $token = bin2hex(random_bytes(32));
                    // Changed token expiry from 1 hour to 24 hours
                    $token_expiry = date("Y-m-d H:i:s", strtotime("+24 hours")); // Token valid for 24 hours

                    // Insert into pending_users table
                    if (insertPendingUser($conn, $email, $user_type, $password_hash, $token, $token_expiry)) {
                        // Send verification email
                        if (sendVerificationEmail($email, $token)) {
                            $success = "Registration successful! Please check your @nbsc.edu.ph email to verify your account. Also check your spam/junk folder.";
                            // Removed unset($_SESSION['csrf_token']); to prevent immediate CSRF token invalidation
                        } else {
                            // If email sending fails, consider rolling back the pending user insertion
                            // or adding a mechanism for users to request a new verification email.
                            // For simplicity here, we just add an error message.
                            $errors[] = "Registration was successful, but the verification email could not be sent. Please contact support and mention 'Mailer Error' if possible.";
                        }
                    } else {
                        $errors[] = "Registration failed due to a database error. Please try again.";
                    }
                }
            }
        }
    }
}

// Get the current CSRF token for the form (this will generate a new one if unset, or use existing)
$csrf_token_value = getCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Secuno System Register</title>
    <link href="[https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap](https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap)" rel="stylesheet">
    <style>
        body {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5; /* Light grey background */
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh; /* Use min-height to ensure it works on shorter content */
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        .register-section {
            width: 90%; /* Responsive width */
            max-width: 450px; /* Max width for larger screens */
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15); /* Stronger shadow */
            background: white;
            box-sizing: border-box; /* Include padding in width */
        }
        .register-section h2 {
            margin-bottom: 25px;
            font-size: 32px; /* Slightly larger heading */
            color: #2c3e50; /* Darker text color */
            text-align: center;
            font-weight: 600; /* Semi-bold */
        }
        .register-section input[type="email"],
        .register-section input[type="password"] {
            width: calc(100% - 24px); /* Account for padding */
            padding: 12px;
            margin-bottom: 18px; /* More space between inputs */
            border: 1px solid #dcdcdc; /* Lighter border */
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .register-section input[type="email"]:focus,
        .register-section input[type="password"]:focus {
            border-color: #007bff; /* Blue focus border */
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25); /* Light blue shadow on focus */
            outline: none; /* Remove default outline */
        }
        .register-section button {
            width: 100%;
            padding: 14px; /* Larger button padding */
            background-color: #00004d; /* Original dark blue */
            color: white;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .register-section button:hover {
            background-color: #001a66; /* Slightly darker on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }
        .register-section button:active {
            transform: translateY(0); /* Press effect */
        }
        .message {
            margin-bottom: 20px; /* More space for messages */
            padding: 15px;
            border-radius: 8px;
            font-size: 15px;
            text-align: center;
            word-wrap: break-word; /* Ensure messages wrap */
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
        /* Media queries for responsiveness */
        @media (max-width: 600px) {
            .register-section {
                padding: 30px 20px; /* Adjust padding for smaller screens */
            }
            .register-section h2 {
                font-size: 28px;
            }
            .register-section input {
                font-size: 15px;
            }
            .register-section button {
                font-size: 16px;
            }
        }

        /* Styles for the honeypot field - makes it invisible */
        /* .honeypot-field {
            position: absolute;
            left: -9999px; 
            opacity: 0;   
            height: 1px;
            width: 1px;
            overflow: hidden;
        } */

                /* Added for the new login link styling */
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 15px;
        }
        .login-link a {
            color: #00004d; /* Dark blue, matching your button */
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            color: #001a66; /* Slightly darker on hover */
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="register-section">
        <h2>User Registration</h2>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php elseif ($success): ?>
            <div class="message success">
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token_value) ?>">
            
            <div class="honeypot-field">
                <label for="fax_number">Fax Number</label>
                <input type="text" id="fax_number" name="fax_number" tabindex="-1" autocomplete="off">
            </div>

            <input type="email" name="email" placeholder="Enter Email (@nbsc.edu.ph only)" required>
            <input type="password" name="password" placeholder="Enter Password" required>
            <input type="password" name="password_confirm" placeholder="Confirm Password" required>
            <button type="submit">Register</button>
        </form>
        <p class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

</body>
</html>