<?php 
session_start(); 
require '../db/config.php'; 
?> 

<!DOCTYPE html> 
<html lang="en"> 
<head> 
    <meta charset="UTF-8" /> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/> 
    <title>Secuno</title> 
    <link rel="stylesheet" href="style/users.css" /> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" /> 
    <style> 
        /* Your existing inline styles here.
           Ensure you regenerate CSP hash if these change!
           (Though, ideally, move all styles to users.css) */
        .sidebar { 
            transition: transform 0.3s ease; 
        } 

        /* This specific inline style from your original code should ideally be removed
           and managed solely by the .sidebar.hidden class in users.css
           However, if you must keep it, it will override the external CSS.
           For best practice, remove this block if users.css is correctly applied.
        */
        /*
        .sidebar.hide { 
            transform: translateX(-100%); 
        } 
        .main.full-width { 
            width: 100%; 
            margin-left: 0; 
        } 
        */

        .logo-container { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-right: 10px; 
        } 

        .close-btn { 
            background: none; 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: #fff; 
        } 
    </style> 
</head> 
<body> 
    <div class="container"> 
        <div class="sidebar" id="sidebar"> <div class="logo-container"> 
                <div class="logo"><span>Secuno</span></div> 
                <button class="close-btn" id="closeBtn">&times;</button> 
            </div> 

            <ul class="menu" id="sidebarMenu"> 
                <li class="active"> 
                    <a href="dashboard.php" data-page="dashboard.php"><i class="fas fa-home"></i> Dashboard</a> 
                </li> 
                <li> 
                    <a href="report_incident.php" data-page="report_incident.php"><i class="fas fa-file-alt"></i> Report Incident</a> 
                </li> 
                <li> 
                    <a href="my_reports.php" data-page="my_reports.php"><i class="fas fa-folder-open"></i> My Reports</a> 
                </li> 
                <li> 
                    <a href="report_details.php" data-page="report_details.php"><i class="fas fa-info-circle"></i> Report Details</a> 
                </li> 
                <li> 
                    <a href="account_logs.php" data-page="account_logs.php"><i class="fas fa-clipboard-list"></i> Account Logs</a> 
                </li> 
                <li> 
                    <a href="settings.php" data-page="settings.php"><i class="fas fa-cog"></i> Settings</a> 
                </li> 
<li>
    <a href="logout.php" onclick="return confirm('Are you sure you want to log out?')">
        <i class="fas fa-sign-out-alt"></i> Log Out
    </a>
</li>

            </ul> 
        </div> 

        <div class="main" id="mainContent"> 
            <div class="top-bar"> 
                <div class="menu-section"> 
                    <button class="menu-toggle" id="menuToggle"> 
                        <span>&#9776;</span> 
                    </button> 
                    <div class="dashboard-title" id="pageTitle"> 
                        Dashboard 
                    </div> 
                </div> 
                <div class="search-notification-section"> 
                    <div class="search-bar"> 
                        <input type="text" placeholder="Search anything..." /> 
                    </div> 
                    <div class="notification-icon" title="Notifications">&#128276;</div> 
                </div> 
            </div> 

            <div id="contentArea"> 
                <?php include 'dashboard.php'; ?> 
            </div> 

            <script> 
                const menuToggle = document.getElementById("menuToggle"); 
                const sidebar = document.getElementById("sidebar"); 
                const mainContent = document.getElementById("mainContent"); 
                const pageTitle = document.getElementById("pageTitle"); 
                const sidebarMenu = document.getElementById("sidebarMenu"); 
                const contentArea = document.getElementById("contentArea"); 
                const closeBtn = document.getElementById("closeBtn"); 

                // Function to set the sidebar and main content state
                function setSidebarState(isVisible) {
                    if (isVisible) {
                        sidebar.classList.remove("hidden");
                        sidebar.classList.add("show"); // If you use 'show' for other styles
                        mainContent.classList.remove("full-width");
                    } else {
                        sidebar.classList.add("hidden");
                        sidebar.classList.remove("show"); // Remove 'show' if present
                        mainContent.classList.add("full-width");
                    }
                }

                // Initial state check on page load and window resize
                function checkWindowSize() {
                    if (window.innerWidth <= 768) {
                        // On mobile, sidebar should initially be hidden
                        setSidebarState(false);
                        // Make sure the close button is visible on mobile when sidebar opens
                        closeBtn.style.display = 'block'; 
                    } else {
                        // On desktop, sidebar should initially be visible
                        setSidebarState(true);
                        // Make sure the close button is visible on desktop
                        closeBtn.style.display = 'block';
                    }
                }

                // Call on page load
                checkWindowSize();

                // Re-check on window resize
                window.addEventListener('resize', checkWindowSize);

                menuToggle.addEventListener("click", () => { 
                    setSidebarState(true); // Show sidebar
                }); 

                closeBtn.addEventListener("click", () => { 
                    setSidebarState(false); // Hide sidebar
                }); 

                sidebarMenu.addEventListener("click", (event) => { 
                    if (event.target.tagName === 'A') { 
                        event.preventDefault(); 
                        const pageUrl = event.target.getAttribute('href'); 
                        let pageName = event.target.getAttribute('data-page')?.replace('.php', '') || '';
                        if (pageName.includes('_')) {
                            pageName = pageName.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                        } else {
                            pageName = pageName.charAt(0).toUpperCase() + pageName.slice(1);
                        }
                        const titleText = pageName || 'Page'; 

                        fetch(pageUrl) 
                            .then(response => response.text()) 
                            .then(data => { 
                                contentArea.innerHTML = data; 
                                pageTitle.textContent = titleText; 

                                document.querySelectorAll('#sidebarMenu li').forEach(li => { 
                                    li.classList.remove('active'); 
                                }); 
                                event.target.parentNode.classList.add('active'); 

                                // On mobile, hide sidebar after clicking a menu item
                                if (window.innerWidth <= 768) {
                                    setSidebarState(false);
                                }
                            }) 
                            .catch(error => { 
                                console.error('Error fetching page:', error); 
                                contentArea.innerHTML = '<p>Failed to load content.</p>'; 
                            }); 
                    } 
                }); 
            </script> 
        </div> 
    </div> 
</body> 
</html>