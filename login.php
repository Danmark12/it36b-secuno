<?php
// login.php - User login page with account lockout and email notification
require_once 'db/config.php'; // Include the database configuration and session start

require_once 'vendor/autoload.php'; // Include PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security Headers (always send these) ---
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// Content-Security-Policy:
// default-src 'self' - Only allow resources from the same origin by default.
// style-src 'self' https://fonts.googleapis.com 'sha256-ZFNsDbSb3zuYkHuMhs5yoI8ysgY4TaScXvWc8J57/Ak=';
//      'self' for inline styles, https://fonts.googleapis.com for Google Fonts CSS.
//      The SHA256 hash below is CRUCIAL for your INLINE <style> block.
//      If you modify the <style> block in this HTML, you MUST REGENERATE this hash.
//      You can usually find the correct hash in browser developer console warnings if it's incorrect.
// font-src 'self' https://fonts.gstatic.com - Google Fonts fonts.
// script-src 'self' https://www.google.com https://www.gstatic.com;
//      'self' for your own scripts, Google's domains for reCAPTCHA.
// frame-src https://www.google.com; - Google reCAPTCHA iframe.
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com 'sha256-ZFNsDbSb3zuYkHuMhs5yoI8ysgY4TaScXvWc8J57/Ak='; font-src 'self' https://fonts.gstatic.com; script-src 'self' https://www.google.com https://www.gstatic.com; frame-src https://www.google.com;");


$errors = [];
$success = '';

// --- Configuration Constants for Login Security ---
const MAX_ATTEMPTS_BEFORE_CAPTCHA = 3; // Number of failed attempts before showing reCAPTCHA
const MAX_ATTEMPTS_BEFORE_LOCKOUT_WARNING = 5; // Number of failed attempts before sending alert email
const MAX_LOGIN_ATTEMPTS_TOTAL = 7; // Number of failed attempts before locking account
const LOCKOUT_DURATION_SECONDS = 30; // How long the account is locked (in seconds)

// --- CSRF Token Generation (for login form) ---
// This function gets or generates a CSRF token for the current session.
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Logs user activity to the database.
 *
 * @param PDO $conn The database connection object.
 * @param int|null $user_id The ID of the user, or null if not applicable (e.g., non-existent user).
 * @param string $activity_type A short, descriptive type of activity (e.g., 'Login Success', 'Login Failed').
 * @param string $description A more detailed description of the activity.
 */
function logUserActivity(PDO $conn, ?int $user_id, string $activity_type, string $description) {
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    try {
        $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address) VALUES (:user_id, :activity_type, :description, :ip_address)");
        $stmt->execute([
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'description' => $description,
            'ip_address' => $ip_address
        ]);
    } catch (PDOException $e) {
        // Log the error but don't stop execution or expose details to the user
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}


