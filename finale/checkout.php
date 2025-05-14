<?php
session_start();
require_once 'db_connection.php';
require_once 'ensure_cart_table.php'; // Ensure cart table structure is correct

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.html');
    exit();
}

// Get user ID from the database based on username
$username = $_SESSION['username'] ?? null;
$user_id = null;

if ($username) {
    // Fetch id, full_name, shipping_address, email, and contact_number
    $stmt = $conn->prepare("SELECT id, full_name, shipping_address, email, contact_number FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
            $full_name_db = $row['full_name']; // Store separately to avoid conflict if POST exists
            $shipping_address_db = $row['shipping_address'];
            $email_db = $row['email'];
            $contact_number_db = $row['contact_number'];
        }
        $stmt->close();
    } else {
        // Handle error if prepare() fails - e.g., log error, display message
        // For now, $user_id will remain null, and the check below will catch it
    }
}

// Check if cart is empty or user ID is not set (user must be logged in)
if (empty($_SESSION['cart']) || !$user_id) { 
    // For debugging:
    // var_dump($_SESSION['cart']);
    // var_dump($user_id);
    // var_dump($_SESSION['username']);
    // exit("Redirecting due to empty cart or missing user_id.");

    header('Location: cart.php'); 
    exit();
}

// Use $_SESSION['cart'] as the source of truth for cart items
$cart_items = $_SESSION['cart']; // Renaming to cart_items for consistency with HTML
$total = 0;

foreach ($cart_items as $item) {
    if (isset($item['price']) && isset($item['quantity'])) {
        $total += $item['price'] * $item['quantity'];
    } else {
        // Handle cases where price or quantity might be missing in session cart item
        // This indicates an issue upstream (e.g., in add_to_cart.php)
        // For now, you could log an error or skip the item
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['full_name', 'contact_number', 'address', 'shipping_method'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }

    if (empty($errors)) {
        // Store order details in session
        $_SESSION['order_details'] = [
            'full_name' => $_POST['full_name'],
            'contact_number' => $_POST['contact_number'],
            'address' => $_POST['address'],
            'shipping_method' => $_POST['shipping_method'],
            'total' => $total
        ];
        
        // Redirect to order confirmation
        header('Location: order_confirmation.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | DataDrip</title>
    <link rel="stylesheet" href="styles.css">
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
        .checkout-container {
            max-width: 800px;
            margin: 120px auto 100px;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .checkout-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
        }

        .form-group input, .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .shipping-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .shipping-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .shipping-method:hover {
            border-color: #d48e0b;
        }

        .shipping-method.selected {
            border-color: #d48e0b;
            background-color: #fff8e8;
        }

        .shipping-method img {
            width: 80px;
            height: auto;
        }

        .shipping-method-radio {
            display: none;
        }

        .order-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .submit-btn {
            background-color: #d48e0b;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background-color: #b37609;
        }

        .error-message {
            color: #ff4444;
            font-size: 14px;
            margin-top: 5px;
        }

        .top-header {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        /* Styles for readonly fields */
        input[readonly],
        textarea[readonly] {
            background-color: #e9ecef; /* A common bootstrap readonly background color */
            color: #495057;           /* A common bootstrap readonly text color */
            cursor: default;          /* Show default cursor as they are not interactive for typing */
            /* border-color: #ced4da; */ /* Optional: if you want to change border */
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
                    <span class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="checkout-container">
        <h1>Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="checkout-form">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : (isset($full_name_db) ? htmlspecialchars($full_name_db) : ''); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" id="contact_number" name="contact_number" required
                       value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : (isset($contact_number_db) ? htmlspecialchars($contact_number_db) : ''); ?>" readonly>
            </div>

            <div class="form-group">
                <label for="address">Delivery Address</label>
                <textarea id="address" name="address" required readonly><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : (isset($shipping_address_db) ? htmlspecialchars($shipping_address_db) : ''); ?></textarea>
                <div style="margin-top: 10px;">
                    <button type="button" id="editShippingDetailsBtn" class="submit-btn" style="background-color: #555; width: auto; padding: 10px 15px;">Edit Details</button>
                    <button type="button" id="confirmShippingDetailsBtn" class="submit-btn" style="background-color: #28a745; width: auto; padding: 10px 15px; display: none;">Confirm Details</button>
                </div>
            </div>

            <div class="form-group">
                <label>Shipping Method</label>
                <div class="shipping-methods">
                    <label class="shipping-method">
                        <input type="radio" name="shipping_method" value="lalamove" class="shipping-method-radio" required>
                        <img src="images/lalamove.png" alt="Lalamove">
                        <span>Lalamove</span>
                        <span>₱150.00</span>
                    </label>
                    <label class="shipping-method">
                        <input type="radio" name="shipping_method" value="jnt" class="shipping-method-radio">
                        <img src="images/jnt.png" alt="J&T Express">
                        <span>J&T Express</span>
                        <span>₱100.00</span>
                    </label>
                    <label class="shipping-method">
                        <input type="radio" name="shipping_method" value="flash" class="shipping-method-radio">
                        <img src="images/flash.png" alt="Flash Express">
                        <span>Flash Express</span>
                        <span>₱120.00</span>
                    </label>
                </div>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
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
                        <?php foreach ($cart_items as $item): 
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
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit" class="submit-btn">Place Order</button>
        </form>
    </div>

    <footer>
        <div class="copyright">
            &copy; 2025 DataDrip. All Rights Reserved.
        </div>
    </footer>

    <script>
        // Add selected class to shipping method when selected
        document.querySelectorAll('.shipping-method-radio').forEach(radio => {
            radio.addEventListener('change', function() {
                // Remove selected class from all shipping methods
                document.querySelectorAll('.shipping-method').forEach(method => {
                    method.classList.remove('selected');
                });
                // Add selected class to the chosen shipping method
                this.closest('.shipping-method').classList.add('selected');
            });
        });

        // Script for Edit/Confirm shipping details
        const fullNameInput = document.getElementById('full_name');
        const contactNumberInput = document.getElementById('contact_number');
        const addressTextarea = document.getElementById('address');
        const editBtn = document.getElementById('editShippingDetailsBtn');
        const confirmBtn = document.getElementById('confirmShippingDetailsBtn');

        editBtn.addEventListener('click', function() {
            fullNameInput.readOnly = false;
            contactNumberInput.readOnly = false;
            addressTextarea.readOnly = false;

            editBtn.style.display = 'none';
            confirmBtn.style.display = 'inline-block';

            fullNameInput.focus();
        });

        confirmBtn.addEventListener('click', function() {
            fullNameInput.readOnly = true;
            contactNumberInput.readOnly = true;
            addressTextarea.readOnly = true;

            confirmBtn.style.display = 'none';
            editBtn.style.display = 'inline-block';
        });
    </script>
</body>
</html> 