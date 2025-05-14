<?php
// Enable full error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connection.php';
//require_once 'ensure_orders_table.php'; //Redundant, remove it to reduce potential issues

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                try {
                    $order_id = $_POST['order_id'];
                    $status = $_POST['status'];
                    $note = isset($_POST['note']) ? $_POST['note'] : '';
                    
                    // Status is now always numeric from the form
                    $status_id = (int)$status;
                    
                    // Check if admin_note column exists in orders table
                    $note_column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'admin_note'");
                    if ($note_column_check->num_rows === 0) {
                        // Add admin_note column if it doesn't exist
                        $conn->query("ALTER TABLE orders ADD COLUMN admin_note TEXT");
                    }
                    
                    // Update both status and note
                    $stmt = $conn->prepare("UPDATE orders SET status_id = ?, admin_note = ? WHERE id = ?");
                    $stmt->bind_param("isi", $status_id, $note, $order_id);
                    $stmt->execute();
                    
                    // Redirect to refresh the page after update
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                } catch (Exception $e) {
                    // Log the error but don't display it
                    error_log(": " . $e->getMessage());
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit();
                }
                break;
        }
    }
}

// Get selected year and month from query parameters
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Get best selling product for the selected period
$best_seller_query = "SELECT 
    p.name as product_name,
    SUM(oi.quantity) as total_quantity,
    COUNT(DISTINCT o.id) as order_count
FROM orders o
JOIN order_items oi ON o.id = oi.order_id
JOIN products p ON oi.product_id = p.id
WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ?";
if (!empty($search_query)) {
    $best_seller_query .= " AND o.id IN (SELECT id FROM orders WHERE user_id IN (SELECT id FROM users WHERE username LIKE ?))";
}
$best_seller_query .= " GROUP BY p.name ORDER BY total_quantity DESC LIMIT 1";

$stmt = $conn->prepare($best_seller_query);
if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("sss", $selected_year, $selected_month, $search_param);
} else {
    $stmt->bind_param("ss", $selected_year, $selected_month);
}
$stmt->execute();
$best_seller = $stmt->get_result()->fetch_assoc();

// Get total income for the selected period, excluding cancelled orders
$query = "SELECT 
            SUM(CASE WHEN status_id != 5 THEN total_amount ELSE 0 END) as total_income,
            COUNT(*) as total_orders,
            COUNT(CASE WHEN status_id = 4 THEN 1 END) as completed_orders
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ?";
if (!empty($search_query)) {
    $query .= " AND u.username LIKE ?";
}
$stmt = $conn->prepare($query);
if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $stmt->bind_param("sss", $selected_year, $selected_month, $search_param);
} else {
    $stmt->bind_param("ss", $selected_year, $selected_month);
}
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

// Check if order_statuses table exists
$result = $conn->query("SHOW TABLES LIKE 'order_statuses'");
$order_statuses_exists = ($result->num_rows > 0);

// Get all orders for the selected period
$query = "SELECT o.*, u.username, o.status_id,
          CASE 
              WHEN os.status IS NOT NULL THEN os.status
              WHEN o.status_id = 1 THEN 'pending'
              WHEN o.status_id = 2 THEN 'processing'
              WHEN o.status_id = 3 THEN 'shipped'
              WHEN o.status_id = 4 THEN 'delivered'
              WHEN o.status_id = 5 THEN 'cancelled'
              ELSE 'pending'
          END as status_name
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          LEFT JOIN order_status os ON o.status_id = os.id
          WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ?";


if (!empty($search_query)) {
    $query .= " AND u.username LIKE ?";
}
$query .= " ORDER BY o.created_at DESC";