/**
 * Sends a One-Time Password (OTP) email to the user.
 *
 * @param string $email The recipient's email address.
 * @param string $otp_code The generated OTP.
 * @return bool True if the email was sent successfully, false otherwise.
*/
function sendOtpEmail($email, $otp_code) {
    $mail = new PHPMailer(true);
    try {
        // Server settings for Gmail SMTP (ensure these match your config for sending)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        // !! IMPORTANT: REPLACE WITH YOUR GMAIL ADDRESS AND APP PASSWORD !!
        // If 2FA is enabled on your Gmail, you MUST use an App Password.
        // Go to your Google Account -> Security -> App passwords to generate one.
        $mail->Username   = 'danmarkpetalcurin@gmail.com';     // <--- REPLACE WITH YOUR GMAIL ADDRESS
        $mail->Password   = 'qdal zfxu fsej bqqf';         // <--- REPLACE WITH YOUR GMAIL APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
        $mail->Port       = 587; // Port for TLS

        // Recipients
        // !! IMPORTANT: REPLACE WITH YOUR GMAIL ADDRESS !!
        $mail->setFrom('danmarkpetalcurin@gmail.com', 'Secuno System'); // <--- REPLACE WITH YOUR GMAIL ADDRESS
        $mail->addAddress($email); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = "Your One-Time Password (OTP) for Secuno System";
        $mail->Body    = "Hello,<br><br>Your One-Time Password (OTP) for Secuno System login is: <strong>" . htmlspecialchars($otp_code) . "</strong><br><br>This OTP is valid for 5 minutes.<br><br>Do not share this code with anyone.<br><br>Best regards,<br>The Secuno System Team";
        $mail->AltBody = "Hello,\n\nYour One-Time Password (OTP) for Secuno System login is: " . htmlspecialchars($otp_code) . "\n\nThis OTP is valid for 5 minutes.\n\nDo not share this code with anyone.\n\nBest regards,\nThe Secuno System Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error for debugging, but don't expose too much detail to the user.
        error_log("OTP email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an email notification when an account is locked.
 *
 * @param string $email The recipient's email address.
 * @param string $unlock_time_formatted The formatted time when the account will unlock.
 * @return bool True if the email was sent successfully, false otherwise.
*/
function sendAccountLockedEmail($email, $unlock_time_formatted) {
    $mail = new PHPMailer(true);
    try {
        // Server settings for Gmail SMTP (should match sendOtpEmail)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'danmarkpetalcurin@gmail.com';     // <--- REPLACE WITH YOUR GMAIL ADDRESS
        $mail->Password   = 'qdal zfxu fsej bqqf';         // <--- REPLACE WITH YOUR GMAIL APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('danmarkpetalcurin@gmail.com', 'Secuno System'); // <--- REPLACE WITH YOUR GMAIL ADDRESS
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Account Locked Notification for Secuno System";
        $mail->Body    = "Hello,<br><br>Your account has been temporarily locked due to too many failed login attempts.<br><br>You will be able to try logging in again after <strong>" . htmlspecialchars($unlock_time_formatted) . "</strong>.<br><br>If you did not attempt to log in, please secure your account and consider changing your password once unlocked.<br><br>Best regards,<br>The Secuno System Team";
        $mail->AltBody = "Hello,\n\nYour account has been temporarily locked due to too many failed login attempts.\n\nYou will be able to try logging in again after " . htmlspecialchars($unlock_time_formatted) . ".\n\nIf you did not attempt to log in, please secure your account and consider changing your password once unlocked.\n\nBest regards,\nThe Secuno System Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Account locked email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Sends an alert email to the user about suspicious login attempts.
 *
 * @param string $email The recipient's email address.
 * @param int $attempts_left The number of attempts remaining before lockout.
 * @return bool True if the email was sent successfully, false otherwise.
*/
function sendAlertEmail($email, $attempts_left) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'danmarkpetalcurin@gmail.com';     // <--- REPLACE WITH YOUR GMAIL ADDRESS
        $mail->Password   = 'qdal zfxu fsej bqqf';         // <--- REPLACE WITH YOUR GMAIL APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('danmarkpetalcurin@gmail.com', 'Secuno System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "⚠️ Security Alert: Unusual Login Attempts on Your Account";
        $mail->Body    = "Hello,<br><br>We've detected unusual login attempts on your Secuno System account.<br><br><strong>Someone is trying to access your account. You have " . htmlspecialchars($attempts_left) . " more attempts left before your account is temporarily locked.</strong><br><br>If this was not you, please secure your account immediately or contact support.<br><br>Best regards,<br>The Secuno System Team";
        $mail->AltBody = "Hello,\n\nWe've detected unusual login attempts on your Secuno System account.\n\nSomeone is trying to access your account. You have " . htmlspecialchars($attempts_left) . " more attempts left before your account is temporarily locked.\n\nIf this was not you, please secure your account immediately or contact support.\n\nBest regards,\nThe Secuno System Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Alert email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}


// --- Main Login Process ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== getCsrfToken()) {
        $errors[] = "Invalid CSRF token. Please try again.";
        unset($_SESSION['csrf_token']); // Regenerate token on failure
        // Log CSRF failure - we don't have a user_id here directly
        logUserActivity($conn, null, 'CSRF Failed', 'Invalid CSRF token submitted during login attempt.');
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic input validation
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }

        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        // Initialize session-based failed attempts if not set (for reCAPTCHA display logic)
        if (!isset($_SESSION['failed_login_attempts'])) {
            $_SESSION['failed_login_attempts'] = 0;
        }

        // If session-based failed attempts are 3 or more, require CAPTCHA
        if ($_SESSION['failed_login_attempts'] >= MAX_ATTEMPTS_BEFORE_CAPTCHA) {
            if (empty($_POST['g-recaptcha-response'])) {
                $errors[] = "Please complete the CAPTCHA challenge.";
            } else {
                $recaptcha_secret = '6LfeJ0QrAAAAAPJPLbNzE5Q9L0CfWyJ9dzcq7OHv'; // Your Secret Key
                $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($recaptcha_secret) . "&response=" . urlencode($_POST['g-recaptcha-response']) . "&remoteip=" . urlencode($user_ip));
                $responseKeys = json_decode($response, true);

                if (intval($responseKeys["success"]) !== 1) {
                    $errors[] = "CAPTCHA verification failed. Please try again.";
                    $_SESSION['failed_login_attempts']++; // Increment even on CAPTCHA failure
                }
            }
        }

        // Proceed only if no validation or CSRF errors so far
        if (empty($errors)) {
            try {
                // Fetch user details including failed_login_attempts from the 'users' table
                $stmt = $conn->prepare("SELECT id, password_hash, user_type, email, failed_login_attempts FROM users WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Add a small delay for both successful and failed logins to deter brute-force attacks
                usleep(rand(500000, 1000000)); // Delay between 0.5 to 1 second

                $is_locked = false;
                $unlock_time = null;
                $user_id = $user['id'] ?? null; // Get user ID if user exists

                // --- Check for Account Lockout (Database-based) ---
                if ($user_id) { // Only check if user exists
                    $stmt_lock = $conn->prepare("SELECT unlock_at FROM account_lockouts WHERE user_id = :user_id LIMIT 1");
                    $stmt_lock->execute(['user_id' => $user_id]);
                    $lockout_record = $stmt_lock->fetch(PDO::FETCH_ASSOC);

                    if ($lockout_record) {
                        $unlock_time = new DateTime($lockout_record['unlock_at']);
                        $current_time = new DateTime();

                        if ($current_time < $unlock_time) {
                            $is_locked = true;
                            $errors[] = "Your account is locked. Please try again after " . $unlock_time->format('H:i:s') . ".";
                            logUserActivity($conn, $user_id, 'Login Failed', 'Attempted login on locked account for email: ' . $email);
                        } else {
                            // Lockout has expired, remove the lockout record
                            $conn->prepare("DELETE FROM account_lockouts WHERE user_id = :user_id")->execute(['user_id' => $user_id]);
                            // Also reset failed attempts in the users table when lockout expires
                            $conn->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = :user_id")->execute(['user_id' => $user_id]);
                            logUserActivity($conn, $user_id, 'Account Unlocked', 'Account unlocked due to lockout expiry for email: ' . $email);
                        }
                    }
                }

                // If account is locked, do not proceed with password verification
                if ($is_locked) {
                    // Errors already set, just exit this block
                } elseif ($user && password_verify($password, $user['password_hash'])) {
                    // --- Password correct, now proceed to OTP ---

                    // Reset all failed login attempts for this user in the database
                    $conn->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = :user_id")->execute(['user_id' => $user['id']]);
                    // Also clear any session-based failed attempts
                    $_SESSION['failed_login_attempts'] = 0;
                    // Ensure any existing lockout record is removed on successful login
                    $conn->prepare("DELETE FROM account_lockouts WHERE user_id = :user_id")->execute(['user_id' => $user['id']]);

                    // Generate OTP
                    $otp_code = strval(random_int(100000, 999999)); // 6-digit OTP, ensure string
                    $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes")); // OTP valid for 5 minutes

                    // Store OTP in database
                    $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id")->execute(['user_id' => $user['id']]);
                    $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (:user_id, :otp_code, :expires_at)");
                    if ($stmt->execute([
                        'user_id' => $user['id'],
                        'otp_code' => $otp_code,
                        'expires_at' => $otp_expiry
                    ])) {
                        // Send OTP via email
                        if (sendOtpEmail($user['email'], $otp_code)) {
                            // Store user_id and email temporarily in session for OTP verification
                            $_SESSION['otp_user_id'] = $user['id'];
                            $_SESSION['otp_email'] = $user['email'];

                            // Successful Login:
                            // After successful login and setting $_SESSION['user_id']
                            // Log this as a pre-OTP success (OTP sent)
                            logUserActivity($conn, $user['id'], 'Login OTP Sent', 'User entered correct credentials; OTP sent to ' . $user['email'] . '.');

                            // Redirect to OTP verification page
                            header('Location: verify_otp.php');
                            exit();
                        } else {
                            $errors[] = "Authentication successful, but could not send OTP email. Please try again or contact support.";
                            $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id AND otp_code = :otp_code")->execute(['user_id' => $user['id'], 'otp_code' => $otp_code]);
                            logUserActivity($conn, $user['id'], 'OTP Send Failed', 'Authentication successful, but OTP email failed for user: ' . $user['email'] . '.');
                        }
                    } else {
                        $errors[] = "Failed to generate and store OTP. Please try again.";
                        logUserActivity($conn, $user['id'], 'OTP Generation Failed', 'Failed to generate/store OTP for user: ' . $user['email'] . '.');
                    }

                } else {
                    // Invalid credentials (email not found or password incorrect)
                    $errors[] = "Invalid email or password.";

                    // Determine user ID for logging failed attempt:
                    // If user exists, use their ID. If not, use 0 or null to indicate non-existent user.
                    $attemptedUserId = $user['id'] ?? null;
                    $attemptedUsername = $email; // Use the provided email as the attempted username

                    // Failed Login Attempt:
                    // If login credentials are incorrect
                    // Assuming you have the attempted username, you might need to query the users table
                    // to get a user_id for failed attempts if you want to track by user_id for non-existent users,
                    // or just pass a dummy ID like 0 if you log non-user specific attempts.
                    // For specific user attempts, fetch the user_id if the username exists, even if password fails.
                    logUserActivity($conn, $attemptedUserId, 'Login Failed', 'Failed login attempt for username: ' . $attemptedUsername);


                    // Increment failed attempts in the database for the specific user
                    if ($user) {
                        $new_attempts = $user['failed_login_attempts'] + 1;
                        $conn->prepare("UPDATE users SET failed_login_attempts = :attempts WHERE id = :user_id")->execute(['attempts' => $new_attempts, 'user_id' => $user['id']]);

                        // Send alert email if attempts reach MAX_ATTEMPTS_BEFORE_LOCKOUT_WARNING
                        if ($new_attempts === MAX_ATTEMPTS_BEFORE_LOCKOUT_WARNING) {
                            $attempts_left_for_warning = MAX_LOGIN_ATTEMPTS_TOTAL - $new_attempts;
                            sendAlertEmail($user['email'], $attempts_left_for_warning);
                            logUserActivity($conn, $user['id'], 'Login Warning Sent', 'Warning email sent to user ' . $user['email'] . ' due to multiple failed login attempts.');
                        }

                        // Lock the account if attempts exceed MAX_LOGIN_ATTEMPTS_TOTAL
                        if ($new_attempts >= MAX_LOGIN_ATTEMPTS_TOTAL) {
                            $lock_until_datetime = new DateTime();
                            $lock_until_datetime->modify('+' . LOCKOUT_DURATION_SECONDS . ' seconds');
                            $lock_until_db_format = $lock_until_datetime->format("Y-m-d H:i:s");
                            $lock_until_display_format = $lock_until_datetime->format('H:i:s'); // Format for display in email/message

                            $stmt_insert_lock = $conn->prepare("INSERT INTO account_lockouts (user_id, unlock_at) VALUES (:user_id, :unlock_at) ON DUPLICATE KEY UPDATE unlock_at = VALUES(unlock_at), locked_at = CURRENT_TIMESTAMP()");
                            $stmt_insert_lock->execute(['user_id' => $user['id'], 'unlock_at' => $lock_until_db_format]);

                            // Reset failed attempts in 'users' table after locking to prevent immediate re-lock after unlock
                            $conn->prepare("UPDATE users SET failed_login_attempts = 0 WHERE id = :user_id")->execute(['user_id' => $user['id']]);

                            $errors[] = "Your account has been locked due to too many failed login attempts. Please try again after " . $lock_until_display_format . ".";

                            // Send account locked email
                            sendAccountLockedEmail($user['email'], $lock_until_display_format);
                            logUserActivity($conn, $user['id'], 'Account Locked', 'Account locked due to ' . $new_attempts . ' failed login attempts for user: ' . $user['email'] . '.');
                        }
                    } else {
                         // If user does not exist, still increment session-based counter for reCAPTCHA trigger
                         $_SESSION['failed_login_attempts']++;
                         // Logging for non-existent user is already handled by logUserActivity($conn, $attemptedUserId, ...) above
                    }
                    // Always increment session-based counter for reCAPTCHA trigger, even if user doesn't exist (to prevent enumeration)
                    // This is already done for non-existent users in the 'else' block above for `if ($user)`.
                    // For existing users where password failed, it's covered by the `if ($user)` block.
                }

            } catch (PDOException $e) {
                // Log the database error for administrator
                error_log("Login database error: " . $e->getMessage());
                $errors[] = "An unexpected error occurred. Please try again later.";
                // Log the critical database error, possibly without a user_id if the error occurred before fetching it.
                logUserActivity($conn, null, 'Critical Error', 'Database error during login process: ' . $e->getMessage());
            }
        }
    }
}

// Get the current CSRF token for the form (even on initial page load)
$csrf_token_value = getCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Secuno System Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
        .login-section {
            width: 90%;
            max-width: 450px;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            background: white;
            box-sizing: border-box;
        }
        .login-section h2 {
            margin-bottom: 25px;
            font-size: 32px;
            color: #2c3e50;
            text-align: center;
            font-weight: 600;
        }
        .login-section input[type="email"],
        .login-section input[type="password"] {
            width: calc(100% - 24px);
            padding: 12px;
            margin-bottom: 18px;
            border: 1px solid #dcdcdc;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .login-section input[type="email"]:focus,
        .login-section input[type="password"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }
        .login-section button {
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
        .login-section button:hover {
            background-color: #001a66;
            transform: translateY(-2px);
        }
        .login-section button:active {
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
        /* Style for link to registration */
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .register-link a {
            color: #00004d;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-section">
        <h2>User Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="message error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token_value) ?>">

            <input type="email" name="email" placeholder="Enter Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <input type="password" name="password" placeholder="Enter Password" required>

            <?php if (isset($_SESSION['failed_login_attempts']) && $_SESSION['failed_login_attempts'] >= MAX_ATTEMPTS_BEFORE_CAPTCHA): ?>
                <div class="g-recaptcha" data-sitekey="6LfeJ0QrAAAAAGlPMpLLVKkBfryQyetzM4UVdgU1"></div> <br>
            <?php endif; ?>

            <button type="submit">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</div>

</body>
</html>