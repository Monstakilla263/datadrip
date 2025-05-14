<?php
session_start();
require_once 'db_connection.php';
require_once 'ensure_order_items_table.php'; // Ensure order_items table structure is correct

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

// Check if order details exist
if (!isset($_SESSION['order_details'])) {
    header('Location: cart.php');
    exit();
}

// Get user ID from session
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
// Get user ID from session
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
if (!$stmt) {
    die('Database error: ' . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log for debugging
    error_log("User not found: $username");
    // Redirect to login page
    session_unset();
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit();
}

$user = $result->fetch_assoc();
$user_id = $user['id'];

// Get shipping cost based on method
$shipping_costs = [
    'lalamove' => 150.00,
    'jnt' => 100.00,
    'flash' => 120.00
];

$shipping_cost = $shipping_costs[$_SESSION['order_details']['shipping_method']];
$total_with_shipping = $_SESSION['order_details']['total'] + $shipping_cost;

// Get shipping_method_id from shipping_methods table
$shipping_method_name = $_SESSION['order_details']['shipping_method'];
$stmt_shipping_id = $conn->prepare("SELECT id FROM shipping_methods WHERE name = ?");
if (!$stmt_shipping_id) {
    throw new Exception("Database error preparing to fetch shipping method ID: " . $conn->error);
}
$stmt_shipping_id->bind_param("s", $shipping_method_name);
$stmt_shipping_id->execute();
$result_shipping_id = $stmt_shipping_id->get_result();
if ($row_shipping_id = $result_shipping_id->fetch_assoc()) {
    $shipping_method_id = $row_shipping_id['id'];
} else {
    throw new Exception("Invalid shipping method: " . htmlspecialchars($shipping_method_name));
}
$stmt_shipping_id->close();

// Generate order number
$order_number = 'DD' . date('YmdHis') . rand(100, 999);

// Begin transaction
$conn->begin_transaction();

try {
    // Insert into orders table with new shipping detail columns and correct shipping_method_id
    $stmt = $conn->prepare("INSERT INTO orders (user_id, order_number, order_shipping_name, order_shipping_contact, order_shipping_address, shipping_method_id, total_amount, status_id) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    if (!$stmt) {
        throw new Exception("Database error preparing insert order: " . $conn->error);
    }
    $stmt->bind_param("issssid", 
        $user_id,
        $order_number,
        $_SESSION['order_details']['full_name'],
        $_SESSION['order_details']['contact_number'],
        $_SESSION['order_details']['address'],
        $shipping_method_id, // Use the fetched ID
        $total_with_shipping
    );
    $stmt->execute();
    
    // Get the order ID
    $order_id = $conn->insert_id;
    
    // Insert order items and update inventory
    $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price, subtotal, name, size) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt_inventory = $conn->prepare("UPDATE products SET inventory = inventory - ? WHERE id = ?");

    foreach ($_SESSION['cart'] as $cart_key => $item) {
        // Extract the product ID from the cart key (format: "product_id_size")
        $product_id = (int)strtok($cart_key, '_');
        $size = substr($cart_key, strlen($product_id) + 1); // Get size from remaining string
        $subtotal = $item['price'] * $item['quantity'];
        
        // Check if product exists
        $stmt_check = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt_check->bind_param("i", $product_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("Product not found: " . $product_id);
        }
        $stmt_check->close();
        
        // Insert order item
        $stmt_items->bind_param("iisidsd",
            $order_id,
            $product_id,
            $item['quantity'],
            $item['price'],
            $subtotal,
            $item['name'],
            $size
        );
        $stmt_items->execute();
        
        // Update inventory
        $stmt_inventory->bind_param("ii", $item['quantity'], $product_id);
        $stmt_inventory->execute();
        
        // Check if inventory update was successful
        if ($stmt_inventory->affected_rows === 0) {
            throw new Exception("Failed to update inventory for product ID: " . $product_id);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Clear cart from database if user is logged in
    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    die("Error processing order: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | DataDrip</title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            box-sizing: border-box;
        }
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
            padding-top: 80px;
        }

        .confirmation-container {
            max-width: 800px;
            margin: 40px auto 100px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .success-message {
            text-align: center;
            margin-bottom: 30px;
            color: #4CAF50;
        }

        .success-message i {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        .order-details {
            margin-top: 30px;
        }

        .detail-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .detail-group:last-child {
            border-bottom: none;
        }

        .detail-group h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .shipping-details p {
            margin: 5px 0;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .cart-table th,
        .cart-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .cart-table th {
            background-color: #f8f8f8;
            font-weight: 500;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #d48e0b;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-top: 20px;
            font-weight: 500;
        }

        .back-btn:hover {
            background-color: #b37609;
        }

        footer {
            background: rgba(0, 0, 0, 0.8);
            color: white;
            text-align: center;
            padding: 20px;
            position: fixed;
            bottom: 0;
            width: 100%;
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
                <nav class="dashboard-nav">
                    <ul>
                        <li><a href="dashboard.php">Home</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="catalog.php">Catalog</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </nav>
                <div class="user-info">
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="confirmation-container">
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you for your order. Your order number is: <strong><?php echo $order_number; ?></strong></p>
        </div>

        <div class="order-details">
            <div class="detail-group">
                <h3>Shipping Details</h3>
                <div class="shipping-details">
                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($_SESSION['order_details']['full_name']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($_SESSION['order_details']['contact_number']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($_SESSION['order_details']['address']); ?></p>
                    <p><strong>Shipping Method:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['order_details']['shipping_method'])); ?></p>
                </div>
            </div>

            <div class="detail-group">
                <h3>Order Summary</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>₱<?php echo number_format($subtotal, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Subtotal:</strong></td>
                            <td><strong>₱<?php echo number_format($_SESSION['order_details']['total'], 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Shipping Fee:</strong></td>
                            <td><strong>₱<?php echo number_format($shipping_cost, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>₱<?php echo number_format($total_with_shipping, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="text-align: center;">
            <a href="dashboard.php" class="back-btn">Back to Home</a>
        </div>
    </div>

    <footer>
        <div class="copyright">
            &copy; 2025 DataDrip. All Rights Reserved.
        </div>
    </footer>
</body>
</html>

<?php
// Clear cart and order details after showing the confirmation
unset($_SESSION['cart']);
unset($_SESSION['order_details']);
?> 