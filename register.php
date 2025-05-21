<?php
session_start();
require 'db/config.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:");

$errors = [];
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$_SESSION['register_attempts'] = $_SESSION['register_attempts'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['register_attempts'] > 5) {
        $errors[] = "Too many registration attempts. Please try again later.";
    } elseif (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid CSRF token.";
    } else {
        $_SESSION['register_attempts']++;

        $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
        $user_type = $_POST['user_type'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$email || !$user_type || !$password || !$confirm_password) {
            $errors[] = "All fields are required.";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email address.";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }

        $allowed_user_types = ['admin', 'user'];
        if (!in_array($user_type, $allowed_user_types)) {
            $errors[] = "Invalid user type selected.";
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = "Email is already registered.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }

        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $conn->prepare("INSERT INTO users (email, user_type, password_hash)
                                        VALUES (:email, :user_type, :password_hash)");
                $stmt->execute([
                    'email' => $email,
                    'user_type' => $user_type,
                    'password_hash' => $password_hash
                ]);

                $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
                $_SESSION['register_attempts'] = 0;
            } catch (PDOException $e) {
                $errors[] = "Registration failed: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Secuno Register</title>
  <style>
    body {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      background-color: #fff;
    }
    .container {
      display: flex;
      height: 100vh;
    }
    .register-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 40px;
      background: #fff;
    }
    .logo {
      position: absolute;
      top: 20px;
      left: 30px;
      font-size: 24px;
      font-weight: bold;
    }
    .logo span {
      color: #00b0f0;
    }
    .register-form {
      width: 100%;
      max-width: 400px;
    }
    .register-form h2 {
      margin-bottom: 20px;
      font-size: 28px;
    }
    .register-form input, .register-form select {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .register-form button {
      width: 100%;
      padding: 12px;
      background-color: #00004d;
      color: white;
      font-size: 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }
    .register-form .message {
      margin-bottom: 10px;
      font-size: 14px;
    }
    .register-form .message.error {
      color: red;
    }
    .register-form .message.success {
      color: green;
    }
    .register-form .login-link {
      margin-top: 15px;
      font-size: 14px;
      text-align: center;
    }
    .register-form .login-link a {
      color: #00b0f0;
      text-decoration: none;
    }
    .image-section {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #fff;
    }
    .image-section img {
      width: 300px;
      max-width: 100%;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="register-section">
    <div class="logo">
      <span>Secuno</span>
    </div>
    <div class="register-form">
      <h2>Sign Up</h2>

      <?php if (!empty($errors)): ?>
        <div class="message error">
          <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
      <?php elseif ($success): ?>
        <div class="message success">
          <p><?= $success ?></p>
        </div>
      <?php endif; ?>

      <form action="register.php" method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <input type="email" name="email" placeholder="Enter Email" required>

        <select name="user_type" required>
          <option value="">Select User Type</option>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>

        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>

        <button type="submit">Register</button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.php">Log In here!</a>
      </div>
    </div>
  </div>

  <div class="image-section">
    <img src="../image/doctor.jpg" alt="Doctor">
  </div>
</div>

</body>
</html>
