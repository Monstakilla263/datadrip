<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

// Get user ID from session
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// Handle status updates
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE orders SET status_id = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $new_status, $order_id, $user_id);
    $stmt->execute();
}

// Handle order deletion
if (isset($_POST['delete_order']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    // Only allow deletion of delivered or cancelled orders based on status_id
    // status_id 4 is 'delivered' and 5 is 'cancelled'
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ? AND status_id IN (4, 5)");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    
    // Delete associated order items if the order was deleted
    if ($stmt->affected_rows > 0) {
        $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $stmt_items->close();
    }
    $stmt->close();
}


// Get all orders for the user
$query = "SELECT o.*, os.status as status_name, os.id as status_id_from_os_table 
          FROM orders o 
          JOIN order_status os ON o.status_id = os.id 
          WHERE o.user_id = ? ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

$stmt_inventory = $conn->prepare("UPDATE products SET inventory = inventory - ? WHERE id = ?");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders - DataDrip</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            color: #ffffff;
            font-size: 20px;
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
        }

        nav ul li a:hover {
            color: #d48e0b;
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
        
        .orders-container {
            max-width: 1200px;
            margin: 120px auto 40px;
            padding: 20px;
        }

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .order-number {
            font-weight: bold;
            color: #333;
        }

        .order-date {
            color: #666;
        }

        .order-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-delivered { background: #c3e6cb; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-details {
            margin: 15px 0;
        }

        .order-items {
            margin-top: 15px;
        }

        .shipping-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .total-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-top: 15px;
            text-align: right;
        }

        .status-form {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .status-form select {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .status-form button {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .status-form button:hover {
            background: #0056b3;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .delete-btn i {
            margin-right: 5px;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
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
                        <li><a href="dashboard.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="auth-buttons">
                    <span class="username" style="color: orange;"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="orders-container">
            <h1>My Orders</h1>
            
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-number">Order #<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                            <?php 
                            // Use status name fetched from database
                            $status = $order['status_name'] ?? 'pending'; // Align with the new alias 'status_name'
                            
                            // Only allow deletion of delivered or cancelled orders based on status name
                            if (strtolower($status) === 'delivered' || strtolower($status) === 'cancelled'): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="delete_order" class="delete-btn">
                                        <i class="fas fa-times"></i> Delete
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-status status-<?php echo strtolower($status); ?>">
                            Status: <?php echo ucfirst($status); ?>
                        </div>

                        <div class="shipping-info">
                            <h3>Shipping Information</h3>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['order_shipping_name']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['order_shipping_contact']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['order_shipping_address']); ?></p>
                            <?php
                            // Map shipping_method_id to shipping method text
                            $shipping_methods = array(
                                1 => 'Lalamove',
                                2 => 'J&T Express',
                                3 => 'Flash Express'
                            );
                            $shipping_method = $shipping_methods[$order['shipping_method_id']] ?? 'Unknown';
                            ?>
                            <p><strong>Method:</strong> <?php echo htmlspecialchars($shipping_method); ?></p>
                        </div>

                        <div class="order-items">
                            <h3>Order Items</h3>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                            $stmt->bind_param("i", $order['id']);
                            $stmt->execute();
                            $items = $stmt->get_result();
                            while ($item = $items->fetch_assoc()):
                            ?>
                                <div class="item">
                                    <p><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></p>
                                    <p>₱<?php echo number_format($item['subtotal'], 2); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="total-amount">
                            Total: ₱<?php echo number_format($order['total_amount'], 2); ?>
                        </div>



                        <?php if (strtolower($status) !== 'delivered' && strtolower($status) !== 'cancelled'): ?>
                        <form class="status-form" method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="new_status">
                                <option value="4" <?php echo $order['status_id'] == 4 ? 'selected' : ''; ?>>Delivered</option>
                                <option value="5" <?php echo $order['status_id'] == 5 ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status">Update Status</button>
                        </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <h2>No Orders Yet</h2>
                    <p>Start shopping to see your orders here!</p>
                    <a href="catalog.php" class="btn">Browse Catalog</a>
                </div>
            <?php endif; ?>
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