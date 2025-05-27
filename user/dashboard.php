<?php
// dashboard.php - User Dashboard
require_once '../db/config.php'; // Includes database connection and session start

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Adjust path based on your login.php location relative to user/
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User'; // Assuming user_name is stored in session

// --- Fetch Dashboard Data ---
$total_incidents = 0; // Renamed for clarity
$pending_incidents = 0; // Renamed for clarity
$resolved_incidents = 0; // Renamed for clarity
$announcements = [];
$error_message = ''; // Initialize error message variable

try {
    // 1. Fetch Total Incidents (from the 'incidents' table)
    $stmt_total = $conn->query("SELECT COUNT(*) AS total FROM incidents");
    $total_incidents = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Fetch Pending Incidents (using the 'status' column)
    $stmt_pending = $conn->query("SELECT COUNT(*) AS pending FROM incidents WHERE status = 'Pending'");
    $pending_incidents = $stmt_pending->fetch(PDO::FETCH_ASSOC)['pending'];

    // 3. Fetch Resolved Incidents (using the 'resolution_status' column)
    $stmt_resolved = $conn->query("SELECT COUNT(*) AS resolved FROM incidents WHERE resolution_status = 'Resolved'");
    $resolved_incidents = $stmt_resolved->fetch(PDO::FETCH_ASSOC)['resolved'];

    // 4. Fetch Latest Announcements (from the 'announcements' table - this table definition was still needed)
    $stmt_announcements = $conn->query("SELECT title, content, created_at FROM announcements ORDER BY created_at DESC LIMIT 5");
    $announcements = $stmt_announcements->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for debugging, but show a generic message to the user
    error_log("Dashboard data fetching error (User ID: " . $user_id . "): " . $e->getMessage());
    $error_message = "Could not load dashboard data. Please try again later. (DB Error)";
    // In a production environment, you might log user activity here as well:
    // logUserActivity($conn, $user_id, 'Dashboard Load Failed', 'Database error: ' . $e->getMessage());
}

?>

<style>
    /* Basic Dashboard Styles */
    .dashboard-container {
        padding: 25px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .dashboard-container h1 {
        color: #2c3e50;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
        border-left: 5px solid; /* Dynamic border color */
    }

    /* Specific colors for report statuses */
    .stat-card.total { border-left-color: #007bff; }   /* Blue for Total */
    .stat-card.pending { border-left-color: #ffc107; } /* Yellow/Orange for Pending */
    .stat-card.resolved { border-left-color: #28a745; } /* Green for Resolved */

    .stat-card h3 {
        color: #555;
        font-size: 1.1em;
        margin-bottom: 10px;
    }

    .stat-card .count {
        font-size: 2.5em;
        font-weight: bold;
        color: #333;
    }

    .announcements-section {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 5px solid #6c757d; /* Grey for Announcements */
    }

    .announcements-section h2 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .announcement-item {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px dashed #eee;
    }
    .announcement-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }

    .announcement-item h3 {
        font-size: 1.2em;
        color: #007bff; /* Blue for announcement titles */
        margin-top: 0;
        margin-bottom: 5px;
    }

    .announcement-item p {
        font-size: 0.95em;
        color: #555;
        line-height: 1.5;
    }

    .announcement-item .date {
        font-size: 0.8em;
        color: #888;
        text-align: right;
    }

    /* Message styling for error display */
    .message {
        padding: 12px 20px;
        border-radius: 5px;
        margin-bottom: 20px;
        font-weight: bold;
        display: block; /* Always block for the dashboard error */
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<div class="content-area">
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>

        <?php if (!empty($error_message)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <h2>Your Incident Statistics</h2>
        <div class="stats-grid">
            <div class="stat-card total">
                <h3>Total Incidents</h3>
                <div class="count"><?php echo htmlspecialchars($total_incidents); ?></div>
            </div>
            <div class="stat-card pending">
                <h3>Pending Incidents</h3>
                <div class="count"><?php echo htmlspecialchars($pending_incidents); ?></div>
            </div>
            <div class="stat-card resolved">
                <h3>Resolved Incidents</h3>
                <div class="count"><?php echo htmlspecialchars($resolved_incidents); ?></div>
            </div>
        </div>

        <div class="announcements-section">
            <h2>Latest Updates & Announcements</h2>
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                        <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        <div class="date">Published: <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No new announcements at this time.</p>
            <?php endif; ?>
        </div>

    </div>
</div>