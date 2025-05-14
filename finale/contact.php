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
    <title>Contact | DataDrip</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
            color: #ffffff;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
        }

        nav ul li a:hover {
            color: #d48e0b;
        }

        .contact-content {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .right-column {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .image-container {
            background: rgb(56, 51, 51);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .image-container h2 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.8em;
            text-align: center;
        }

        .image-display {
            width: 100%;
            height: 300px;
            border-radius: 8px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
        }

        .image-display img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .map-container {
            background: rgb(56, 51, 51);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
            height: fit-content;
        }

        .map-container h2 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 1.8em;
            text-align: center;
        }

        .map-frame {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .contact-card {
            background: rgb(56, 51, 51);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .contact-card h1 {
            color: #fff;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: #ddd;
            margin-bottom: 8px;
            font-size: 1.1em;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group textarea {
            height: 150px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d48e0b;
            box-shadow: 0 0 0 2px rgba(212, 142, 11, 0.2);
            background-color: rgba(255, 255, 255, 0.1);
        }

        button[type="submit"] {
            background-color: #d48e0b;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto;
            width: auto;
            min-width: 200px;
        }

        button[type="submit"]:hover {
            background-color: #b37609;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .contact-info {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            text-align: center;
        }

        .contact-info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            color: #ddd;
        }

        .contact-info-item i {
            font-size: 2em;
            color: #d48e0b;
            margin-bottom: 15px;
        }

        .contact-info-item h3 {
            color: #fff;
            margin-bottom: 10px;
        }

        .copyright {
            color: #ddd;
            text-align: center;
            padding: 20px 0;
        }

        .message-alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .message-alert.success {
            background-color: rgba(40, 167, 69, 0.2);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.3);
        }

        .message-alert.error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .logout-btn {
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid white;
        }

        .logout-btn:hover {
            background-color: rgba(0, 0, 0, 0.7);
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
        <div class="contact-content">
            <div class="contact-container">
                <div class="contact-card">
                    <h1>Contact Us</h1>
                    <?php
                    if (isset($_SESSION['message'])) {
                        $messageType = $_SESSION['message_type'];
                        echo '<div class="message-alert ' . $messageType . '">';
                        if ($messageType === 'success') {
                            echo '<i class="fas fa-check-circle"></i>';
                        } else {
                            echo '<i class="fas fa-exclamation-circle"></i>';
                        }
                        echo $_SESSION['message'];
                        echo '</div>';
                        // Clear the message after displaying
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    }
                    ?>
                    <form action="process_contact.php" method="POST">
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message:</label>
                            <textarea id="message" name="message" required></textarea>
                        </div>
                        <button type="submit">Send Message</button>
                    </form>

                    <div class="contact-info">
                        <div class="contact-info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>Address</h3>
                            <p>123 DataDrip Street, Manila, Philippines</p>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-phone"></i>
                            <h3>Phone</h3>
                            <p>+63 912 345 6789</p>
                        </div>
                        <div class="contact-info-item">
                            <i class="fas fa-envelope"></i>
                            <h3>Email</h3>
                            <p>support@datadrip.com</p>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <div class="map-container">
                        <h2>Find Us</h2>
                        <iframe 
                            class="map-frame"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.09216575685!2d120.97428817594077!3d14.593823777240894!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397ca182cc63319%3A0x87fde2a2049d0222!2sColegio%20de%20San%20Juan%20de%20Letran!5e0!3m2!1sen!2sph!4v1745911070962!5m2!1sen!2sph"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <div class="image-container">
                        <h2>Our Store</h2>
                        <div class="image-display">
                            <img src="images/store.jpg" alt="DataDrip Store">
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

    <script>
        document.getElementById('image-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview-image');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    document.querySelector('.image-upload-area i').style.display = 'none';
                    document.querySelector('.image-upload-area p').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Reset preview when clicking on image area
        document.querySelector('.image-upload-area').addEventListener('click', function(e) {
            if (e.target === this) {
                document.getElementById('preview-image').style.display = 'none';
                document.querySelector('.image-upload-area i').style.display = 'block';
                document.querySelector('.image-upload-area p').style.display = 'block';
                document.getElementById('image-upload').value = '';
            }
        });
    </script>
</body>
</html> 