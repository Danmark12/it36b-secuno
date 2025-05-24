<?php
// account_logs.php
session_start();
// require '../db/config.php';

// In a real app, you'd fetch logs associated with the current user's ID
$logs = [
    ['timestamp' => '2025-05-24 10:05 AM', 'activity' => 'Logged in from IP: 192.168.1.100 (Successful)'],
    ['timestamp' => '2025-05-24 10:07 AM', 'activity' => 'Submitted new incident report (ID: 00125)'],
    ['timestamp' => '2025-05-23 03:20 PM', 'activity' => 'Updated profile information (Email changed)'],
    ['timestamp' => '2025-05-23 03:10 PM', 'activity' => 'Failed login attempt from IP: 203.0.113.50 (Incorrect Password)'],
    ['timestamp' => '2025-05-23 03:00 PM', 'activity' => 'Logged in from IP: 192.168.1.100 (Successful)'],
    ['timestamp' => '2025-05-22 09:45 AM', 'activity' => 'Viewed Report Details (ID: 00123)'],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Logs</title>
    <style>
        /* Inline CSS for Account Logs */
        .account-logs-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .account-logs-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .logs-list {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .logs-list ul {
            list-style: none;
            padding: 0;
        }
        .logs-list li {
            display: flex;
            align-items: flex-start; /* Align icon and text at the top */
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .logs-list li:last-child {
            border-bottom: none;
        }
        .log-icon {
            font-size: 20px;
            color: #00d1d1;
            flex-shrink: 0; /* Prevent icon from shrinking */
            margin-top: 2px; /* Small adjustment for alignment */
        }
        .log-details {
            flex-grow: 1;
            font-size: 16px;
            color: #333;
        }
        .log-details .timestamp {
            font-weight: bold;
            color: #031b5c;
            margin-bottom: 5px;
            display: block;
        }
        .log-details .activity {
            line-height: 1.5;
        }
        /* Specific icons for different log types (optional, based on activity text) */
        .log-details .activity:contains("Logged in") .log-icon { color: #28a745; } /* Green for successful login */
        .log-details .activity:contains("Failed login") .log-icon { color: #dc3545; } /* Red for failed login */
        .log-details .activity:contains("Submitted") .log-icon { color: #00d1d1; }
        .log-details .activity:contains("Updated profile") .log-icon { color: #ffc107; }
        .no-logs {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="account-logs-content">
        <h2>My Account Activity Logs</h2>

        <?php if (empty($logs)): ?>
            <div class="no-logs">
                <p><i class="fas fa-box-open"></i> No activity logs found for your account.</p>
            </div>
        <?php else: ?>
            <div class="logs-list">
                <ul>
                    <?php foreach ($logs as $log): ?>
                        <li>
                            <span class="log-icon">
                                <?php 
                                    // Dynamically choose icon based on activity text
                                    if (strpos($log['activity'], 'Logged in') !== false && strpos($log['activity'], '(Successful)') !== false) {
                                        echo '<i class="fas fa-sign-in-alt"></i>';
                                    } elseif (strpos($log['activity'], 'Failed login') !== false) {
                                        echo '<i class="fas fa-exclamation-triangle"></i>';
                                    } elseif (strpos($log['activity'], 'Submitted') !== false) {
                                        echo '<i class="fas fa-file-upload"></i>';
                                    } elseif (strpos($log['activity'], 'Updated profile') !== false) {
                                        echo '<i class="fas fa-user-edit"></i>';
                                    } elseif (strpos($log['activity'], 'Viewed Report Details') !== false) {
                                        echo '<i class="fas fa-eye"></i>';
                                    } else {
                                        echo '<i class="fas fa-info-circle"></i>'; // Default icon
                                    }
                                ?>
                            </span>
                            <div class="log-details">
                                <span class="timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></span>
                                <span class="activity"><?php echo htmlspecialchars($log['activity']); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>