<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secuno</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa; /* Light gray background for a modern, clean look */
            color: #343a40; /* Dark gray for text */
            line-height: 1.6;
        }

        header {
            background-color: #212529; /* Dark charcoal for header */
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            color: #ffffff; /* White text for header */
        }

        .logo {
            display: flex;
            align-items: center;
        }

        .logo span {
            font-size: 1.8em;
            font-weight: bold;
        }

        .logo span:first-child {
            color: #007bff; /* Bright blue for "Sec" */
        }

        .logo span:last-child {
            color: #e9ecef; /* Off-white for "uno" */
        }

        .auth-buttons {
            display: flex;
            align-items: center;
        }

        .auth-buttons a {
            text-decoration: none;
            color: #ffffff; /* White text for buttons */
            font-weight: bold;
            margin-left: 20px;
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #007bff; /* Bright blue border */
            background-color: #212529; /* Dark charcoal background */
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .auth-buttons a:hover {
            background-color: #007bff; /* Bright blue hover for buttons */
            border-color: #007bff;
            color: #ffffff;
        }

        .auth-buttons .signup-button {
            background-color: #007bff; /* Bright blue sign-up button */
            color: #fff;
            border: none;
        }

        .auth-buttons .signup-button:hover {
            background-color: #0056b3; /* Slightly darker blue on hover */
        }

        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 80px 30px;
            border-radius: 10px;
            margin: 20px;
            background-color: #ffffff; /* White background for hero section */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .hero-content {
            flex: 1;
            padding-right: 40px;
        }

        .hero-content h1 {
            font-size: 3.5em;
            color: #212529; /* Dark charcoal heading */
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .hero-content p {
            color: #6c757d; /* Muted gray tagline */
            font-size: 1.5em;
            margin-bottom: 30px;
        }

        .hero-content .call-to-action-button {
            display: inline-block;
            background-color: #28a745; /* Green button for action */
            color: #fff;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s ease;
            font-size: 1.1em;
        }

        .hero-content .call-to-action-button:hover {
            background-color: #218838; /* Darker green on hover */
        }

        .hero-image {
            flex: 1;
            text-align: center;
        }

        .hero-image img {
            max-width: 80%;
            height: auto;
            border-radius: 8px; /* Slightly rounded corners for the image */
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #e9ecef; /* Light gray for footer */
            color: #6c757d; /* Muted gray for footer text */
            font-size: 0.9em;
            margin-top: 20px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                text-align: center;
            }

            .logo {
                margin-bottom: 10px;
            }

            .auth-buttons {
                flex-direction: column;
            }
            .auth-buttons a{
                margin-left: 0;
                margin-top: 10px;
            }

            .hero {
                flex-direction: column;
                text-align: center;
                padding: 60px 20px;
            }

            .hero-content {
                padding-right: 0;
                margin-bottom: 30px;
            }

            .hero-content h1{
                font-size: 3em;
            }
            .hero-content p{
                font-size: 1.2em;
            }

            .hero-image img {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <span>Sec</span><span>uno</span>
        </div>
        <div class="auth-buttons">
            <a href="feature/login.php">Log In</a>
            <a href="feature/register.php" class="signup-button">Sign Up</a>
        </div>
    </header>

    <main class="hero">
        <div class="hero-content">
            <h1>Secuno</h1>
            <p>Your trusted partner in cybersecurity and incident management.</p>
            <a href="#" class="call-to-action-button">Learn More</a>
        </div>
        <div class="hero-image">
            <img src="image/doctor.jpg" alt="Cybersecurity Concept Image">
        </div>
    </main>

    <footer>
        <p>&copy; 2025 Secuno. All rights reserved.</p>
    </footer>
</body>
</html>