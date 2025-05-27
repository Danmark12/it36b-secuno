<?php
// This MUST be the very first line of code in this file.
// No spaces, no blank lines before the opening <?php tag.
require_once '../db/config.php'; // Include the database configuration first

// Get the requested page from the URL, default to 'dashboard'
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Define valid pages and their corresponding file paths
// The 'logout' entry is removed because it's now handled by a direct link to logout.php
$valid_pages = [
    'dashboard'       => 'dashboard.php',
    'report_incident' => 'report_incident.php', // Assuming this is in the 'pages' directory
    'my_reports'      => 'my_reports.php',
    'report_details'  => 'report_details.php',
    'account_logs'    => 'account_logs.php',
    'settings'        => 'settings.php'
];

// Check if the requested page is valid
if (!array_key_exists($page, $valid_pages)) {
    $page = 'dashboard'; // Fallback to dashboard if page is invalid
}

// You might want to implement user authentication here
// For now, we'll assume a user is "logged in" for demonstration purposes.
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php'); // Redirect to login page if not authenticated
//     exit();
// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secuno Security Incident Reporting</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
            overflow-x: hidden; /* Prevent horizontal scrollbar during transition */
        }

        /* Main container for the layout */
        .main-container {
            display: flex;
            min-height: 100vh; /* Ensure container takes full viewport height */
        }

        /* Side Menu Styles */
        .side-menu {
            width: 250px;
            background-color: #2c3e50; /* Dark blue-grey */
            color: #ecf0f1; /* Light grey for text */
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            position: fixed; /* Fixed sidebar */
            height: 100vh; /* Full height */
            overflow-y: auto; /* Scrollable if content overflows */
            transition: width 0.3s ease, padding 0.3s ease, opacity 0.3s ease; /* Smooth transitions */
            z-index: 1000; /* Ensure it's on top */
        }

        .side-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
            margin-bottom: 30px;
            border-bottom: 1px solid #34495e;
            padding-bottom: 15px;
        }

        .side-menu h2 {
            margin: 0;
            color: #ffffff;
            font-size: 1.6em;
            white-space: nowrap; /* Prevent wrapping when menu shrinks */
            transition: opacity 0.3s ease;
        }

        .menu-toggle-btn {
            background: none;
            border: none;
            color: #ecf0f1;
            font-size: 1.8em;
            cursor: pointer;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.3s ease;
        }

        .menu-toggle-btn:hover {
            color: #3498db;
        }

        .side-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
            transition: opacity 0.3s ease; /* Smooth transition for menu items */
        }

        .side-menu ul li {
            margin-bottom: 5px;
        }

        .side-menu ul li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
            color: #ecf0f1;
            font-size: 1.1em;
            transition: background-color 0.3s ease, color 0.3s ease;
            white-space: nowrap; /* Prevent wrapping */
        }

        .side-menu ul li a:hover,
        .side-menu ul li a.active {
            background-color: #3498db; /* Blue on hover/active */
            color: #ffffff;
            border-left: 5px solid #2980b9; /* Darker blue border */
        }

        .side-menu ul li a i {
            margin-right: 10px; /* Space between icon and text */
            font-size: 1.2em;
        }

        /* Main Content Area */
        .content-wrapper {
            flex-grow: 1; /* Takes remaining space */
            padding: 20px;
            margin-left: 250px; /* Offset for the fixed sidebar */
            transition: margin-left 0.3s ease; /* Smooth transition for content shift */
            display: flex;
            flex-direction: column; /* Allow content and menu button to stack if needed */
            justify-content: flex-start; /* Align content to start, not center */
            position: relative; /* For positioning the new menu button */
        }

        .content-area {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 20px;
            width: 100%; /* Max width within its wrapper */
            max-width: 960px; /* Optional: Constrain max width of content */
            margin: 0 auto; /* Center the content area horizontally */
        }

        .content-area h1 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .content-area p {
            line-height: 1.6;
            color: #555;
        }

        /* New menu button for when sidebar is hidden */
        .show-menu-btn {
            position: fixed; /* Fixed position */
            top: 20px; /* Adjust as needed */
            left: 20px; /* Adjust as needed */
            background-color: #3498db; /* Blue background */
            color: #ffffff; /* White icon */
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            font-size: 1.5em;
            cursor: pointer;
            z-index: 1001; /* Higher than sidebar when hidden */
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: opacity 0.3s ease, visibility 0.3s ease;
            opacity: 0; /* Hidden by default */
            visibility: hidden; /* Hidden by default */
        }

        .show-menu-btn:hover {
            background-color: #2980b9; /* Darker blue on hover */
        }

        /* Styles for when the menu is hidden */
        .menu-hidden .side-menu {
            width: 0; /* Collapse the menu */
            padding-top: 0; /* Remove top padding */
            overflow: hidden; /* Hide overflowing content */
            opacity: 0; /* Fade out content */
        }

        .menu-hidden .side-menu-header,
        .menu-hidden .side-menu h2,
        .menu-hidden .side-menu ul {
            opacity: 0; /* Hide these elements when menu is hidden */
            pointer-events: none; /* Disable interaction */
        }

        .menu-hidden .content-wrapper {
            margin-left: 0; /* Content takes full width */
        }

        .menu-hidden .show-menu-btn {
            opacity: 1; /* Show the new menu button */
            visibility: visible; /* Make it visible */
        }

        /* Responsive adjustments (optional, but good practice) */
        @media (max-width: 768px) {
            .side-menu {
                width: 200px; /* Slightly smaller on mobile */
            }
            .content-wrapper {
                margin-left: 200px;
                padding: 10px; /* Less padding on smaller screens */
            }
            .content-area {
                padding: 15px;
            }
            .menu-hidden .side-menu {
                width: 0;
            }
            /* Adjust position of show-menu-btn for smaller screens if needed */
            .show-menu-btn {
                top: 10px;
                left: 10px;
                font-size: 1.2em;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="side-menu" id="sideMenu">
            <div class="side-menu-header">
                <h2>Secuno Portal</h2>
                <button class="menu-toggle-btn" id="menuToggleBtn">☰</button>
            </div>
            <ul>
                <li><a href="?page=dashboard" class="<?php echo ($page == 'dashboard' ? 'active' : ''); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="?page=report_incident" class="<?php echo ($page == 'report_incident' ? 'active' : ''); ?>"><i class="fas fa-pencil-alt"></i> Report Incident</a></li>
                <li><a href="?page=my_reports" class="<?php echo ($page == 'my_reports' ? 'active' : ''); ?>"><i class="fas fa-folder-open"></i> My Reports</a></li>
               
                <li><a href="?page=account_logs" class="<?php echo ($page == 'account_logs' ? 'active' : ''); ?>"><i class="fas fa-history"></i> Account Logs</a></li>
                <li><a href="?page=settings" class="<?php echo ($page == 'settings' ? 'active' : ''); ?>"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="content-wrapper">
            <button class="show-menu-btn" id="showMenuBtn">☰</button>

            <div class="content-area">
                <?php
                // Include the content for the selected page
                if (file_exists($valid_pages[$page])) {
                    include $valid_pages[$page];
                } else {
                    echo "<h1>Page Not Found</h1><p>The requested page could not be loaded.</p>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggleBtn = document.getElementById('menuToggleBtn');
            const showMenuBtn = document.getElementById('showMenuBtn'); // Get the new button
            const body = document.body;

            // Check localStorage on load to maintain the menu state
            if (localStorage.getItem('menuHidden') === 'true') {
                body.classList.add('menu-hidden');
            }

            // Function to toggle menu visibility
            function toggleMenu() {
                body.classList.toggle('menu-hidden');

                // Store the state in localStorage
                if (body.classList.contains('menu-hidden')) {
                    localStorage.setItem('menuHidden', 'true');
                } else {
                    localStorage.setItem('menuHidden', 'false');
                }
            }

            menuToggleBtn.addEventListener('click', toggleMenu);
            showMenuBtn.addEventListener('click', toggleMenu); // Add event listener for the new button
        });
    </script>

</body>
</html>