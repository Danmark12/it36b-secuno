<?php
// settings.php
// This file allows a logged-in user to update their profile settings.
require_once '../db/config.php'; // Includes database connection and session start
// Assuming logUserActivity is in config.php or a file included by it.

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$isError = false;

// Define allowed file types and max size for profile photos
const ALLOWED_PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
const MAX_PHOTO_SIZE = 2 * 1024 * 1024; // 2 MB in bytes

// Fetch current user data to pre-fill the form
$current_user = [];
try {
    // Crucial: Fetch 'password_hash' for password comparison
    $stmt = $conn->prepare("SELECT name, email, profile_photo_path, password_hash FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) {
        // User not found in DB despite being in session - potential issue or deleted user
        error_log("Security Alert: User ID " . $user_id . " in session but not found in database during settings access.");
        // Log this unusual activity
        logUserActivity($conn, $user_id, 'Security Alert', 'User ID in session not found in DB during settings access.');
        header('Location: logout.php'); // Force logout for security
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user data for settings (User ID: " . $user_id . "): " . $e->getMessage());
    $message = "Error loading user data. Please try again later.";
    $isError = true;
    // Log the failure to fetch user data for audit
    logUserActivity($conn, $user_id, 'Profile Load Failed', 'Database error while loading user profile for settings: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isError) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'Invalid CSRF token. Please try again.';
        $isError = true;
        logUserActivity($conn, $user_id, 'CSRF Token Mismatch', 'Invalid CSRF token detected on settings update.');
    } else {
        $new_name = trim($_POST['name'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $current_password_input = $_POST['current_password'] ?? ''; // Input from form
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $updates = [];
        $params = [];
        $log_description_parts = [];

        try {
            $conn->beginTransaction();

            // Validate Name
            if (empty($new_name)) {
                $isError = true;
                $message = 'Name cannot be empty.';
            } elseif ($new_name !== $current_user['name']) {
                $updates[] = "name = ?";
                $params[] = $new_name;
                $log_description_parts[] = "Name changed to " . htmlspecialchars($new_name);
            }

            // Validate Email
            if (empty($new_email)) {
                $isError = true;
                $message = 'Email cannot be empty.';
            } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $isError = true;
                $message = 'Invalid email format.';
            } elseif ($new_email !== $current_user['email']) {
                // Check if new email already exists (excluding current user)
                $stmt_check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
                $stmt_check_email->execute([$new_email, $user_id]);
                if ($stmt_check_email->fetch()) {
                    $isError = true;
                    $message = 'This email is already registered to another account.';
                } else {
                    $updates[] = "email = ?";
                    $params[] = $new_email;
                    $log_description_parts[] = "Email changed to " . htmlspecialchars($new_email);
                }
            }

            // Password Update Logic
            if (!empty($new_password)) {
                if (empty($current_password_input)) {
                    $isError = true;
                    $message = 'Current password is required to change password.';
                } elseif (!password_verify($current_password_input, $current_user['password_hash'])) {
                    $isError = true;
                    $message = 'Current password is incorrect.';
                    // Log failed password change attempt
                    logUserActivity($conn, $user_id, 'Password Change Failed', 'Incorrect current password provided during password change attempt.');
                } elseif (strlen($new_password) < 8) {
                    $isError = true;
                    $message = 'New password must be at least 8 characters long.';
                } elseif ($new_password !== $confirm_password) {
                    $isError = true;
                    $message = 'New password and confirm password do not match.';
                } else {
                    // Hash the new password and add to updates
                    $updates[] = "password_hash = ?";
                    $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                    $log_description_parts[] = "Password changed";
                }
            }

            // Profile Photo Upload Logic
            $profile_photo_updated = false;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES['profile_photo']['tmp_name'];
                    $file_type = $_FILES['profile_photo']['type'];
                    $file_size = $_FILES['profile_photo']['size'];
                    $original_file_name = basename($_FILES['profile_photo']['name']);

                    // Validate file type and size
                    if (!in_array($file_type, ALLOWED_PHOTO_TYPES)) {
                        $isError = true;
                        $message = "Unsupported profile photo type. Allowed types: JPEG, PNG, GIF.";
                        logUserActivity($conn, $user_id, 'Profile Photo Upload Failed', 'Unsupported file type for profile photo: ' . htmlspecialchars($file_type));
                    } elseif ($file_size > MAX_PHOTO_SIZE) {
                        $isError = true;
                        $message = "Profile photo exceeds the maximum size of " . (MAX_PHOTO_SIZE / (1024 * 1024)) . "MB.";
                        logUserActivity($conn, $user_id, 'Profile Photo Upload Failed', 'File size too large for profile photo: ' . htmlspecialchars($file_size));
                    }

                    if (!$isError) {
                        $upload_directory = 'uploads/profile_photos/';
                        if (!is_dir($upload_directory)) {
                            mkdir($upload_directory, 0755, true);
                        }

                        $file_extension = pathinfo($original_file_name, PATHINFO_EXTENSION);
                        $new_photo_name = uniqid('profile_', true) . '.' . $file_extension;
                        $target_file_path = $upload_directory . $new_photo_name;

                        if (move_uploaded_file($file_tmp_name, $target_file_path)) {
                            // Delete old profile photo if it's not the default and exists
                            if ($current_user['profile_photo_path'] && $current_user['profile_photo_path'] !== 'uploads/profile_photos/default.png' && file_exists($current_user['profile_photo_path'])) {
                                unlink($current_user['profile_photo_path']);
                            }
                            $updates[] = "profile_photo_path = ?";
                            $params[] = $target_file_path;
                            $profile_photo_updated = true;
                            $log_description_parts[] = "Profile photo updated";
                        } else {
                            $isError = true;
                            $message = "Failed to upload profile photo. Please check directory permissions.";
                            error_log("Failed to move uploaded profile photo: " . $file_tmp_name . " to " . $target_file_path);
                            logUserActivity($conn, $user_id, 'Profile Photo Upload Failed', 'Failed to move uploaded file.');
                        }
                    }
                } else {
                    // Specific handling for common PHP upload errors
                    $upload_error_message = 'An unknown file upload error occurred.';
                    switch ($_FILES['profile_photo']['error']) {
                        case UPLOAD_ERR_INI_SIZE:
                            $upload_error_message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
                            break;
                        case UPLOAD_ERR_FORM_SIZE:
                            $upload_error_message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                            break;
                        case UPLOAD_ERR_PARTIAL:
                            $upload_error_message = 'The uploaded file was only partially uploaded.';
                            break;
                        case UPLOAD_ERR_NO_TMP_DIR:
                            $upload_error_message = 'Missing a temporary folder.';
                            break;
                        case UPLOAD_ERR_CANT_WRITE:
                            $upload_error_message = 'Failed to write file to disk.';
                            break;
                        case UPLOAD_ERR_EXTENSION:
                            $upload_error_message = 'A PHP extension stopped the file upload.';
                            break;
                        // UPLOAD_ERR_NO_FILE handled by the outer if condition
                    }
                    $isError = true;
                    $message = "Profile photo upload error: " . $upload_error_message;
                    error_log("Profile photo upload error for user ID " . $user_id . ": " . $upload_error_message);
                    logUserActivity($conn, $user_id, 'Profile Photo Upload Failed', 'PHP upload error code ' . $_FILES['profile_photo']['error'] . ': ' . $upload_error_message);
                }
            }

            // Only attempt database update if no errors and there are changes to make
            if (!$isError && !empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $params[] = $user_id;

                $stmt = $conn->prepare($sql);
                $stmt->execute($params);

                $conn->commit();
                $message = "Profile updated successfully!";
                $isError = false; // Reset to false for success message

                // Update session variables if changed
                if (in_array("email = ?", $updates)) {
                    $_SESSION['user_email'] = $new_email;
                }
                if (in_array("name = ?", $updates)) {
                    $_SESSION['user_name'] = $new_name; // Assuming you store user_name in session
                }
                // Update current_user array for displaying latest info on next load or if form is re-rendered
                $current_user['name'] = $new_name;
                $current_user['email'] = $new_email;
                if ($profile_photo_updated) {
                    $current_user['profile_photo_path'] = $target_file_path;
                }
                // Important: If password was changed, the stored hash in $current_user should also be updated
                // to reflect the new hash, in case subsequent operations rely on it in the same request.
                if (in_array("password_hash = ?", $updates)) {
                    $current_user['password_hash'] = password_hash($new_password, PASSWORD_BCRYPT);
                }


                // Log the successful update
                logUserActivity($conn, $user_id, 'Profile Updated', 'User updated profile: ' . implode(', ', $log_description_parts));

            } elseif (!$isError && empty($updates)) {
                $message = "No changes submitted.";
                $isError = false; // Still a success, just no action taken
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            error_log("Profile update PDO error (User ID: " . $user_id . "): " . $e->getMessage());
            $message = 'An error occurred while updating your profile. Please try again.';
            $isError = true;
            logUserActivity($conn, $user_id, 'Profile Update Failed - DB Error', 'Database error during profile update: ' . $e->getMessage());
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Profile update general error (User ID: " . $user_id . "): " . $e->getMessage());
            $message = 'An unexpected error occurred. Please try again later.';
            $isError = true;
            logUserActivity($conn, $user_id, 'Profile Update Failed - General Error', 'Unexpected error during profile update: ' . $e->getMessage());
        }
    }
    // Regenerate CSRF token after form submission
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
} else {
    // Generate CSRF token on initial page load
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get the current CSRF token value for the form
$csrf_token_value = $_SESSION['csrf_token'];
?>

<style>
    .settings-container {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-left: 5px solid #007bff; /* Blue accent border */
    }

    .settings-container h1 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .message {
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: bold;
        display: <?php echo (!empty($message) ? 'block' : 'none'); ?>;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .profile-photo-preview {
        text-align: center;
        margin-bottom: 20px;
    }
    .profile-photo-preview img {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #ddd;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"] {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 16px;
        box-sizing: border-box;
    }

    .form-group input[type="file"] {
        padding: 8px 0;
    }

    .form-group button {
        background-color: #00004d;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 5px;
        font-size: 17px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .form-group button:hover {
        background-color: #001a66;
    }
</style>

<div class="content-area">
    <div class="settings-container">
        <h1>Account Settings</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $isError ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($current_user)): ?>
            <form action="?page=settings" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token_value); ?>">

                <div class="profile-photo-preview">
                    <img src="<?php echo htmlspecialchars($current_user['profile_photo_path'] ?: 'uploads/profile_photos/default.png'); ?>" alt="Profile Photo">
                </div>

                <div class="form-group">
                    <label for="profile_photo">Change Profile Photo:</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept=".jpeg,.jpg,.png,.gif">
                    <small>Max 2MB. Allowed types: JPEG, PNG, GIF.</small>
                </div>

                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" required>
                </div>

                <h2>Change Password</h2>
                <div class="form-group">
                    <label for="current_password">Current Password (required to change password):</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Enter current password">
                </div>

                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Enter new password (min 8 characters)">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                </div>

                <div class="form-group">
                    <button type="submit">Update Profile</button>
                </div>
            </form>
        <?php else: ?>
            <p>Unable to load your profile settings. Please try logging in again.</p>
        <?php endif; ?>
    </div>
</div>