try {
    $stmt = $conn->prepare($query);
    if (!empty($search_query)) {
        $search_param = "%$search_query%";
        $stmt->bind_param("sss", $selected_year, $selected_month, $search_param);
    } else {
        $stmt->bind_param("ss", $selected_year, $selected_month);
    }
    $stmt->execute();
    $orders = $stmt->get_result();

    if ($orders === false) {
        echo "";
    } else {
        echo "<div class='debug-message'>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error-message'>Error: " . $e->getMessage() . "</div>";
    $orders = false;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Reports - Admin Dashboard</title>
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

        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
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

        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .filter-form button {
            padding: 8px 20px;
            background-color: #d48e0b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .filter-form button:hover {
            background-color: #b37609;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }

        .summary-card h3 {
            color: #666;
            margin-bottom: 10px;
        }

        .summary-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        .income-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .income-table th,
        .income-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .income-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d4edda; color: #155724; }
        .status-delivered { background-color: #c3e6cb; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .edit-btn {
            background-color: #d48e0b;
            color: white;
        }

        .edit-btn:hover {
            background-color: #b37609;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-btn {
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .modal-form button {
            padding: 10px;
            background-color: #d48e0b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .modal-form button:hover {
            background-color: #b37609;
        }

        .best-seller {
            background: linear-gradient(135deg, #fff 0%, #fff8e8 100%);
        }

        .best-seller-info {
            text-align: center;
        }

        .best-seller .product-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #d48e0b;
            margin-bottom: 5px;
        }

        .best-seller .stats {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .best-seller .stats span {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(212, 142, 11, 0.1);
            border-radius: 12px;
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

        <h1>Income Reports</h1>

        <div class="filter-section">
            <form class="filter-form" method="GET">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by username..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <select name="year" required>
                    <?php
                    $current_year = date('Y');
                    for ($year = $current_year; $year >= $current_year - 5; $year--) {
                        $selected = $year == $selected_year ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
                <select name="month" required>
                    <?php
                    $months = [
                        '01' => 'January', '02' => 'February', '03' => 'March',
                        '04' => 'April', '05' => 'May', '06' => 'June',
                        '07' => 'July', '08' => 'August', '09' => 'September',
                        '10' => 'October', '11' => 'November', '12' => 'December'
                    ];
                    foreach ($months as $value => $name) {
                        $selected = $value == $selected_month ? 'selected' : '';
                        echo "<option value='$value' $selected>$name</option>";
                    }
                    ?>
                </select>
                <button type="submit">Apply Filter</button>
            </form>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Income</h3>
                <div class="value">₱<?php echo number_format($summary['total_income'] ?? 0, 2); ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Orders</h3>
                <div class="value"><?php echo $summary['total_orders'] ?? 0; ?></div>
            </div>
            <div class="summary-card">
                <h3>Completed Orders</h3>
                <div class="value"><?php echo $summary['completed_orders'] ?? 0; ?></div>
            </div>
            <div class="summary-card best-seller">
                <h3>Best Selling Product</h3>
                <?php if ($best_seller): ?>
                    <div class="best-seller-info">
                        <div class="product-name"><?php echo htmlspecialchars($best_seller['product_name']); ?></div>
                        <div class="stats">
                            <span class="quantity"><?php echo $best_seller['total_quantity']; ?> units sold</span>
                            <span class="orders"><?php echo $best_seller['order_count']; ?> orders</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="value">No sales data</div>
                <?php endif; ?>
            </div>
        </div>

        <table class="income-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                try {
                    $stmt = $conn->prepare($query);
                    if (!empty($search_query)) {
                        $search_param = "%$search_query%";
                        $stmt->bind_param("sss", $selected_year, $selected_month, $search_param);
                    } else {
                        $stmt->bind_param("ss", $selected_year, $selected_month);
                    }
                    $stmt->execute();
                    $orders = $stmt->get_result();
                
                
                } catch (Exception $e) {
                    echo "<div class=\'error-message\'>Error: " . $e->getMessage() . "</div>";
                    $orders = false;
                }
                
                if($orders):
                    while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_number']; ?></td>
                            <td><?php echo htmlspecialchars($order['username']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo isset($order['status_name']) ? strtolower($order['status_name']) : 'pending'; ?>">
                                    <?php echo isset($order['status_name']) ? ucfirst($order['status_name']) : 'Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-btn edit-btn" onclick="openEditModal(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['admin_note'] ?? ''); ?>', '<?php echo isset($order['status_name']) ? $order['status_name'] : 'pending'; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; 
                 endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Order</h2>
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
            </div>
            <form class="modal-form" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="edit_order_id">
                
                <div>
                    <label for="status">Status:</label>
                    <select name="status" id="edit_status" required>
                        <option value="1">Pending</option>
                        <option value="2">Processing</option>
                        <option value="3">Shipped</option>
                        <option value="4">Delivered</option>
                        <option value="5">Cancelled</option>
                    </select>
                </div>

                <div>
                    <label for="note">Admin Note:</label>
                    <textarea name="note" id="edit_note" rows="4"></textarea>
                </div>

                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(orderId, note, status) {
            document.getElementById('editModal').style.display = 'block';
            document.getElementById('edit_order_id').value = orderId;
            document.getElementById('edit_note').value = note;
            
            // Map status names to IDs
            let statusId = 1; // Default to pending (1)
            switch(status.toLowerCase()) {
                case 'pending': statusId = 1; break;
                case 'processing': statusId = 2; break;
                case 'shipped': statusId = 3; break;
                case 'delivered': statusId = 4; break;
                case 'cancelled': statusId = 5; break;
            }
            document.getElementById('edit_status').value = statusId;
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>