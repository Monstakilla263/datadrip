<?php
session_start();
// Check if user is logged in (either admin or regular user)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | DataDrip</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .team-card {
            background-color: rgb(56, 51, 51);
        }
        body {
            background-color: rgb(78, 72, 72);
        }
        .top-header {
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            backdrop-filter: blur(5px);
        }

        .logo-container {
            margin-top: 24px;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 20px;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li a {
            color: #ffffff; /* WHITE TEXT */
            font-size: 20px;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500; /* Added for better visibility */
        }

    nav ul li a:hover {
        color: #d48e0b; /* optional hover color */
    }
        .about-content {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
            text-align: center;
        }

        .about-intro {
            background: rgb(56, 51, 51);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            margin-bottom: 60px;
        }

        .about-intro h1 {
            color: #fff;
            font-size: 2.5em;
            margin-bottom: 20px;
        }

        .about-intro p {
            color: #ddd;
            font-size: 1.1em;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }

        .team-section {
            margin-top: 40px;
            background-color: rgb(56, 51, 51);
            padding: 40px 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .team-section h2 {
            color: #fff;
            font-size: 2em;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .team-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .team-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4);
        }

        .team-card .image-container {
            width: 100%;
            height: 300px;
            overflow: hidden;
            position: relative;
        }

        .team-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .team-card:hover img {
            transform: scale(1.05);
        }

        .team-card .info {
            padding: 20px;
            text-align: center;
            background-color: rgb(56, 51, 51);
        }

        .team-card h3 {
            color: #fff;
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .team-card .role {
            color: #d48e0b;
            font-weight: 500;
            margin-bottom: 15px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .team-card .social-links {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .team-card .social-links a {
            color: #fff;
            font-size: 1.2em;
            transition: color 0.3s ease;
            opacity: 0.8;
        }

        .team-card .social-links a:hover {
            color: #d48e0b;
            opacity: 1;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 60px 0;
        }

        .mission-card, .vision-card {
            background: rgb(56, 51, 51);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .mission-card h3, .vision-card h3 {
            color: #d48e0b;
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .mission-card p, .vision-card p {
            color: #ddd;
            line-height: 1.6;
        }

        .copyright {
            color: #ddd;
            text-align: center;
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="top-header">
            <div class="logo-container">
                <img src="images/datadrip.png" alt="DataDrip Logo">
            </div>
            <div class="nav-auth">
                <nav>
                    <ul>
                    <li>
                        <a href="<?php
                            if (isset($_SESSION['admin_username'])) {
                                echo 'admin_dashboard.php';
                            } else {
                                echo 'dashboard.php';
                            }
                        ?>">Home</a>
                    </li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <span class="username" style="color: orange;">
                        <?php 
                        if (isset($_SESSION['admin_username'])) {
                            echo htmlspecialchars($_SESSION['admin_username']);
                        } else {
                            echo htmlspecialchars($_SESSION['username']);
                        }
                        ?>
                    </span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="about-content">
            <div class="about-intro">
                <h1>About DataDrip</h1>
                <p>Welcome to DataDrip, your premier destination for high-quality clothing and accessories. We are committed to providing you with the latest fashion trends while ensuring comfort and style.</p>
            </div>

            <div class="mission-vision">
                <div class="mission-card">
                    <h3>Our Mission</h3>
                    <p>To provide stylish and comfortable fashion for everyone, making quality clothing accessible while maintaining the highest standards of customer service and satisfaction.</p>
                </div>
                <div class="vision-card">
                    <h3>Our Vision</h3>
                    <p>To become the leading fashion destination that empowers individuals to express themselves through style, while building a sustainable and inclusive fashion community.</p>
                </div>
            </div>

            <div class="team-section">
                <h2>Meet Our Development Team</h2>
                <div class="team-cards">
                    <div class="team-card">
                        <div class="image-container">
                            <img src="images/patrick.jpg" alt="Patrick Bindadan">
                        </div>
                        <div class="info">
                            <h3>Patrick Bindadan</h3>
                            <div class="role">CEO</div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="team-card">
                        <div class="image-container">
                            <img src="images/eli.jpg" alt="Eli Esguerra">
                        </div>
                        <div class="info">
                            <h3>Eli Esguerra</h3>
                            <div class="role">CEO</div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="team-card">
                        <div class="image-container">
                            <img src="images/alfonso.jpg" alt="Alfonso Mortel">
                        </div>
                        <div class="info">
                            <h3>Alfonso Mortel</h3>
                            <div class="role">CEO</div>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-linkedin"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-links"></div>
            <div class="social-icons"></div>
        </div>
        <div class="copyright">
            &copy; 2025 DataDrip. All Rights Reserved.
        </div>
    </footer>
</body>
</html> 