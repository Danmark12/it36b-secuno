<?php
// report_incident.php
session_start();
// require '../db/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you'd collect and sanitize all POST data
    $type = $_POST['incident_type'] ?? '';
    $system = $_POST['system_affected'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $dateTime = $_POST['date_time'] ?? '';
    $description = $_POST['description'] ?? '';

    // Handle file uploads
    $attachments = [];
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        foreach ($_FILES['attachments']['name'] as $key => $name) {
            if ($_FILES['attachments']['error'][$key] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['attachments']['tmp_name'][$key];
                $upload_dir = 'uploads/'; // Ensure this directory exists and is writable
                $file_path = $upload_dir . basename($name);
                if (move_uploaded_file($tmp_name, $file_path)) {
                    $attachments[] = $file_path;
                }
            }
        }
    }

    // Here you would insert data into your database
    // Example: $stmt = $pdo->prepare("INSERT INTO incidents (...) VALUES (...)");
    // $stmt->execute([...]);

    $message = '<p class="success-message">Incident reported successfully!</p>';
    // You might want to redirect to 'my_reports.php' after submission
    // header("Location: my_reports.php");
    // exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident</title>
    <style>
        /* Inline CSS for Report Incident */
        .report-incident-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .report-incident-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .report-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            box-sizing: border-box; /* Include padding in element's total width and height */
            background-color: #fff;
            transition: border-color 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="datetime-local"]:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #00d1d1;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 209, 209, 0.2);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group input[type="file"] {
            padding: 10px 0;
            font-size: 16px;
        }
        .form-actions {
            margin-top: 20px;
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
    </style>
</head>
<body>
    <div class="report-incident-content">
        <h2>Submit New Security Incident</h2>

        <?php echo $message; // Display success/error messages ?>

        <form class="report-form" action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="incident_type">Incident Type:</label>
                <select id="incident_type" name="incident_type" required>
                    <option value="">Select Type</option>
                    <option value="Phishing">Phishing</option>
                    <option value="Malware">Malware</option>
                    <option value="Data Breach">Data Breach</option>
                    <option value="Unauthorized Access">Unauthorized Access</option>
                    <option value="DDoS Attack">DDoS Attack</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="system_affected">System/Service Affected:</label>
                <input type="text" id="system_affected" name="system_affected" placeholder="e.g., User Database, Web Server, Email Service" required>
            </div>

            <div class="form-group">
                <label for="severity">Severity:</label>
                <select id="severity" name="severity" required>
                    <option value="">Select Severity</option>
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>

            <div class="form-group">
                <label for="date_time">Date/Time of Incident:</label>
                <input type="datetime-local" id="date_time" name="date_time" required>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" placeholder="Provide a detailed description of the incident, including what happened, when, and any observed symptoms." rows="6" required></textarea>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments (optional):</label>
                <input type="file" id="attachments" name="attachments[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                <small style="color: #666;">Max file size 5MB. Allowed: PDF, DOC, JPG, PNG.</small>
            </div>

            <div class="form-actions">
                <button type="submit"><i class="fas fa-paper-plane"></i> Submit Incident</button>
            </div>
        </form>
    </div>
</body>
</html>