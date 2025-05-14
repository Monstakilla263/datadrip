<?php
session_start();
require_once 'db_connection.php';
require_once 'ensure_cart_table.php'; // Ensure cart table structure is correct

// Debug session
error_log("Session user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
error_log("Session username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'not set'));
error_log("Session contents: " . print_r($_SESSION, true));

// If user_id is not set but username is, try to get user_id from database
if (!isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        error_log("Set user_id in add_to_cart.php: " . $row['id']);
    } else {
        error_log("Failed to retrieve user_id for username: " . $username);
    }
    $stmt->close();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'error' => 'Please log in first',
        'debug' => [
            'session_id' => session_id(),
            'session_contents' => $_SESSION
        ]
    ]);
    exit();
}

// Get product details from POST data
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
$name = $_POST['name'] ?? '';
$category = $_POST['category'] ?? '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
$size = $_POST['size'] ?? 'one-size';

// Validate required fields
if (!$product_id || !$name || !$category || !$price) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required product information']);
    exit();
}

// Validate size for non-bag items
if ($category !== 'bags' && !in_array($size, ['S', 'M', 'L', 'XL'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid size']);
    exit();
}

// Validate quantity
if ($quantity < 1) $quantity = 1;
if ($quantity > 10) $quantity = 10;

// Check inventory availability
$stmt = $conn->prepare("SELECT inventory FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit();
}

if ($product['inventory'] < $quantity) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Not enough stock available. Only ' . $product['inventory'] . ' items left.']);
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Create unique cart item key that includes size
$cart_key = $product_id . '_' . $size;

// Add item to cart session
$_SESSION['cart'][$cart_key] = [
    'name' => $name,
    'category' => $category,
    'price' => $price,
    'quantity' => $quantity,
    'size' => $size
];

// Store in database
try {
    $user_id = $_SESSION['user_id'];
    error_log("Attempting to add item to cart for user_id: " . $user_id);
    error_log("Product details: product_id=" . $product_id . ", name=" . $name . ", size=" . $size);
    
    $stmt = $conn->prepare("
        INSERT INTO cart_items (user_id, product_id, name, category, price, quantity, size)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        quantity = quantity + VALUES(quantity),
        price = VALUES(price)
    ");
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("iissdis", $user_id, $product_id, $name, $category, $price, $quantity, $size);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Item added to cart successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

exit();