<?php
// dashboard.php
// Include your database configuration if needed for fetching dashboard data
// require '../db/config.php'; 

// Placeholder data - in a real app, you'd fetch these from a database
$totalReports = 150;
$openReports = 45;
$closedReports = 105;
$announcement = "System maintenance scheduled for May 28th, 2025, 2:00 AM - 4:00 AM PST.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        /* Inline CSS for Dashboard */
        .dashboard-content {
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dashboard-content h2 {
            color: #031b5c;
            margin-bottom: 25px;
            font-size: 28px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .dashboard-card {
            background-color: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.15);
        }
        .dashboard-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #00d1d1;
        }
        .dashboard-card h3 {
            font-size: 18px;
            color: #555;
            margin-bottom: 10px;
        }
        .dashboard-card p {
            font-size: 36px;
            font-weight: bold;
            color: #031b5c;
        }
        .announcements {
            background-color: #e6f7ff; /* Light blue */
            border-left: 5px solid #00d1d1;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .announcements h3 {
            color: #031b5c;
            margin-bottom: 15px;
            font-size: 22px;
        }
        .announcements p {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        .latest-updates {
            margin-top: 30px;
        }
        .latest-updates h3 {
            color: #031b5c;
            margin-bottom: 15px;
            font-size: 22px;
        }
        .latest-updates ul {
            list-style: none;
            padding: 0;
        }
        .latest-updates li {
            background-color: #fff;
            border: 1px solid #eee;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .latest-updates li .update-icon {
            font-size: 24px;
            color: #00d1d1;
        }
        .latest-updates li .update-text {
            font-size: 16px;
            color: #333;
        }
        .latest-updates li .update-text span {
            font-weight: bold;
            color: #031b5c;
        }
    </style>
</head>
<body>
    <div class="dashboard-content">
        <h2>Dashboard Overview</h2>

        <div class="dashboard-cards">
            <div class="dashboard-card">
                <i class="fas fa-chart-bar icon"></i>
                <h3>Total Reports</h3>
                <p><?php echo $totalReports; ?></p>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-folder-open icon"></i>
                <h3>Open Reports</h3>
                <p><?php echo $openReports; ?></p>
            </div>
            <div class="dashboard-card">
                <i class="fas fa-check-circle icon"></i>
                <h3>Closed Reports</h3>
                <p><?php echo $closedReports; ?></p>
            </div>
        </div>

        <div class="announcements">
            <h3><i class="fas fa-bullhorn"></i> Latest Announcements</h3>
            <p><?php echo htmlspecialchars($announcement); ?></p>
        </div>

        <div class="latest-updates">
            <h3><i class="fas fa-sync-alt"></i> Recent Activity</h3>
            <ul>
                <li>
                    <span class="update-icon"><i class="fas fa-file-alt"></i></span>
                    <p class="update-text"><span>John Doe</span> submitted a new incident report (Type: Phishing) - 2 hours ago.</p>
                </li>
                <li>
                    <span class="update-icon"><i class="fas fa-user-shield"></i></span>
                    <p class="update-text"><span>Admin</span> reviewed Report #00123 - 5 hours ago.</p>
                </li>
                <li>
                    <span class="update-icon"><i class="fas fa-check-double"></i></span>
                    <p class="update-text">Report #00120 has been <span>resolved</span> - Yesterday.</p>
                </li>
            </ul>
        </div>
    </div>
</body>
</html>