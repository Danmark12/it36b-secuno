<?php
// settings.php
session_start();
// require '../db/config.php';

$message = '';
// Placeholder user data - in a real app, fetch from database
$currentUser = [
    'name' => 'John Doe',
    'email' => 'john.doe@example.com',
    'profile_photo' => 'https://via.placeholder.com/150/00d1d1/ffffff?text=JD' // Placeholder image
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = $_POST['name'] ?? $currentUser['name'];
    $newEmail = $_POST['email'] ?? $currentUser['email'];
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Simulate updating user data
    $currentUser['name'] = $newName;
    $currentUser['email'] = $newEmail;

    if (!empty($newPassword)) {
        if ($newPassword === $confirmPassword) {
            // In a real app: Hash the new password and update in DB
            // $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $message = '<p class="success-message">Profile and password updated successfully!</p>';
        } else {
            $message = '<p class="error-message">New password and confirm password do not match.</p>';
        }
    } else {
        $message = '<p class="success-message">Profile updated successfully!</p>';
    }

    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/'; // Ensure this directory exists and is writable
        $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $new_file_name = 'profile_' . $_SESSION['user_id'] . '.' . $file_extension; // Use user ID for unique name
        $file_path = $upload_dir . $new_file_name;

        if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $file_path)) {
            $currentUser['profile_photo'] = $file_path; // Update photo path
            $message .= '<p class="success-message">Profile photo updated.</p>';
        } else {
            $message .= '<p class="error-message">Failed to upload profile photo.</p>';
        }
    }
    // In a real app: Update the user data in the database
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <style>
        /* Inline CSS for Settings */
        .settings-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .settings-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .profile-card {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .profile-card .profile-photo-container {
            position: relative;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #00d1d1;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .profile-card .profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-card h3 {
            font-size: 24px;
            color: #031b5c;
            margin-bottom: 10px;
        }
        .profile-card p {
            font-size: 16px;
            color: #555;
        }
        .settings-form {
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box;
            background-color: #fff;
            transition: border-color 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: #00d1d1;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 209, 209, 0.2);
        }
        .form-group input[type="file"] {
            padding-top: 8px;
        }
        .form-actions {
            margin-top: 30px;
            text-align: right;
        }
        .form-actions button {
            background-color: #00d1d1;
            color: #031b5c;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
            font-weight: bold;
        }
        .form-actions button:hover {
            background-color: #00b3b3;
            transform: translateY(-2px);
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="settings-content">
        <h2>User Settings</h2>

        <div class="profile-card">
            <div class="profile-photo-container">
                <img src="<?php echo htmlspecialchars($currentUser['profile_photo']); ?>" alt="Profile Photo" class="profile-photo">
            </div>
            <h3><?php echo htmlspecialchars($currentUser['name']); ?></h3>
            <p><?php echo htmlspecialchars($currentUser['email']); ?></p>
        </div>

        <?php echo $message; // Display success/error messages ?>

        <form class="settings-form" action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="profile_photo">Profile Photo (optional):</label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                <small style="color: #666;">Upload a new profile picture. Max size 2MB.</small>
            </div>

            <div class="form-group">
                <label for="password">New Password (optional):</label>
                <input type="password" id="password" name="password" placeholder="Leave blank to keep current password">
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password">
            </div>

            <div class="form-actions">
                <button type="submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</body>
</html>