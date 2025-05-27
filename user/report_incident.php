<?php
// This file will handle both incident reporting and displaying existing incidents.
// It will be included by user.php, so config.php and session are already handled.

// Assume $conn is available from config.php (database connection)
// Assume user_id is available from session, e.g., $_SESSION['user_id']
// For demonstration, let's set a dummy user_id if session isn't fully implemented yet
if (!isset($_SESSION['user_id'])) {
    // IMPORTANT: In a real application, this part MUST be secured.
    // This is just for demonstration if you haven't implemented login yet.
    // Replace with actual session user ID after successful authentication.
    $_SESSION['user_id'] = 1; // Dummy user ID for testing
}
$loggedInUserId = $_SESSION['user_id'];

$message = '';
$isError = false;

// --- Function to log user activity ---
// This function needs to be defined if it's not in a global include like config.php
// For now, I'm including it here for completeness based on the previous conversation.
if (!function_exists('logUserActivity')) {
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
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null; // Capture User Agent
        try {
            $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, activity_type, description, ip_address, user_agent) VALUES (:user_id, :activity_type, :description, :ip_address, :user_agent)");
            $stmt->execute([
                'user_id' => $user_id,
                'activity_type' => $activity_type,
                'description' => $description,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ]);
        } catch (PDOException $e) {
            // Log the error but don't stop execution or expose details to the user
            error_log("Failed to log user activity: " . $e->getMessage());
        }
    }
}


