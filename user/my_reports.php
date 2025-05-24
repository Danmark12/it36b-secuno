<?php
// my_reports.php
session_start();
// require '../db/config.php';

// In a real app, you'd fetch reports associated with the current user's ID
$reports = [
    [
        'id' => '00125',
        'type' => 'Phishing',
        'system' => 'Email System',
        'severity' => 'High',
        'status' => 'Pending',
        'date' => '2025-05-23 10:30 AM'
    ],
    [
        'id' => '00124',
        'type' => 'Malware',
        'system' => 'Endpoint PCs',
        'severity' => 'Medium',
        'status' => 'Reviewed',
        'date' => '2025-05-22 03:15 PM'
    ],
    [
        'id' => '00123',
        'type' => 'Unauthorized Access',
        'system' => 'User Database',
        'severity' => 'Critical',
        'status' => 'Resolved',
        'date' => '2025-05-20 09:00 AM'
    ],
    [
        'id' => '00122',
        'type' => 'DDoS Attack',
        'system' => 'Web Server',
        'severity' => 'Critical',
        'status' => 'Closed',
        'date' => '2025-05-18 01:00 PM'
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports</title>
    <style>
        /* Inline CSS for My Reports */
        .my-reports-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .my-reports-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .reports-table-container {
            overflow-x: auto; /* For responsive tables */
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .reports-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            min-width: 600px; /* Ensures table doesn't get too cramped on small screens */
        }
        .reports-table th, .reports-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .reports-table th {
            background-color: #031b5c;
            color: #fff;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .reports-table tr:nth-child(even) {
            background-color: #f6f6f6;
        }
        .reports-table tr:hover {
            background-color: #eaf8f8;
            cursor: pointer;
        }
        .status-tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 13px;
            text-transform: uppercase;
        }
        .status-tag.pending {
            background-color: #ffe0b2; /* Light orange */
            color: #e65100; /* Darker orange */
        }
        .status-tag.reviewed {
            background-color: #bbdefb; /* Light blue */
            color: #1565c0; /* Darker blue */
        }
        .status-tag.resolved {
            background-color: #c8e6c9; /* Light green */
            color: #2e7d32; /* Darker green */
        }
        .status-tag.closed { /* Assuming 'Closed' is a final status similar to resolved */
            background-color: #b2dfdb; /* Teal */
            color: #00695c; /* Darker teal */
        }
        .view-details-btn {
            background-color: #00d1d1;
            color: #031b5c;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s ease;
            font-weight: bold;
        }
        .view-details-btn:hover {
            background-color: #00b3b3;
        }
        .no-reports {
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
    <div class="my-reports-content">
        <h2>My Submitted Reports</h2>

        <?php if (empty($reports)): ?>
            <div class="no-reports">
                <p><i class="fas fa-box-open"></i> You haven't submitted any reports yet.</p>
                <p>Click "Report Incident" to create a new one!</p>
            </div>
        <?php else: ?>
            <div class="reports-table-container">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Report ID</th>
                            <th>Type</th>
                            <th>System</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['id']); ?></td>
                                <td><?php echo htmlspecialchars($report['type']); ?></td>
                                <td><?php echo htmlspecialchars($report['system']); ?></td>
                                <td><?php echo htmlspecialchars($report['severity']); ?></td>
                                <td>
                                    <span class="status-tag <?php echo strtolower(htmlspecialchars($report['status'])); ?>">
                                        <?php echo htmlspecialchars($report['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($report['date']); ?></td>
                                <td>
                                    <button class="view-details-btn" onclick="loadReportDetails('<?php echo htmlspecialchars($report['id']); ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Example function to simulate loading report details
        function loadReportDetails(reportId) {
            // This assumes your main JS has a function to fetch pages.
            // You would call it like this:
            const contentArea = document.getElementById("contentArea");
            const pageTitle = document.getElementById("pageTitle");

            fetch(`report_details.php?id=${reportId}`)
                .then(response => response.text())
                .then(data => {
                    contentArea.innerHTML = data;
                    pageTitle.textContent = `Report Details: ${reportId}`;
                    // You might also want to update the active state in the sidebar here
                })
                .catch(error => {
                    console.error('Error fetching report details:', error);
                    contentArea.innerHTML = '<p>Failed to load report details.</p>';
                });
        }
    </script>
</body>
</html>