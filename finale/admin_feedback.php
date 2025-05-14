<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}

require_once 'db_connection.php';

// Fetch messages from the messages table
$query = "SELECT m.*, u.username 
          FROM messages m
          LEFT JOIN users u ON m.user_id = u.id
          ORDER BY m.created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Feedback - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            font-family: Arial, sans-serif;
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

        .content-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 0 20px;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .feedback-header h1 {
            color: #333;
            font-size: 2.5em;
            margin: 0;
        }

        .back-button {
            background-color: #d48e0b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-button:hover {
            background-color: #b37609;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .messages-container {
            display: grid;
            gap: 20px;
        }

        .message-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: #333;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .message-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .message-sender {
            font-weight: bold;
            color: #d48e0b;
        }

        .message-date {
            color: #666;
            font-size: 0.9em;
        }

        .message-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            background: rgba(212, 142, 11, 0.1);
            color: #d48e0b;
        }

        .message-content {
            color: #444;
            line-height: 1.6;
        }

        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .reply-btn {
            background-color: #d48e0b;
            color: white;
        }

        .reply-btn:hover {
            background-color: #b37609;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .no-messages {
            text-align: center;
            color: #666;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .no-messages h2 {
            color: #333;
            margin: 10px 0;
        }

        .no-messages p {
            color: #666;
        }

        .username {
            color: #d48e0b !important;
            font-weight: 600;
        }

        .logout-btn1 {
            padding: 0.5rem 1rem;
            background-color: transparent;
            color: white;
            border: 1px solid white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .logout-btn1:hover {
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
                        <li><a href="admin_dashboard.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <span class="username" style="color: white;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="content-container">
            <div class="feedback-header">
                <h1>Customer Feedback</h1>
                <a href="admin_dashboard.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <div class="messages-container">
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $sender = $row['username'] ? htmlspecialchars($row['username']) : 'Guest';
                        $date = date('F j, Y, g:i a', strtotime($row['created_at']));
                        ?>
                        <div class="message-card">
                            <div class="message-header">
                                <div class="message-info">
                                    <span class="message-sender"><?php echo $sender; ?></span>
                                    <span class="message-date"><?php echo $date; ?></span>
                                </div>
                                <span class="message-status">New</span>
                            </div>
                            <div class="message-content">
                                <p><?php echo nl2br(htmlspecialchars($row['message'])); ?></p>
                            </div>
                            <div class="message-actions">
                                <button class="action-btn delete-btn" onclick="deleteMessage(<?php echo $row['id']; ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="no-messages">
                            <i class="fas fa-inbox" style="font-size: 3em; color: #d48e0b; margin-bottom: 20px;"></i>
                            <h2>No Messages Yet</h2>
                            <p>When customers send messages through the contact form, they will appear here.</p>
                          </div>';
                }
                ?>
            </div>
        </div>
    </main>

    <script>
        function replyToMessage(messageId) {
            // Implement reply functionality
            alert('Reply functionality will be implemented here');
        }

        function deleteMessage(messageId) {
            if (confirm('Are you sure you want to delete this message?')) {
                // Implement delete functionality using AJAX
                fetch('delete_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'message_id=' + messageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the message card from the DOM
                        const messageCard = document.querySelector(`.message-card[data-message-id="${messageId}"]`);
                        if (messageCard) {
                            messageCard.remove();
                        }
                        location.reload(); // Refresh to update the view
                    } else {
                        alert('Error deleting message');
                    }
                });
            }
        }
    </script>
</body>
</html> 