<?php
require_once 'db_connection.php';

// Check if orders table exists
$result = $conn->query("SHOW TABLES LIKE 'orders'");
if ($result->num_rows === 0) {
    echo "<p>Error: Orders table does not exist!</p>";
    exit;
}

// Check if status_id column exists in orders table
$result = $conn->query("SHOW COLUMNS FROM orders LIKE 'status_id'");
if ($result->num_rows === 0) {
    // Add status_id column to orders table
    echo "<p>Adding status_id column to orders table...</p>";
    $conn->query("ALTER TABLE orders ADD COLUMN status_id INT DEFAULT 1");
    echo "<p>Status ID column added.</p>";
}

// Create order_statuses table if it doesn't exist
require_once 'create_order_statuses.php';

// Update existing orders without status_id
$conn->query("UPDATE orders SET status_id = 1 WHERE status_id IS NULL");

echo "<p></p>";
?>
