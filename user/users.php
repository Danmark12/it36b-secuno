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
        .sidebar { 
            transition: transform 0.3s ease; 
        } 

        .sidebar.hide { 
            transform: translateX(-100%); 
        } 

        .main.full-width { 
            width: 100%; 
            margin-left: 0; 
        } 

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
        <div class="sidebar show" id="sidebar"> 
            <div class="logo-container"> 
                <div class="logo"><span>Secuno</span></div> 
                <button class="close-btn" id="closeBtn">&times;</button> 
            </div> 

            <ul class="menu" id="sidebarMenu"> 
                <li class="active"> 
                    <a href="dashboard.php" data-page="dashboard.php"><i class="fas fa-home"></i> Dashboard</a> 
                </li> 
                <li> 
                    <a href="report_incident.php" data-page="report_incident.php"><i class="fas fa-exclamation-circle"></i> Report Incident</a> 
                </li> 
                <li> 
                    <a href="my_reports.php" data-page="my_reports.php"><i class="fas fa-folder-open"></i> My Reports</a> 
                </li> 
                <li> 
                    <a href="settings.php" data-page="settings.php"><i class="fas fa-cog"></i> Settings</a> 
                </li> 
                <li> 
                    <a href="../feature/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a> 
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

                menuToggle.addEventListener("click", () => { 
                    sidebar.classList.add("show"); 
                    sidebar.classList.remove("hide"); 
                    mainContent.classList.remove("full-width"); 
                }); 

                closeBtn.addEventListener("click", () => { 
                    sidebar.classList.remove("show"); 
                    sidebar.classList.add("hide"); 
                    mainContent.classList.add("full-width"); 
                }); 

                sidebarMenu.addEventListener("click", (event) => { 
                    if (event.target.tagName === 'A') { 
                        event.preventDefault(); 
                        const pageUrl = event.target.getAttribute('href'); 
                        const pageName = event.target.getAttribute('data-page')?.replace('.php', '').replace(/_/g, ' ') || 'Page'; 
                        const titleText = pageName.charAt(0).toUpperCase() + pageName.slice(1); 

                        fetch(pageUrl) 
                            .then(response => response.text()) 
                            .then(data => { 
                                contentArea.innerHTML = data; 
                                pageTitle.textContent = titleText; 

                                document.querySelectorAll('#sidebarMenu li').forEach(li => { 
                                    li.classList.remove('active'); 
                                }); 
                                event.target.parentNode.classList.add('active'); 
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