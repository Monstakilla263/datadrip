<?php
require_once 'db_connection.php';

// Add size column to cart_items table if it doesn't exist
$sql = "SHOW COLUMNS FROM cart_items LIKE 'size'";
$result = $conn->query($sql);

if ($result->num_rows === 0) {
    $sql = "ALTER TABLE cart_items ADD COLUMN size VARCHAR(20) DEFAULT 'one-size' AFTER quantity";
    if ($conn->query($sql) === TRUE) {
        echo "Size column added successfully to cart_items table<br>";
    } else {
        echo "Error adding size column: " . $conn->error . "<br>";
    }
} else {
    echo "Size column already exists in cart_items table<br>";
}

$conn->close();
echo "Database setup completed.";
?> 