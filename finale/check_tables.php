<?php
require_once 'db_connection.php';

// Check if shipping_methods table exists
$result = $conn->query("SHOW TABLES LIKE 'shipping_methods'");
if ($result->num_rows === 0) {
    echo "<p>Creating shipping_methods table...</p>";
    $conn->query("CREATE TABLE shipping_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default shipping methods
    $conn->query("INSERT INTO shipping_methods (name, cost) VALUES 
        ('Standard', 100.00),
        ('Express', 150.00),
        ('Next Day', 200.00)");
    
    echo "<p>Shipping methods table created and populated.</p>";
}

// Check if order_statuses table exists
$result = $conn->query("SHOW TABLES LIKE 'order_statuses'");
if ($result->num_rows === 0) {
    echo "<p>Creating order_statuses table...</p>";
    $conn->query("CREATE TABLE order_statuses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Insert default order statuses
    $conn->query("INSERT INTO order_statuses (name) VALUES 
        ('Pending'),
        ('Processing'),
        ('Shipped'),
        ('Delivered'),
        ('Cancelled')");
    
    echo "<p>Order statuses table created and populated.</p>";
}

echo "<p>Database structure check complete. <a href='admin_purchase_history.php'>Return to Purchase History</a></p>";
?>
