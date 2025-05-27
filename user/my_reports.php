<?php
// my_reports.php
// This file lists all incidents reported by the logged-in user.
// It will be included by user.php, so config.php and session are already handled.

// Assume $conn is available from config.php (database connection)
// Assume user_id is available from session, e.g., $_SESSION['user_id']
// For demonstration, let's set a dummy user_id if session isn't fully implemented yet
if (!isset($_SESSION['user_id'])) {
    // IMPORTANT: In a real application, this part MUST be secured with proper authentication.
    // This is just for demonstration if you haven't implemented login yet.
    // Replace with actual session user ID after successful authentication.
    $_SESSION['user_id'] = 1; // Dummy user ID for testing
}
$loggedInUserId = $_SESSION['user_id'];

$message = '';
$isError = false;

// --- Pagination Logic ---
$recordsPerPage = 10; // Number of incidents to display per page
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
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
    $userIncidents = $incidentsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching user incidents for My Reports: " . $e->getMessage());
    $message = 'Error loading your incident reports. Please try again later.';
    $isError = true;
    $userIncidents = []; // Ensure incidents array is empty on error
    $totalPages = 1; // Prevent division by zero if no records
}

// --- Report Details View (if 'view_report_id' is set) - This section is typically in report_incident.php,
//     but included here for completeness if you decide to show details directly in my_reports.php
//     The 'Action' button in the table currently links to report_incident.php.
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
        }

    } catch (PDOException $e) {
        error_log("Error fetching report details for My Reports: " . $e->getMessage());
        $message = 'Error loading report details. Please try again later.';
        $isError = true;
    }
}

?>

<style>
    /* Styles for the main container of this page */
    .my-reports-container {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-left: 5px solid #3498db; /* Accent border */
    }

    .my-reports-container h1, .my-reports-container h2 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    /* Message display styles (consistent with other pages) */
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

    /* Status badges */
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


    /* Action button for viewing details */
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

    /* Report Details Specific Styles (Copied from report_incident.php for consistency if you decide to display here) */
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

    @media (max-width: 768px) {
        .reports-table th, .reports-table td {
            padding: 8px;
            font-size: 0.9em;
        }
        .reports-table th:nth-child(3), .reports-table td:nth-child(3), /* Hide system_affected */
        .reports-table th:nth-child(4), .reports-table td:nth-child(4) { /* Hide severity */
            display: none;
        }
    }
</style>

<div class="content-area">
    <div class="my-reports-container">
        <h1>My Incident Reports</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $isError ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($reportDetails): // Display report details if a report ID is passed to this page ?>
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

            <a href="?page=my_reports" class="back-to-reports-btn">Back to All Reports</a>
        <?php else: // Display the table if no specific report is being viewed ?>

            <?php if (!empty($userIncidents)): ?>
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
                        <?php foreach ($userIncidents as $incident): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($incident['id']); ?></td>
                                <td><?php echo htmlspecialchars($incident['incident_type']); ?></td>
                                <td><?php echo htmlspecialchars($incident['system_affected']); ?></td>
                                <td><?php echo htmlspecialchars($incident['severity']); ?></td>
                                <td><span class="status-badge <?php echo htmlspecialchars($incident['status']); ?>"><?php echo htmlspecialchars($incident['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($incident['created_at']))); ?></td>
                                <td>
                                    <a href="?page=my_reports&view_report_id=<?php echo htmlspecialchars($incident['id']); ?>" class="view-btn">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=my_reports&page_num=<?php echo $currentPage - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=my_reports&page_num=<?php echo $i; ?>" class="<?php echo ($i === $currentPage) ? 'current-page' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=my_reports&page_num=<?php echo $currentPage + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p>You have not reported any incidents yet.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>