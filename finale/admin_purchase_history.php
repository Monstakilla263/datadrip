<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}

// Get all orders with user information
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Check if shipping_methods and order_statuses tables exist
$tables_exist = true;
$result = $conn->query("SHOW TABLES LIKE 'shipping_methods'");
if ($result->num_rows === 0) {
    $tables_exist = false;
}
$result = $conn->query("SHOW TABLES LIKE 'order_statuses'");
if ($result->num_rows === 0) {
    $tables_exist = false;
}

// If tables don't exist, run a simpler query
if (!$tables_exist) {
    $query = "SELECT o.*, u.username, 
              (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count
              FROM orders o 
              JOIN users u ON o.user_id = u.id";
} else {
    $query = "SELECT o.*, u.username, 
              (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as item_count,
              (SELECT name FROM shipping_methods WHERE id = o.shipping_method_id) as shipping_method,
              (SELECT name FROM order_statuses WHERE id = o.status_id) as status
              FROM orders o 
              JOIN users u ON o.user_id = u.id";
}

if (!empty($search_query)) {
    $query .= " WHERE u.username LIKE ?";
}
$query .= " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("s", $search_param);
}
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Purchase History - Admin Dashboard</title>
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

        .container {
            padding-top: 100px;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #333;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background-color: #555;
            transform: translateY(-2px);
        }

        .purchase-history {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .order-card {
            border-bottom: 1px solid #eee;
            padding: 20px;
            transition: background-color 0.3s;
        }

        .order-card:last-child {
            border-bottom: none;
        }

        .order-card:hover {
            background-color: #f9f9f9;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .order-id {
            font-weight: bold;
            color: #333;
        }

        .order-date {
            color: #666;
        }

        .order-user {
            color: #d48e0b;
            font-weight: 500;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 10px;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .detail-label {
            font-size: 0.9em;
            color: #666;
        }

        .detail-value {
            font-weight: 500;
            color: #333;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #c3e6cb;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .view-details-btn {
            background-color: #d48e0b;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }

        .view-details-btn:hover {
            background-color: #b37609;
        }

        .items-count {
            background-color: #eee;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.9em;
            color: #666;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .order-items {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            display: none;
        }

        .order-items.show {
            display: block;
        }

        .item-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .item-list li {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            color: #666;
        }

        .search-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: #d48e0b;
            box-shadow: 0 0 0 2px rgba(212, 142, 11, 0.2);
        }

        .search-btn {
            padding: 8px 20px;
            background-color: #d48e0b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .search-btn:hover {
            background-color: #b37609;
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
                    <span class="username" style="color: orange;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1>User Purchase History</h1>

        <div class="search-section">
            <form class="search-form" method="GET">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by username..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>

        <div class="purchase-history">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <span class="order-id">Order #<?php echo $order['order_number']; ?></span>
                            <span class="order-user">
                                <i class="fas fa-user"></i> 
                                <?php echo htmlspecialchars($order['username']); ?>
                            </span>
                            <span class="order-date">
                                <i class="far fa-calendar-alt"></i>
                                <?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?>
                            </span>
                        </div>

                        <div class="order-details">
                            <div class="detail-group">
                                <span class="detail-label">Status</span>
                                <span class="status-badge status-<?php echo isset($order['status']) ? strtolower($order['status']) : 'pending'; ?>">
                                    <?php echo isset($order['status']) ? ucfirst($order['status']) : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Items</span>
                                <span class="items-count"><?php echo isset($order['item_count']) ? $order['item_count'] : 0; ?> items</span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="detail-group">
                                <span class="detail-label">Shipping Method</span>
                                <span class="detail-value"><?php echo isset($order['shipping_method']) ? ucfirst($order['shipping_method']) : 'Standard'; ?></span>
                            </div>
                        </div>

                        <button class="view-details-btn" onclick="toggleOrderItems(<?php echo $order['id']; ?>)">
                            View Items
                        </button>

                        <div id="order-items-<?php echo $order['id']; ?>" class="order-items">
                            <h4>Order Items</h4>
                            <ul class="item-list">
                                <?php
                                $items_query = "SELECT * FROM order_items WHERE order_id = " . $order['id'];
                                $items_result = $conn->query($items_query);
                                while ($item = $items_result->fetch_assoc()):
                                ?>
                                    <li>
                                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                                        <span>₱<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <i class="fas fa-shopping-cart" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
                    <h2>No Purchase History</h2>
                    <p>There are no orders in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleOrderItems(orderId) {
        const itemsDiv = document.getElementById('order-items-' + orderId);
        itemsDiv.classList.toggle('show');
        
        const button = itemsDiv.previousElementSibling;
        button.textContent = itemsDiv.classList.contains('show') ? 'Hide Items' : 'View Items';
    }
    </script>
</body>
</html>
<?php $conn->close(); ?> 