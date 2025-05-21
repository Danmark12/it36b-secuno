<?php
require 'db.php'; // Your PDO connection
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = 'student';

    // ✅ Validate Gmail email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        showMessage("❌ Only valid @gmail.com email addresses are allowed.");
        exit;
    }

    // ✅ Check if already registered
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetchColumn() > 0) {
        showMessage("❌ Email is already registered.");
        exit;
    }

    // ✅ Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // ✅ Generate token
    $token = bin2hex(random_bytes(32));
    $token_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

    // ✅ Insert into pending_users
    $stmt = $conn->prepare("INSERT INTO pending_users (email, user_type, password_hash, token, token_expires_at) VALUES (:email, :user_type, :password_hash, :token, :token_expires_at)");
    $stmt->execute([
        'email' => $email,
        'user_type' => $user_type,
        'password_hash' => $password_hash,
        'token' => $token,
        'token_expires_at' => $token_expiry
    ]);

    // ✅ Send verification email
    $verify_link = "http://yourdomain.com/verify.php?token=$token"; // Replace with your domain
    $subject = "Verify Your Email";
    $body = "Click this link to verify your account: <a href='$verify_link'>$verify_link</a>";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Your Gmail
        $mail->Password = 'your-app-password'; // Use App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Your App');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        showMessage("✅ Registration successful! Please check your Gmail to verify your account.");
    } catch (Exception $e) {
        showMessage("❌ Email could not be sent. Error: {$mail->ErrorInfo}");
    }
}

// Message display
function showMessage($message) {
    echo "<div class='message-box'>$message</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            width: 350px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #3f8efc;
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background-color: #296ed9;
        }

        .message-box {
            margin: 20px auto;
            width: 90%;
            max-width: 500px;
            padding: 15px;
            background-color: #eaf7ea;
            border-left: 6px solid #4CAF50;
            border-radius: 8px;
            color: #2e7d32;
            font-size: 15px;
            text-align: center;
        }

        .message-box:empty {
            display: none;
        }
    </style>
</head>
<body>

<form method="POST" action="">
    <h2>User Registration</h2>
    <label>Email (@gmail.com only)</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Register</button>
</form>

</body>
</html>
