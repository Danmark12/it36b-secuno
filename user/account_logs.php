<?php
// account_logs.php
// This file allows the logged-in user to view their activity logs.
// It will be included by user.php, so config.php and session are already handled.

// Assume $conn is available from config.php (database connection)
// Assume user_id is available from session, e.g., $_SESSION['user_id']

if (!isset($_SESSION['user_id'])) {
    // Redirect to login or show an error if not logged in
    // IMPORTANT: In a real application, this MUST be secured with proper authentication.
    // This is for demonstration if you haven't implemented login yet.
    $_SESSION['user_id'] = 1; // Dummy user ID for testing
}
$loggedInUserId = $_SESSION['user_id'];

$message = '';
$isError = false;

// --- Pagination Logic ---
$recordsPerPage = 20; // Number of logs to display per page
$currentPage = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
if ($currentPage < 1) $currentPage = 1;

$offset = ($currentPage - 1) * $recordsPerPage;

$userActivityLogs = [];
$totalRecords = 0;
$totalPages = 1;

try {
    // Get total number of logs for the current user
    $totalLogsStmt = $conn->prepare("SELECT COUNT(*) FROM user_activity_logs WHERE user_id = ?");
    $totalLogsStmt->execute([$loggedInUserId]);
    $totalRecords = $totalLogsStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);

    // Fetch activity logs for the current page, ordered by most recent
    $logsStmt = $conn->prepare("SELECT * FROM user_activity_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT ?, ?");
    $logsStmt->bindValue(1, $loggedInUserId, PDO::PARAM_INT);
    $logsStmt->bindValue(2, $offset, PDO::PARAM_INT);
    $logsStmt->bindValue(3, $recordsPerPage, PDO::PARAM_INT);
    $logsStmt->execute();
    $userActivityLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching user activity logs: " . $e->getMessage());
    $message = 'Error loading your activity logs. Please try again later.';
    $isError = true;
}

?>

<style>
    /* Styles for the main container of this page */
    .account-logs-container {
        background-color: #ffffff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border-left: 5px solid #6c757d; /* Grey accent border for logs */
    }

    .account-logs-container h1 {
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

    /* Table styles */
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .logs-table th, .logs-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
        vertical-align: top; /* Align content to top for multi-line descriptions */
    }

    .logs-table th {
        background-color: #f2f2f2;
        color: #333;
        font-weight: bold;
    }

    .logs-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .logs-table tr:hover {
        background-color: #f1f1f1;
    }

    /* Pagination styles (reusing existing for consistency) */
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

    @media (max-width: 768px) {
        .logs-table th, .logs-table td {
            padding: 8px;
            font-size: 0.9em;
        }
        /* Hide some columns on smaller screens for better readability */
        .logs-table th:nth-child(4), .logs-table td:nth-child(4), /* IP Address */
        .logs-table th:nth-child(5), .logs-table td:nth-child(5) { /* User Agent */
            display: none;
        }
    }
</style>

<div class="content-area">
    <div class="account-logs-container">
        <h1>My Account Activity Logs</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $isError ? 'error' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($userActivityLogs)): ?>
            <table class="logs-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Activity Type</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userActivityLogs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['timestamp']))); ?></td>
                            <td><?php echo htmlspecialchars($log['activity_type']); ?></td>
                            <td><?php echo htmlspecialchars($log['description'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($log['user_agent'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=account_logs&page_num=<?php echo $currentPage - 1; ?>">&laquo; Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=account_logs&page_num=<?php echo $i; ?>" class="<?php echo ($i === $currentPage) ? 'current-page' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=account_logs&page_num=<?php echo $currentPage + 1; ?>">Next &raquo;</a>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <p>No activity logs found for your account.</p>
        <?php endif; ?>
    </div>
</div>