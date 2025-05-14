<?php
session_start();
// Check if admin is logged in (you may want to use a different session variable for admins)
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<style>
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
        .auth-buttons button {
            background: transparent;
            border: 1px solid #fff;
            color: #fff;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .auth-buttons button:hover {
            background: #fff;
            color: #000;
        }
        .logo-container img {
            height: 400px;
            width: auto;
            position: static;
            margin-top: 24px;
        }
        .hero {
            margin-top: 0;
            min-height: 100vh;
            width: 100%;
            background: url('images/homepageimage4.png') center center/cover no-repeat;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            box-sizing: border-box;
        }
        .admin-categories {
            display: flex;
            flex-direction: row;
            gap: 30px;
            padding: 20px;
            width: auto;
            max-width: 1200px;
            margin: 0 auto;
            justify-content: center;
        }

        .admin-btn {
            min-width: 220px;
            width: auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-size: 1.2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .admin-btn:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .admin-btn i {
            font-size: 2rem;
            color: #d48e0b;
            min-width: 40px;
        }

        .logout-btn2{
            padding: 0.5rem 1rem;
            background-color: transparent;
            color: white;
            border: 1px solid white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .logout-btn2:hover {
            background-color: rgb(250, 249, 249);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            color: black;
        }
        </style>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DataDrip</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                        <li><a href="admin_dashboard.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <span class="username" style="color: orange;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="logout-btn2">Logout</a>
                </div>
            </div>
        </div> <!-- Close .top-header here -->
    </header>
    <main>
        <div class="hero">
            <div class="hero-text">
                <h1>Welcome to the Admin Dashboard, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h1>
                <div class="admin-categories">
                    <a href="admin_users.php" class="admin-btn">
                        <i class="fas fa-users"></i><br>
                        User Management
                    </a>
                    <a href="admin_purchase_history.php" class="admin-btn">
                        <i class="fas fa-history"></i><br>
                        User Purchase History
                    </a>
                    <a href="admin_inventory.php" class="admin-btn">
                        <i class="fas fa-box"></i><br>
                        Inventory Management
                    </a>
                    <a href="admin_income_reports.php" class="admin-btn">
                        <i class="fas fa-chart-line"></i><br>
                        Income Reports
                    </a>
                    <a href="admin_feedback.php" class="admin-btn">
                        <i class="fas fa-comments"></i><br>
                        Customer Feedback
                    </a>
                    <a href="add_product.php" class="admin-btn">
                        <i class="fas fa-plus-circle"></i><br>
                        Add Product
                    </a>
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