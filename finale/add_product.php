<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.html');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $gender = $_POST['gender'];
    $image_url = $_POST['image_url'];
    $inventory = isset($_POST['inventory']) ? (int)$_POST['inventory'] : 0;

    // Insert product into database
    // Get category and gender IDs
    $category_stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $category_stmt->bind_param("s", $category);
    $category_stmt->execute();
    $category_result = $category_stmt->get_result();
    $category_id = $category_result->fetch_assoc()['id'];

    $gender_stmt = $conn->prepare("SELECT id FROM genders WHERE name = ?");
    $gender_stmt->bind_param("s", $gender);
    $gender_stmt->execute();
    $gender_result = $gender_stmt->get_result();
    $gender_id = $gender_result->fetch_assoc()['id'];

    // First check if product with same name and category already exists
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE name = ? AND category_id = ?");
    $check_stmt->bind_param("si", $name, $category_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Product with the same name and category already exists';
        header('Location: add_product.php');
        exit();
    }

    // Insert product into database
    $stmt = $conn->prepare("INSERT INTO products (name, price, category_id, gender_id, inventory, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $price, $category_id, $gender_id, $inventory, $image_url])) {
        // Get the last inserted product ID
        $productId = $conn->insert_id;
        
        // Prepare product data for event
        $productData = [
            'id' => $productId,
            'name' => $name,
            'price' => $price,
            'category' => $category,
            'gender' => $gender,
            'image_url' => $image_url,
            'inventory' => $inventory
        ];

        // Store success message
        $_SESSION['success'] = 'Product added successfully';
        
        // Redirect to admin inventory page
        header('Location: admin_inventory.php');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to add product';
        header('Location: add_product.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | DataDrip Admin</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .add-product-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            background: #d48e0b;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #b37609;
        }
    </style>
</head>
<body>
    <div class="add-product-container">
        <h2>Add New Product</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="color: red; margin-bottom: 15px;"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="price">Price (₱)</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="tshirts">T-Shirts</option>
                    <option value="hoodies">Hoodies</option>
                    <option value="jackets">Jackets</option>
                    <option value="lowerwear">Lower Wear</option>
                    <option value="bags">Bags</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="men">Men</option>
                    <option value="women">Women</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="image_url">Product Image URL</label>
                <input type="url" id="image_url" name="image_url" required>
                <small style="color: #666;">Enter a valid image URL from the internet</small>
            </div>
            
            <div class="form-group">
                <label for="inventory">Initial Inventory</label>
                <input type="number" id="inventory" name="inventory" min="0" value="0">
                <small style="color: #666;">Enter the initial stock quantity</small>
            </div>
            
            <button type="submit" class="submit-btn">Add Product</button>
        </form>
    </div>
</body>
</html>