// --- Handle Incident Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_incident'])) {
    $incident_type = $_POST['incident_type'] ?? '';
    $system_affected = $_POST['system_affected'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $incident_date_time = $_POST['incident_date_time'] ?? '';
    $description = $_POST['description'] ?? '';

    // Basic Validation (enhance as needed)
    if (empty($incident_type) || empty($system_affected) || empty($severity) || empty($incident_date_time) || empty($description)) {
        $message = 'All required text fields must be filled out to report an incident.';
        $isError = true;
        // Log this validation failure
        logUserActivity($conn, $loggedInUserId, 'Report Submission Failed', 'Attempted incident submission with missing required fields.');
    } else {
        try {
            $conn->beginTransaction(); // Start a transaction for incident and attachments

            $stmt = $conn->prepare("INSERT INTO incidents (user_id, incident_type, system_affected, severity, incident_date_time, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$loggedInUserId, $incident_type, $system_affected, $severity, $incident_date_time, $description]);

            $incidentId = $conn->lastInsertId(); // Get the ID of the newly inserted incident

            // After successful incident submission:
            // After the incident data is successfully inserted into the 'incidents' table
            logUserActivity($conn, $_SESSION['user_id'], 'Report Submitted', 'New incident reported (ID: ' . $incidentId . ').');


            // --- Handle File Attachments ---
            if ($incidentId && isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $targetDir = "uploads/attachments/"; // Define your upload directory

                // Create directory if it doesn't exist
                if (!is_dir($targetDir)) {
                    if (!mkdir($targetDir, 0775, true)) { // Use 0775 for better security than 0777
                        throw new Exception("Failed to create upload directory: " . $targetDir);
                    }
                }

                // Allowed file types and max file size
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'log'];
                $maxFileSize = 10 * 1024 * 1024; // 10MB (adjust as needed)
                $maxFiles = 5; // Maximum number of files allowed

                $uploadedCount = 0;
                $attachmentsMessage = [];

                foreach ($_FILES['attachments']['name'] as $key => $fileName) {
                    // Skip if no file was actually selected for this input slot
                    if (empty($fileName)) {
                        continue;
                    }

                    $fileTmpName = $_FILES['attachments']['tmp_name'][$key];
                    $fileSize = $_FILES['attachments']['size'][$key];
                    $fileError = $_FILES['attachments']['error'][$key];
                    $fileType = $_FILES['attachments']['type'][$key];

                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    // Check for maximum files limit
                    if ($uploadedCount >= $maxFiles) {
                        $attachmentsMessage[] = 'Maximum ' . $maxFiles . ' files allowed. Skipped ' . htmlspecialchars($fileName) . '.';
                        $isError = true;
                        logUserActivity($conn, $loggedInUserId, 'Attachment Upload Failed', 'Exceeded max file limit (' . $maxFiles . ') for incident ' . $incidentId . '. Skipped: ' . htmlspecialchars($fileName));
                        continue;
                    }

                    if ($fileError === 0) { // UPLOAD_ERR_OK
                        if (in_array($fileExt, $allowedTypes)) {
                            if ($fileSize < $maxFileSize) {
                                // Generate a unique filename to prevent conflicts and security issues
                                $newFileName = uniqid('attach_', true) . '.' . $fileExt;
                                $filePath = $targetDir . $newFileName;

                                if (move_uploaded_file($fileTmpName, $filePath)) {
                                    // Insert attachment info into database
                                    $attachStmt = $conn->prepare("INSERT INTO incident_attachments (incident_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
                                    $attachStmt->execute([$incidentId, htmlspecialchars($fileName), $filePath, $fileType, $fileSize]);
                                    $uploadedCount++;
                                    logUserActivity($conn, $loggedInUserId, 'Attachment Upload Success', 'Attachment ' . htmlspecialchars($fileName) . ' uploaded successfully for incident ' . $incidentId . '.');
                                } else {
                                    $attachmentsMessage[] = 'Failed to move uploaded file: ' . htmlspecialchars($fileName) . '. Please check server permissions.';
                                    error_log("Failed to move uploaded file: " . $fileTmpName . " to " . $filePath);
                                    $isError = true;
                                    logUserActivity($conn, $loggedInUserId, 'Attachment Upload Failed', 'Failed to move uploaded file ' . htmlspecialchars($fileName) . ' for incident ' . $incidentId . '.');
                                }
                            } else {
                                $attachmentsMessage[] = 'File ' . htmlspecialchars($fileName) . ' is too large (max ' . ($maxFileSize / (1024 * 1024)) . 'MB).';
                                $isError = true;
                                logUserActivity($conn, $loggedInUserId, 'Attachment Upload Failed', 'File ' . htmlspecialchars($fileName) . ' too large for incident ' . $incidentId . '.');
                            }
                        } else {
                            $attachmentsMessage[] = 'File ' . htmlspecialchars($fileName) . ' has an unsupported type. Allowed types: ' . implode(', ', $allowedTypes) . '.';
                            $isError = true;
                            logUserActivity($conn, $loggedInUserId, 'Attachment Upload Failed', 'Unsupported file type for ' . htmlspecialchars($fileName) . ' for incident ' . $incidentId . '.');
                        }
                    } else {
                        // Handle specific upload errors
                        $uploadErrors = [
                            UPLOAD_ERR_INI_SIZE   => "The uploaded file exceeds the upload_max_filesize directive in php.ini.",
                            UPLOAD_ERR_FORM_SIZE  => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",
                            UPLOAD_ERR_PARTIAL    => "The uploaded file was only partially uploaded.",
                            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
                            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder.",
                            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the file upload.",
                        ];
                        $errorMessage = ($uploadErrors[$fileError] ?? 'Unknown error ' . $fileError);
                        $attachmentsMessage[] = 'Error uploading ' . htmlspecialchars($fileName) . ': ' . $errorMessage . '.';
                        error_log("File upload error for " . $fileName . ": " . $errorMessage);
                        $isError = true;
                        logUserActivity($conn, $loggedInUserId, 'Attachment Upload Failed', 'Error ' . $fileError . ' uploading ' . htmlspecialchars($fileName) . ' for incident ' . $incidentId . ': ' . $errorMessage);
                    }
                }

                if (!empty($attachmentsMessage)) {
                    $message = 'Incident reported. However, some issues occurred with attachments:<br>' . implode('<br>', $attachmentsMessage);
                } else {
                    $message = 'Incident reported successfully with attachments!';
                }
            } else {
                $message = 'Incident reported successfully!';
            }
            $conn->commit(); // Commit the transaction
        } catch (Exception $e) {
            $conn->rollBack(); // Rollback on any error
            error_log("Error reporting incident or uploading attachments: " . $e->getMessage());
            $message = 'An error occurred while reporting the incident or uploading attachments. Please try again.';
            $isError = true;
            logUserActivity($conn, $loggedInUserId, 'Incident Report Error', 'Critical error during incident report or attachment upload for user: ' . $loggedInUserId . '. Error: ' . $e->getMessage());
        }
    }
}

// --- Pagination Logic for Incident Reports Table ---
$recordsPerPage = 10; // Number of incidents to display per page
$currentPage = isset($_GET['incident_page']) ? (int)$_GET['incident_page'] : 1;
if ($currentPage < 1) $currentPage = 1; // Ensure page is not less than 1

$offset = ($currentPage - 1) * $recordsPerPage;

try {
    // Get total number of incidents for the current user
    $totalIncidentsStmt = $conn->prepare("SELECT COUNT(*) FROM incidents WHERE user_id = ?");
    $totalIncidentsStmt->execute([$loggedInUserId]);
    $totalRecords = $totalIncidentsStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // Fetch incidents for the current page, ordered by most recent
    $incidentsStmt = $conn->prepare("SELECT * FROM incidents WHERE user_id = ? ORDER BY created_at DESC LIMIT ?, ?");
    $incidentsStmt->bindValue(1, $loggedInUserId, PDO::PARAM_INT);
    $incidentsStmt->bindValue(2, $offset, PDO::PARAM_INT);
    $incidentsStmt->bindValue(3, $recordsPerPage, PDO::PARAM_INT);
    $incidentsStmt->execute();
    $incidents = $incidentsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching incidents: " . $e->getMessage());
    $message = 'Error loading incident reports. Please try again later.';
    $isError = true;
    $incidents = []; // Ensure incidents array is empty on error
    $totalPages = 1;
    logUserActivity($conn, $loggedInUserId, 'Data Fetch Error', 'Error fetching incident reports for user: ' . $loggedInUserId . '. Error: ' . $e->getMessage());
}

// --- Report Details View (if 'view_report_id' is set) ---
$reportDetails = null;
if (isset($_GET['view_report_id'])) {
    $viewReportId = (int)$_GET['view_report_id'];
    try {
        $stmt = $conn->prepare("SELECT * FROM incidents WHERE id = ? AND user_id = ?");
        $stmt->execute([$viewReportId, $loggedInUserId]);
        $reportDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch attachments if report details found
        if ($reportDetails) {
            $attachmentsStmt = $conn->prepare("SELECT * FROM incident_attachments WHERE incident_id = ?");
            $attachmentsStmt->execute([$viewReportId]);
            $reportDetails['attachments'] = $attachmentsStmt->fetchAll(PDO::FETCH_ASSOC);
            logUserActivity($conn, $loggedInUserId, 'View Report Details', 'User viewed incident report details (ID: ' . $viewReportId . ').');
        } else {
            // Log attempt to view non-existent or unauthorized report
            logUserActivity($conn, $loggedInUserId, 'View Report Failed', 'Attempted to view non-existent or unauthorized incident report (ID: ' . $viewReportId . ').');
        }

    } catch (PDOException $e) {
        error_log("Error fetching report details: " . $e->getMessage());
        $message = 'Error loading report details. Please try again later.';
        $isError = true;
        logUserActivity($conn, $loggedInUserId, 'Data Fetch Error', 'Error fetching specific incident report (ID: ' . $viewReportId . ') for user: ' . $loggedInUserId . '. Error: ' . $e->getMessage());
    }
}
?>

<style>
    /* General container styles */
    .content-area-section {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-left: 5px solid #3498db; /* Accent border */
    }

    .content-area-section h2 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    /* Form specific styles (used within modal) */
    .form-group {
        margin-bottom: 18px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
        color: #555;
    }

    .form-group input[type="text"],
    .form-group input[type="datetime-local"],
    .form-group select,
    .form-group textarea,
    .form-group input[type="file"] { /* Added file input here */
        width: calc(100% - 22px); /* Adjust for padding and border */
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 1em;
        box-sizing: border-box; /* Include padding and border in the element's total width and height */
    }

    .form-group textarea {
        resize: vertical; /* Allow vertical resizing */
        min-height: 100px;
    }

    .form-group button[type="submit"] {
        background-color: #2ecc71; /* Green submit button */
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1.1em;
        transition: background-color 0.3s ease;
    }

    .form-group button[type="submit"]:hover {
        background-color: #27ae60;
    }

    /* Message display */
    .message {
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: bold;
        display: <?php echo (!empty($message) ? 'block' : 'none'); ?>; /* Show only if message exists */
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

    /* "Add New Report" button (trigger for modal) */
    .add-report-trigger-container {
        text-align: right; /* Align button to the right */
        margin-bottom: 20px;
    }

    .add-report-trigger-btn {
        background-color: #3498db; /* Blue button */
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
        display: inline-flex; /* Use flex for icon and text alignment */
        align-items: center;
        gap: 8px; /* Space between icon and text */
    }

    .add-report-trigger-btn:hover {
        background-color: #2980b9;
    }

    /* Table styles */
    .reports-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .reports-table th, .reports-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }

    .reports-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    .reports-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .reports-table tr:hover {
        background-color: #f1f1f1;
    }

    .reports-table .view-btn {
        background-color: #5bc0de; /* Info blue */
        color: white;
        padding: 6px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        text-decoration: none;
        transition: background-color 0.3s ease;
        display: inline-block; /* For proper padding */
    }

    .reports-table .view-btn:hover {
        background-color: #31b0d5;
    }

    /* Pagination styles */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .pagination a, .pagination span {
        text-decoration: none;
        color: #3498db;
        padding: 8px 12px;
        margin: 0 4px;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .pagination a:hover {
        background-color: #f2f2f2;
        color: #2980b9;
    }

    .pagination .current-page {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
        font-weight: bold;
    }

    /* Report Details Specific Styles */
    .report-details-item {
        margin-bottom: 15px;
    }

    .report-details-item strong {
        display: inline-block;
        width: 150px; /* Align labels */
        font-weight: 600;
        color: #333;
    }

    .report-details-item span {
        color: #666;
    }

    .report-details-item.description, .report-details-item.remarks, .report-details-item.resolution {
        border: 1px solid #eee;
        padding: 10px;
        border-radius: 5px;
        background-color: #fdfdfd;
        white-space: pre-wrap; /* Preserve whitespace and allow wrapping */
        word-wrap: break-word; /* Break long words */
        max-height: 200px; /* Limit height */
        overflow-y: auto; /* Add scroll for overflow */
        margin-top: 5px;
        color: #555;
    }

    .report-details-item ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .report-details-item ul li {
        padding: 5px 0;
        border-bottom: 1px dotted #eee;
    }
    .report-details-item ul li:last-child {
        border-bottom: none;
    }
    .report-details-item a {
        color: #3498db;
        text-decoration: none;
    }
    .report-details-item a:hover {
        text-decoration: underline;
    }

    .back-to-reports-btn {
        background-color: #6c757d; /* Grey button */
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-top: 20px;
    }
    .back-to-reports-btn:hover {
        background-color: #5a6268;
    }

    /* Status badges for tables and detail view */
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        font-weight: 600;
        color: white;
    }

    .status-badge.Pending { background-color: #f0ad4e; } /* Orange */
    .status-badge.Reviewed { background-color: #5bc0de; } /* Info Blue */
    .status-badge.Ongoing { background-color: #0275d8; } /* Primary Blue */
    .status-badge.Resolved { background-color: #5cb85c; } /* Success Green */
    .status-badge.Closed { background-color: #28a745; } /* Darker Green */


    /* Modal Styles */
    .modal {
        display: none; /* Hidden by default */
        position: fixed; /* Stay in place */
        z-index: 1002; /* Sit on top, higher than sidebar */
        left: 0;
        top: 0;
        width: 100%; /* Full width */
        height: 100%; /* Full height */
        overflow: auto; /* Enable scroll if needed */
        background-color: rgba(0,0,0,0.6); /* Black w/ opacity */
        justify-content: center; /* Center horizontally */
        align-items: center; /* Center vertically */
        padding: 20px; /* Padding around modal content */
        box-sizing: border-box; /* Include padding in width/height */
    }

    .modal-content {
        background-color: #fefefe;
        margin: auto; /* Centered */
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        max-width: 600px; /* Max width of the modal */
        width: 100%; /* Responsive width */
        position: relative;
        animation-name: animatetop;
        animation-duration: 0.4s;
    }

    @keyframes animatetop {
        from {top: -300px; opacity: 0}
        to {top: 0; opacity: 1}
    }

    .close-button {
        color: #aaa;
        float: right;
        font-size: 32px;
        font-weight: bold;
        position: absolute;
        top: 10px;
        right: 20px;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .close-button:hover,
    .close-button:focus {
        color: #333;
        text-decoration: none;
        cursor: pointer;
    }

    /* Responsive adjustments for modal */
    @media (max-width: 768px) {
        .modal-content {
            padding: 20px;
            margin: 10px; /* Smaller margin on mobile */
        }
        .form-group input[type="text"],
        .form-group input[type="datetime-local"],
        .form-group select,
        .form-group textarea,
        .form-group input[type="file"] { /* Added file input here */
            width: 100%; /* Full width on smaller screens */
        }
    }
</style>

<div class="content-area">
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $isError ? 'error' : 'success'; ?>">
            <?php echo nl2br(htmlspecialchars($message)); ?>
        </div>
    <?php endif; ?>

    <?php if ($reportDetails): // Display report details if a report ID is passed ?>
        <div class="content-area-section">
            <h2>Incident Report Details (ID: <?php echo htmlspecialchars($reportDetails['id']); ?>)</h2>
            <div class="report-details-item">
                <strong>Incident Type:</strong> <span><?php echo htmlspecialchars($reportDetails['incident_type']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>System Affected:</strong> <span><?php echo htmlspecialchars($reportDetails['system_affected']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Severity:</strong> <span><?php echo htmlspecialchars($reportDetails['severity']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Date/Time:</strong> <span><?php echo htmlspecialchars($reportDetails['incident_date_time']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Status:</strong> <span class="status-badge <?php echo htmlspecialchars($reportDetails['status']); ?>"><?php echo htmlspecialchars($reportDetails['status']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Resolution Status:</strong> <span><?php echo htmlspecialchars($reportDetails['resolution_status']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Reported On:</strong> <span><?php echo htmlspecialchars($reportDetails['created_at']); ?></span>
            </div>
            <div class="report-details-item">
                <strong>Last Updated:</strong> <span><?php echo htmlspecialchars($reportDetails['updated_at']); ?></span>
            </div>

            <div class="report-details-item">
                <strong>Description:</strong>
                <div class="description"><?php echo nl2br(htmlspecialchars($reportDetails['description'])); ?></div>
            </div>

            <div class="report-details-item">
                <strong>Admin Remarks:</strong>
                <div class="remarks"><?php echo nl2br(htmlspecialchars($reportDetails['admin_remarks'] ?? 'N/A')); ?></div>
            </div>

            <div class="report-details-item">
                <strong>Resolution Details:</strong>
                <div class="resolution"><?php echo nl2br(htmlspecialchars($reportDetails['resolution_details'] ?? 'N/A')); ?></div>
            </div>

            <div class="report-details-item">
                <strong>Attachments:</strong>
                <?php if (!empty($reportDetails['attachments'])): ?>
                    <ul>
                        <?php foreach ($reportDetails['attachments'] as $attachment): ?>
                            <li><a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" target="_blank"><i class="fas fa-paperclip"></i> <?php echo htmlspecialchars($attachment['file_name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span>No attachments.</span>
                <?php endif; ?>
            </div>

            <a href="?page=report_incident" class="back-to-reports-btn">Back to All Reports</a>
        </div>
    <?php else: // Display the modal trigger button and the table if no specific report is being viewed ?>

        <div class="add-report-trigger-container">
            <button id="openReportModalBtn" class="add-report-trigger-btn">
                <i class="fas fa-plus-circle"></i> Report New Incident
            </button>
        </div>

        <div class="content-area-section">
            <h2>My Reported Incidents</h2>

            <?php if (!empty($incidents)): ?>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>System Affected</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Reported On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incidents as $incident): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($incident['id']); ?></td>
                                <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                                <td><?php echo htmlspecialchars($incident['system_affected']); ?></td>
                                <td><?php echo htmlspecialchars($incident['severity']); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars($incident['status']); ?>"><?php echo htmlspecialchars($incident['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($incident['created_at']))); ?></td>
                                <td>
                                    <a href="?page=report_incident&view_report_id=<?php echo htmlspecialchars($incident['id']); ?>" class="view-btn">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=report_incident&incident_page=<?php echo $currentPage - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=report_incident&incident_page=<?php echo $i; ?>" class="<?php echo ($i === $currentPage) ? 'current-page' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=report_incident&incident_page=<?php echo $currentPage + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p>No incidents reported yet. Click the "Report New Incident" button above to submit your first incident.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div id="reportIncidentModal" class="modal">
    <div class="modal-content">
        <span class="close-button">&times;</span>
        <h2>Report a New Security Incident</h2>
        <form action="?page=report_incident" method="POST" id="incidentReportForm" enctype="multipart/form-data">
            <div class="form-group">
                <label for="modal_incident_type">Incident Type:</label>
                <select id="modal_incident_type" name="incident_type" required>
                    <option value="">Select Type</option>
                    <option value="Phishing">Phishing</option>
                    <option value="Malware">Malware</option>
                    <option value="Unauthorized Access">Unauthorized Access</option>
                    <option value="Data Leak">Data Leak</option>
                    <option value="DDoS Attack">DDoS Attack</option>
                    <option value="Insider Threat">Insider Threat</option>
                    <option value="System Misconfiguration">System Misconfiguration</option>
                    <option value="Physical Incident">Physical Incident</option>
                    <option value="Ransomware">Ransomware</option>
                    <option value="Software Vulnerability">Software Vulnerability</option>
                    <option value="Hardware Failure">Hardware Failure</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modal_system_affected">System Affected:</label>
                <input type="text" id="modal_system_affected" name="system_affected" placeholder="e.g., User Database, Web Server, HR System" required>
            </div>
            <div class="form-group">
                <label for="modal_severity">Severity:</label>
                <select id="modal_severity" name="severity" required>
                    <option value="">Select Severity</option>
                    <option value="Critical">Critical</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
            <div class="form-group">
                <label for="modal_incident_date_time">Incident Date & Time:</label>
                <input type="datetime-local" id="modal_incident_date_time" name="incident_date_time" required>
            </div>
            <div class="form-group">
                <label for="modal_description">Description:</label>
                <textarea id="modal_description" name="description" placeholder="Provide a detailed description of the incident..." rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label for="modal_attachments">Attachments (Optional):</label>
                <input type="file" id="modal_attachments" name="attachments[]" multiple
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.log">
                <small>Max 5 files, 10MB each. Allowed types: Images, PDFs, Docs, Logs.</small>
            </div>
            <div class="form-group">
                <button type="submit" name="submit_incident">Submit Incident Report</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get the modal, button that opens it, and the <span> element that closes it
        const modal = document.getElementById('reportIncidentModal');
        const openModalBtn = document.getElementById('openReportModalBtn');
        const closeButton = document.querySelector('.close-button');
        const incidentReportForm = document.getElementById('incidentReportForm');

        // When the user clicks the button, open the modal
        openModalBtn.onclick = function() {
            modal.style.display = 'flex'; // Use flex to center content
            // Optionally reset form fields when opening
            incidentReportForm.reset();
            // Set current date/time as default for convenience
            const now = new Date();
            const year = now.getFullYear();
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const day = now.getDate().toString().padStart(2, '0');
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            document.getElementById('modal_incident_date_time').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        // When the user clicks on <span> (x), close the modal
        closeButton.onclick = function() {
            modal.style.display = 'none';
        }

        // When the user clicks anywhere outside of the modal content, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Handle form submission within the modal
        // The PHP will handle the actual submission, but we might want to
        // close the modal and refresh the page on success.
        incidentReportForm.addEventListener('submit', function(event) {
            // The form will submit normally, and PHP will process it.
            // After PHP processes, the page will reload, and the message will be shown.
            // If you want an AJAX submission without page reload, that would require more JS.
            // For now, a full page reload is expected after submission.
            // You can add client-side validation here before submission if desired.
        });

        // If a message is displayed (from a previous submission), ensure the modal is closed
        // This is important because the page reloads after submission.
        const messageDiv = document.querySelector('.message');
        if (messageDiv && messageDiv.style.display === 'block') {
            // Optionally, you could make the modal open again if there was an error,
            // but for simplicity, we'll just ensure it's closed.
            modal.style.display = 'none';
        }

    });
</script>