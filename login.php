<?php
session_start();

require_once 'db/config.php';
require_once 'vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Security Headers ---
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self' https://www.google.com https://www.gstatic.com; frame-src https://www.google.com;"); // Adjusted for reCAPTCHA


$errors = [];
$success = '';

// --- CSRF Token Generation (for login form) ---
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// --- Function to send OTP email ---
function sendOtpEmail($email, $otp_code) {
    $mail = new PHPMailer(true);
    try {
        // Server settings for Gmail SMTP (same as your register.php)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'danmarkpetalcurin@gmail.com'; // Your Gmail address
        $mail->Password   = 'qdal zfxu fsej bqqf';           // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('danmarkpetalcurin@gmail.com', 'Secuno System');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Your One-Time Password (OTP) for Secuno System";
        $mail->Body    = "Hello,<br><br>Your One-Time Password (OTP) for Secuno System login is: <strong>" . htmlspecialchars($otp_code) . "</strong><br><br>This OTP is valid for 5 minutes.<br><br>Do not share this code with anyone.<br><br>Best regards,<br>The Secuno System Team";
        $mail->AltBody = "Hello,\n\nYour One-Time Password (OTP) for Secuno System login is: " . htmlspecialchars($otp_code) . "\n\nThis OTP is valid for 5 minutes.\n\nDo not share this code with anyone.\n\nBest regards,\nThe Secuno System Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP email could not be sent to {$email}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// --- Main Login Process ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot check (same as register.php for consistency)
    if (!empty($_POST['fax_number'])) {
        // Silently fail or log bot attempt
        $errors[] = "Login failed due to suspicious activity. Please try again."; // Generic error
        // Or simply exit();
    } else {
        // CSRF token validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== getCsrfToken()) {
            $errors[] = "Invalid CSRF token. Please try again.";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            // Initialize or increment failed login attempts
            $_SESSION['failed_login_attempts'] = $_SESSION['failed_login_attempts'] ?? 0;
            // Get user's IP for rate limiting/CAPTCHA
            $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

            // --- CAPTCHA Verification ---
            // If failed attempts are 3 or more, require CAPTCHA
            if ($_SESSION['failed_login_attempts'] >= 3) {
                if (empty($_POST['g-recaptcha-response'])) {
                    $errors[] = "Please complete the CAPTCHA challenge.";
                } else {
                    $recaptcha_secret = 'YOUR_RECAPTCHA_SECRET_KEY'; // REPLACE WITH YOUR ACTUAL reCAPTCHA SECRET KEY
                    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=" . $recaptcha_secret . "&response=" . $_POST['g-recaptcha-response'] . "&remoteip=" . $user_ip);
                    $responseKeys = json_decode($response, true);

                    if (intval($responseKeys["success"]) !== 1) {
                        $errors[] = "CAPTCHA verification failed. Please try again.";
                        $_SESSION['failed_login_attempts']++; // Increment even on CAPTCHA failure
                    }
                }
            }

            if (empty($errors)) {
                try {
                    // Check if email exists in the database
                    $stmt = $conn->prepare("SELECT id, password_hash, user_type, email FROM users WHERE email = :email LIMIT 1");
                    $stmt->execute(['email' => $email]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($user && password_verify($password, $user['password_hash'])) {
                        // --- Password correct, now proceed to OTP ---

                        // Reset failed login attempts
                        $_SESSION['failed_login_attempts'] = 0;

                        // Generate OTP
                        $otp_code = random_int(100000, 999999); // 6-digit OTP
                        $otp_expiry = date("Y-m-d H:i:s", strtotime("+5 minutes")); // OTP valid for 5 minutes

                        // Store OTP in database
                        $stmt = $conn->prepare("INSERT INTO otp_codes (user_id, otp_code, expires_at) VALUES (:user_id, :otp_code, :expires_at)");
                        if ($stmt->execute([
                            'user_id' => $user['id'],
                            'otp_code' => $otp_code,
                            'expires_at' => $otp_expiry
                        ])) {
                            // Send OTP via email
                            if (sendOtpEmail($user['email'], $otp_code)) {
                                // Store user_id temporarily in session for OTP verification
                                $_SESSION['otp_user_id'] = $user['id'];
                                $_SESSION['otp_email'] = $user['email'];

                                // Redirect to OTP verification page
                                header('Location: verify_otp.php');
                                exit();
                            } else {
                                $errors[] = "Authentication successful, but could not send OTP email. Please try again or contact support.";
                                // Optionally, delete the stored OTP if email sending failed
                                $conn->prepare("DELETE FROM otp_codes WHERE user_id = :user_id AND otp_code = :otp_code")->execute(['user_id' => $user['id'], 'otp_code' => $otp_code]);
                            }
                        } else {
                            $errors[] = "Failed to generate and store OTP. Please try again.";
                        }

                    } else {
                        // Invalid credentials
                        $errors[] = "Invalid email or password.";
                        $_SESSION['failed_login_attempts']++; // Increment failed attempts
                    }

                } catch (PDOException $e) {
                    error_log("Login error: " . $e->getMessage());
                    $errors[] = "An unexpected error occurred. Please try again later.";
                }
            }
        }
    }
}

// Get the current CSRF token for the form
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
        .honeypot-field {
            position: absolute;
            left: -9999px;
            opacity: 0;
            height: 1px;
            width: 1px;
            overflow: hidden;
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

            <input type="email" name="email" placeholder="Enter Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <input type="password" name="password" placeholder="Enter Password" required>

            <?php if ($_SESSION['failed_login_attempts'] >= 3): ?>
                <div class="g-recaptcha" data-sitekey="YOUR_RECAPTCHA_SITE_KEY"></div> <br>
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