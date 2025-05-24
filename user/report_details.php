<?php
// report_details.php
session_start();
// require '../db/config.php';

// In a real application, you'd get the report ID from the URL (e.g., $_GET['id'])
// and fetch details from the database.
$reportId = $_GET['id'] ?? 'N/A'; // Get ID from URL, default to N/A
$report = [
    'id' => $reportId,
    'type' => 'Phishing',
    'system' => 'Email System',
    'severity' => 'High',
    'status' => 'Pending',
    'date_submitted' => '2025-05-23 10:30 AM',
    'description' => 'User received a suspicious email impersonating the IT department, requesting login credentials. The email contained a malicious link. User reported before clicking.',
    'timeline' => [
        ['date' => '2025-05-23 10:30 AM', 'event' => 'Incident reported by John Doe.'],
        ['date' => '2025-05-23 11:00 AM', 'event' => 'Initial review by Security Analyst Jane Smith.'],
        ['date' => '2025-05-23 11:15 AM', 'event' => 'Email quarantined and phishing link analyzed.'],
    ],
    'admin_remarks' => "Initial assessment confirms phishing attempt. Quarantined email and blocked sender. Awaiting user education follow-up.",
    'attached_files' => [
        ['name' => 'phishing_email_screenshot.png', 'path' => 'uploads/phishing_email_screenshot.png'],
        ['name' => 'email_headers.txt', 'path' => 'uploads/email_headers.txt'],
    ],
    'resolution_status' => 'Ongoing', // Could be 'Ongoing', 'Mitigated', 'Resolved', 'Closed'
    'resolution_details' => 'Ongoing investigation. User has been informed about best practices.',
];

// If no ID is provided or report not found, you might want to show an error or redirect
if ($reportId === 'N/A' || !$report) {
    echo "<p style='padding: 20px; text-align: center; color: #e74c3c;'>Report not found or invalid ID.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Details: <?php echo htmlspecialchars($reportId); ?></title>
    <style>
        /* Inline CSS for Report Details */
        .report-details-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .report-details-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .detail-section {
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .detail-section h3 {
            color: #00d1d1;
            margin-bottom: 15px;
            font-size: 22px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .detail-item {
            margin-bottom: 12px;
            font-size: 16px;
            color: #333;
        }
        .detail-item strong {
            color: #031b5c;
            display: inline-block;
            width: 150px; /* Align labels */
        }
        .detail-item p {
            margin-top: 10px;
            line-height: 1.6;
            background-color: #f0f8ff; /* Light azure */
            border-left: 3px solid #00d1d1;
            padding: 12px;
            border-radius: 5px;
        }
        .detail-item ul {
            list-style: none;
            padding: 0;
            margin-top: 10px;
        }
        .detail-item ul li {
            padding: 8px 0;
            border-bottom: 1px dashed #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .detail-item ul li:last-child {
            border-bottom: none;
        }
        .detail-item a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .detail-item a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .timeline ol {
            list-style: none;
            padding: 0;
            position: relative;
            margin-left: 20px; /* Space for line */
        }
        .timeline ol::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #00d1d1;
        }
        .timeline li {
            position: relative;
            padding: 10px 0 10px 25px;
            border-left: none;
            margin-bottom: 15px;
            font-size: 15px;
            color: #555;
        }
        .timeline li::before {
            content: '';
            position: absolute;
            left: -8px; /* Adjust to align with line */
            top: 15px;
            width: 15px;
            height: 15px;
            background-color: #00d1d1;
            border-radius: 50%;
            border: 3px solid #fff; /* White border to stand out */
        }
        .timeline li strong {
            color: #031b5c;
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        .status-tag { /* Reuse from my_reports.php, define here for inline */
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
        }
        .status-tag.pending { background-color: #ffe0b2; color: #e65100; }
        .status-tag.reviewed { background-color: #bbdefb; color: #1565c0; }
        .status-tag.resolved { background-color: #c8e6c9; color: #2e7d32; }
        .status-tag.ongoing { background-color: #d1c4e9; color: #4527a0; } /* Purple for ongoing */
        .status-tag.closed { background-color: #b2dfdb; color: #00695c; }
    </style>
</head>
<body>
    <div class="report-details-content">
        <h2>Report Details: #<?php echo htmlspecialchars($report['id']); ?></h2>

        <div class="detail-section">
            <h3><i class="fas fa-file-alt"></i> Incident Overview</h3>
            <div class="detail-item">
                <strong>Type:</strong> <?php echo htmlspecialchars($report['type']); ?>
            </div>
            <div class="detail-item">
                <strong>System Affected:</strong> <?php echo htmlspecialchars($report['system']); ?>
            </div>
            <div class="detail-item">
                <strong>Severity:</strong> <?php echo htmlspecialchars($report['severity']); ?>
            </div>
            <div class="detail-item">
                <strong>Status:</strong> 
                <span class="status-tag <?php echo strtolower(htmlspecialchars($report['status'])); ?>">
                    <?php echo htmlspecialchars($report['status']); ?>
                </span>
            </div>
            <div class="detail-item">
                <strong>Date Submitted:</strong> <?php echo htmlspecialchars($report['date_submitted']); ?>
            </div>
            <div class="detail-item">
                <strong>Description:</strong> 
                <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
            </div>
        </div>

        <div class="detail-section timeline">
            <h3><i class="fas fa-history"></i> Report Timeline</h3>
            <?php if (!empty($report['timeline'])): ?>
                <ol>
                    <?php foreach ($report['timeline'] as $event): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($event['date']); ?></strong>
                            <?php echo htmlspecialchars($event['event']); ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p>No timeline events available.</p>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-user-tie"></i> Admin Remarks</h3>
            <?php if (!empty($report['admin_remarks'])): ?>
                <p><?php echo nl2br(htmlspecialchars($report['admin_remarks'])); ?></p>
            <?php else: ?>
                <p>No administrative remarks yet.</p>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-paperclip"></i> Attached Files</h3>
            <?php if (!empty($report['attached_files'])): ?>
                <ul>
                    <?php foreach ($report['attached_files'] as $file): ?>
                        <li>
                            <i class="fas fa-file"></i> 
                            <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank" download>
                                <?php echo htmlspecialchars($file['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No files attached.</p>
            <?php endif; ?>
        </div>

        <div class="detail-section">
            <h3><i class="fas fa-tasks"></i> Resolution Status</h3>
            <div class="detail-item">
                <strong>Status:</strong> 
                <span class="status-tag <?php echo strtolower(htmlspecialchars($report['resolution_status'])); ?>">
                    <?php echo htmlspecialchars($report['resolution_status']); ?>
                </span>
            </div>
            <?php if (!empty($report['resolution_details'])): ?>
                <div class="detail-item">
                    <strong>Details:</strong> 
                    <p><?php echo nl2br(htmlspecialchars($report['resolution_details'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 