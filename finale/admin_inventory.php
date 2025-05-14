<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}

// Include database connection
require_once 'db_connection.php';

// Handle price updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_price'])) {
    $product_id = $_POST['product_id'];
    $new_price = $_POST['price'];
    
    // Validate price (optional, but recommended)
    if (!is_numeric($new_price) || $new_price < 0) {
        $_SESSION['message'] = "Error: Invalid price value.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET price = ? WHERE id = ?");
        $stmt->bind_param("di", $new_price, $product_id);
        $stmt->execute();
        
        // Add success message
        $_SESSION['message'] = "Price updated successfully!";
    }
    header("Location: admin_inventory.php");
    exit();
}

// Handle inventory updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $product_id = $_POST['product_id'];
    $new_inventory = $_POST['inventory'];
    
    // Validate inventory (optional, but recommended)
     if (!is_numeric($new_inventory) || $new_inventory < 0) {
        $_SESSION['message'] = "Error: Invalid inventory value.";
    } else {
        $stmt = $conn->prepare("UPDATE products SET inventory = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_inventory, $product_id);
        $stmt->execute();
        
        // Add success message
        $_SESSION['message'] = "Inventory updated successfully!";
    }
    header("Location: admin_inventory.php");
    exit();
}

// Fetch all products with their current inventory and price
$query = "SELECT p.id, p.name, c.name as category, g.name as gender, p.price, p.inventory 
          FROM products p
          JOIN categories c ON p.category_id = c.id
          JOIN genders g ON p.gender_id = g.id
          ORDER BY 
            c.name,
            CAST(SUBSTRING_INDEX(p.name, ' ', -1) AS UNSIGNED),
            p.name";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Add Font Awesome for icons -->
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

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-buttons .username {
            color: orange;
            margin-right: 10px;
        }

        .logout-btn {
            padding: 8px 16px;
            background: transparent;
            border: 1px solid #fff;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: #fff;
            color: #000;
        }

        .logo-container img {
            height: 400px;
            width: auto;
            position: static;
            margin-top: 24px;
        }
        .container {
            padding-top: 100px;
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 20px;
            padding-right: 20px;
        }

        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .update-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 0;
        }

        .inventory-input {
            width: 80px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }

        .update-btn {
            padding: 8px 16px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .inventory-table th, .inventory-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .inventory-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .inventory-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .update-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .inventory-input {
            width: 80px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .update-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .update-btn:hover {
            background-color: #45a049;
        }
        
        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        
        .category-header {
            background-color: #333;
            color: white;
            padding: 10px;
            margin-top: 20px;
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

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        body {
            background: #f5f5f5;
            min-height: 100vh;
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
                    <span class="username"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <h1>Inventory Management</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php 
                echo $_SESSION['message'];
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>

        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Gender</th>
                    <th>Price (₱)</th>
                    <th>Current Stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $current_category = '';
                while ($row = $result->fetch_assoc()):
                    if ($current_category != $row['category']):
                        $current_category = $row['category'];
                        ?>
                        <tr>
                            <td colspan="6" class="category-header">
                                <?php echo ucfirst($current_category); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo ucfirst($row['category']); ?></td>
                        <td><?php echo ucfirst($row['gender']); ?></td>
                        <td>
                            <form method="POST" class="update-form" action="admin_inventory.php">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <input type="number" name="price" value="<?php echo $row['price']; ?>" 
                                       min="0" step="0.01" class="inventory-input" required>
                                <button type="submit" name="update_price" class="update-btn">
                                    Update Price
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" class="update-form" action="admin_inventory.php">
                                <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                <input type="number" name="inventory" value="<?php echo $row['inventory']; ?>" 
                                           min="0" class="inventory-input" required>
                                    <button type="submit" name="update_inventory" class="update-btn">
                                        Update Stock
                                    </button>
                                </form>
                        </td>
                        <td>
                            <button class="delete-btn" onclick="deleteProduct(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                    Delete
                                </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <script src="product_sync.js"></script>
        <script>
        function deleteProduct(productId, productName) {
            if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                fetch('delete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Trigger product deletion event
                        triggerProductDeletion(productId);
                        // Refresh the page after successful deletion
                        window.location.reload();
                    } else {
                        alert('Failed to delete product: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the product');
                });
            }
        }
        </script>
    </div>
<footer style="margin-top: 50px;">
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