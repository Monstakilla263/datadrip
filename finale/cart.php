<?php
session_start();
require_once 'db_connection.php';
require_once 'ensure_cart_table.php'; // Ensure cart table structure is correct

// Get user ID from the database based on username
$username = $_SESSION['username'] ?? null;
$user_id = null;

if ($username) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_id = $row['id'];
    }
    $stmt->close();
}

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantities'])) {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $cart_key => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity < 1) { // Ensure quantity is at least 1
                $quantity = 1;
            }

            if (isset($_SESSION['cart'][$cart_key])) {
                list($product_id, $size) = explode('_', $cart_key);
                $product_id = (int)$product_id;

                // Get available stock from the database
                $stock_stmt = $conn->prepare("SELECT inventory FROM products WHERE id = ?");
                $stock_stmt->bind_param("i", $product_id);
                $stock_stmt->execute();
                $stock_result = $stock_stmt->get_result();
                $product_stock = $stock_result->fetch_assoc();
                $available_stock = $product_stock ? $product_stock['inventory'] : 0;
                $stock_stmt->close();

                if ($quantity > $available_stock) {
                    // Quantity exceeds stock, set error message and skip update for this item
                    $_SESSION['cart_errors'][] = "Requested quantity for " . htmlspecialchars($_SESSION['cart'][$cart_key]['name']) . " exceeds available stock. Only " . $available_stock . " items available.";
                } else {
                    // Quantity is valid, proceed with update
                    $_SESSION['cart'][$cart_key]['quantity'] = $quantity;

                    // If user is logged in, update database
                    if ($user_id) {
                        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ? AND size = ?");
                        $stmt->bind_param("iiss", $quantity, $user_id, $product_id, $size);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }
    // Redirect to prevent form resubmission and to reflect changes
    header('Location: cart.php');
    exit();
}

// Handle removing items from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id']) && isset($_POST['size'])) {
    $remove_id = (int)$_POST['remove_id'];  // Convert to integer
    $size = $_POST['size'];
    $cart_key = $remove_id . '_' . $size;
    
    if ($user_id) {
        // Remove from database
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ? AND size = ?");
        $stmt->bind_param("iis", $user_id, $remove_id, $size);
        $stmt->execute();
        $stmt->close();
    }
    
    // Also remove from session if exists
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
    }
    
    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit();
}

// Initialize cart
$cart_items = array();

// If user is logged in, get cart items from database
if ($user_id) {
    $stmt = $conn->prepare("SELECT ci.*, p.image_url FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Create unique cart key with product_id and size
        $cart_key = (int)$row['product_id'] . '_' . $row['size'];
        
        $cart_items[$cart_key] = array(
            'product_id' => (int)$row['product_id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'price' => $row['price'],
            'quantity' => $row['quantity'],
            'size' => $row['size'],
            'image_url' => $row['image_url'] // Added image_url
        );
    }
    $stmt->close();
    
    // Update session cart with database data, now including image_url
    $_SESSION['cart'] = $cart_items;
} else {
    // Use session cart if not logged in
    // $cart_items will contain whatever $_SESSION['cart'] has.
    // If image_url was not added by add_to_cart.php, it won't be here for guest users.
    $cart_items = $_SESSION['cart'] ?? array();
}

// Calculate total
$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart | DataDrip</title>
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
        .cart-container {
            max-width: 1200px;
            margin: 160px auto 100px;
            padding: 20px;
            position: relative;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .cart-table th {
            background-color: #333;
            color: white;
            font-weight: 500;
        }

        .cart-table tr:hover {
            background-color: #f9f9f9;
        }

        .cart-total {
            margin-top: 30px;
            text-align: right;
            font-size: 1.2rem;
            font-weight: bold;
        }

        .empty-cart {
            text-align: center;
            padding: 50px;
            font-size: 1.2rem;
            color: #666;
        }


        footer {
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            text-align: center;
            padding: 20px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            backdrop-filter: blur(5px);
        }

        .copyright {
            font-size: 14px;
            color: #fff;
            opacity: 0.8;
        }

        .remove-btn {
            padding: 5px 10px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .remove-btn:hover {
            background-color: #cc0000;
        }

        .checkout-section {
            text-align: right;
            margin-top: 20px;
            padding-right: 20px;
        }
        .checkout-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .checkout-btn:hover {
            background-color: #45a049;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
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
                    <span class="username"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="cart-container">
        <h1>Your Shopping Cart</h1>

        <?php if (isset($_SESSION['cart_errors']) && !empty($_SESSION['cart_errors'])): ?>
            <div class="error-messages" style="color: red; margin-bottom: 20px;">
                <ul>
                    <?php foreach ($_SESSION['cart_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['cart_errors']); // Clear errors after displaying ?>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
            </div>
        <?php else: ?>
            <form method="post" action="cart.php"> 
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $cart_key => $item):
                            // Calculate subtotal for each item
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                            
                            // Extract product_id and size from cart_key
                            list($product_id, $size) = explode('_', $cart_key);
                        ?>
                            <tr>
                                <td>
                                    <?php if (isset($item['image_url']) && !empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width:50px; height:auto; border-radius: 4px;">
                                    <?php else: ?>
                                        <span style="display:inline-block; width:50px; text-align:center;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <?php if(isset($item['size']) && $item['size'] != 'one-size'): ?>
                                        <br><small>Size: <?php echo htmlspecialchars($item['size']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['category']); ?></td>
                                <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantities[<?php echo $cart_key; ?>]" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px;">
                                </td>
                                <td>₱<?php echo number_format($subtotal, 2); ?></td>
                                <td>
                                    <form method="post" action="cart.php" style="display:inline;">
                                        <input type="hidden" name="remove_id" value="<?php echo $product_id; ?>">
                                        <input type="hidden" name="size" value="<?php echo isset($item['size']) ? $item['size'] : 'one-size'; ?>">
                                        <button type="submit" class="remove-btn">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="4" class="text-right"></td>
                            <td><button type="submit" name="update_quantities" class="checkout-btn">Update</button></td>
                            <td colspan="2" class="text-right"><strong>Total: ₱<?php echo number_format($total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </form>
            <?php if ($total > 0): ?>
                <div class="checkout-section">
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer>
        <div class="copyright">
            &copy; 2025 DataDrip. All Rights Reserved.
        </div>
    </footer>
</body>
</html> 